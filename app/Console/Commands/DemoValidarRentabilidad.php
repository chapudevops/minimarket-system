<?php

namespace App\Console\Commands;

use App\Estados\EstadoDocumento;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Estado de resultados del escenario y comprobacion de invariantes.
 *
 * Calcula el P&L de verdad, no un flujo de caja:
 *
 *     INGRESOS NETOS - COSTO DE MERCADERIA VENDIDA = UTILIDAD BRUTA
 *     UTILIDAD BRUTA - GASTOS OPERATIVOS           = RESULTADO OPERATIVO
 *
 * Los ingresos van NETOS (sin IGV) porque el costo de compra tambien lo esta.
 * Mezclar las dos bases infla el margen 18 puntos.
 */
class DemoValidarRentabilidad extends Command
{
    protected $signature = 'demo:validar-rentabilidad {--json : Salida en JSON}';

    protected $description = 'Calcula el resultado del escenario DEMO y valida sus invariantes';

    public function handle(): int
    {
        $pl = $this->estadoDeResultados();
        $fallos = $this->invariantes();

        if ($this->option('json')) {
            $this->line(json_encode(['resultado' => $pl, 'invariantes_fallidas' => $fallos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $fallos === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->mostrar($pl, $fallos);

        return $fallos === [] && $pl['resultado_operativo'] > 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string,float|int> */
    private function estadoDeResultados(): array
    {
        $ventas = DB::table('ventas')->where('estado', EstadoVenta::APROBADA);

        $ingresoBruto = (float) (clone $ventas)->sum('total');
        $ingresoNeto = (float) (clone $ventas)->sum('subtotal');
        $numeroVentas = (int) (clone $ventas)->count();

        $unidades = (int) DB::table('venta_detalles as d')
            ->join('ventas as v', 'v.id', '=', 'd.venta_id')
            ->where('v.estado', EstadoVenta::APROBADA)
            ->sum('d.cantidad');

        // Costo de lo VENDIDO, al precio de compra (neto) de cada producto.
        $costoVendido = (float) DB::table('venta_detalles as d')
            ->join('ventas as v', 'v.id', '=', 'd.venta_id')
            ->join('productos as p', 'p.id', '=', 'd.producto_id')
            ->where('v.estado', EstadoVenta::APROBADA)
            ->sum(DB::raw('d.cantidad * p.precio_compra'));

        // Lo devuelto sale de ambos lados: ni es ingreso ni es costo.
        $devueltoNeto = (float) DB::table('notas_credito')
            ->where('estado', EstadoDocumento::REGISTRADA)->sum('subtotal');

        $costoDevuelto = (float) DB::table('nota_credito_detalles as nd')
            ->join('notas_credito as n', 'n.id', '=', 'nd.nota_credito_id')
            ->join('productos as p', 'p.id', '=', 'nd.producto_id')
            ->where('n.estado', EstadoDocumento::REGISTRADA)
            ->sum(DB::raw('nd.cantidad * p.precio_compra'));

        $ingresoNeto = round($ingresoNeto - $devueltoNeto, 2);
        $costoVendido = round($costoVendido - $costoDevuelto, 2);
        $utilidadBruta = round($ingresoNeto - $costoVendido, 2);
        $gastos = (float) DB::table('gastos')->sum('monto');

        return [
            'ingresos_brutos' => round($ingresoBruto, 2),
            'ingresos_netos' => $ingresoNeto,
            'costo_mercaderia_vendida' => $costoVendido,
            'utilidad_bruta' => $utilidadBruta,
            'margen_bruto_pct' => $ingresoNeto > 0 ? round($utilidadBruta / $ingresoNeto * 100, 2) : 0.0,
            'gastos_operativos' => round($gastos, 2),
            'resultado_operativo' => round($utilidadBruta - $gastos, 2),
            'numero_ventas' => $numeroVentas,
            'unidades_vendidas' => $unidades,
            'ticket_promedio' => $numeroVentas > 0 ? round($ingresoBruto / $numeroVentas, 2) : 0.0,
        ];
    }

    /**
     * Cada invariante devuelve el numero de filas que la incumplen. Cero es
     * lo esperado; cualquier otra cosa es un escenario mal generado.
     *
     * @return array<string,int>
     */
    private function invariantes(): array
    {
        $comprobaciones = [
            'stock negativo' => fn () => DB::table('producto_almacen')->where('stock', '<', 0)->count(),

            'producto vendido cuyo costo supera su venta neta' => fn () => DB::table('productos as p')
                ->whereIn('p.id', fn ($q) => $q->select('producto_id')->from('venta_detalles'))
                ->whereRaw('p.precio_compra >= p.precio_venta / 1.18')
                ->count(),

            'venta anterior a la primera compra' => function () {
                $primeraCompra = DB::table('compras')->min('fecha_emision');

                return $primeraCompra === null ? 0 : DB::table('ventas')
                    ->whereDate('fecha_emision', '<', $primeraCompra)->count();
            },

            'nota de credito anterior a su venta' => fn () => DB::table('notas_credito as n')
                ->join('ventas as v', 'v.id', '=', 'n.venta_id')
                ->whereColumn('n.fecha_emision', '<', 'v.fecha_emision')->count(),

            'devolucion mayor que lo vendido' => fn () => DB::table(DB::raw('(
                    SELECT n.venta_id, nd.producto_id, SUM(nd.cantidad) devuelto
                    FROM nota_credito_detalles nd
                    JOIN notas_credito n ON n.id = nd.nota_credito_id
                    GROUP BY n.venta_id, nd.producto_id
                ) dev'))
                ->join(DB::raw('(
                    SELECT venta_id, producto_id, SUM(cantidad) vendido
                    FROM venta_detalles GROUP BY venta_id, producto_id
                ) ven'), function ($j) {
                    $j->on('ven.venta_id', '=', 'dev.venta_id')
                      ->on('ven.producto_id', '=', 'dev.producto_id');
                })
                ->whereRaw('dev.devuelto > ven.vendido')->count(),

            'cierre de caja anterior a su apertura' => fn () => DB::table('apertura_cajas')
                ->whereNotNull('fecha_cierre')
                ->whereColumn('fecha_cierre', '<', 'fecha_apertura')->count(),

            'venta sin caja del dia abierta' => fn () => DB::table('ventas as v')
                ->where('v.estado', EstadoVenta::APROBADA)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('apertura_cajas as a')
                    ->whereRaw('DATE(a.fecha_apertura) = DATE(v.fecha_emision)'))
                ->count(),

            'venta con total distinto a su detalle' => fn () => DB::table(DB::raw('(
                    SELECT v.id, v.total, SUM(d.total) suma
                    FROM ventas v JOIN venta_detalles d ON d.venta_id = v.id
                    GROUP BY v.id, v.total
                ) x'))->whereRaw('ABS(x.total - x.suma) > 0.05')->count(),

            'documento DEMO enviado a SUNAT' => fn () => DB::table('ventas')
                ->whereNotIn('estado_sunat', [EstadoSunat::NO_ENVIADO, EstadoSunat::NO_APLICA])->count()
                + DB::table('ventas')->whereNotNull('enviado_sunat_at')->count(),

            'producto sin afectacion tributaria vendido' => fn () => DB::table('productos as p')
                ->whereIn('p.id', fn ($q) => $q->select('producto_id')->from('venta_detalles'))
                ->whereNotIn('p.operacion', \App\Sunat\Tributos::AFECTACIONES)
                ->count(),
        ];

        $fallos = [];
        foreach ($comprobaciones as $nombre => $fn) {
            $n = (int) $fn();
            if ($n > 0) {
                $fallos[$nombre] = $n;
            }
        }

        return $fallos;
    }

    private function mostrar(array $pl, array $fallos): void
    {
        $sol = fn ($v) => 'S/ '.number_format($v, 2);

        $this->newLine();
        $this->line('  <fg=cyan>ESTADO DE RESULTADOS — ESCENARIO DEMO</>');
        $this->line('  <fg=gray>Cifras simuladas. No son resultados comerciales reales.</>');
        $this->newLine();

        $this->line(sprintf('    Ingresos netos (sin IGV)     %14s', $sol($pl['ingresos_netos'])));
        $this->line(sprintf('  − Costo de mercadería vendida  %14s', $sol($pl['costo_mercaderia_vendida'])));
        $this->line('    ────────────────────────────────────────────');
        $this->line(sprintf('  = <fg=green>Utilidad bruta               %14s</>', $sol($pl['utilidad_bruta'])));
        $this->line(sprintf('    Margen bruto                 %13s%%', number_format($pl['margen_bruto_pct'], 2)));
        $this->newLine();
        $this->line(sprintf('  − Gastos operativos            %14s', $sol($pl['gastos_operativos'])));
        $this->line('    ────────────────────────────────────────────');

        $color = $pl['resultado_operativo'] > 0 ? 'green' : 'red';
        $this->line(sprintf('  = <fg=%s>RESULTADO OPERATIVO          %14s</>', $color, $sol($pl['resultado_operativo'])));

        $this->newLine();
        $this->line(sprintf('    Ventas                       %14d', $pl['numero_ventas']));
        $this->line(sprintf('    Unidades vendidas            %14d', $pl['unidades_vendidas']));
        $this->line(sprintf('    Ticket promedio              %14s', $sol($pl['ticket_promedio'])));
        $this->line(sprintf('    Ingresos con IGV             %14s', $sol($pl['ingresos_brutos'])));

        $this->newLine();
        if ($fallos === []) {
            $this->info('  Invariantes: las 10 comprobaciones pasan.');
        } else {
            $this->error('  Invariantes incumplidas:');
            foreach ($fallos as $nombre => $n) {
                $this->line(sprintf('    <fg=red>✗</> %-50s %d caso(s)', $nombre, $n));
            }
        }
    }
}
