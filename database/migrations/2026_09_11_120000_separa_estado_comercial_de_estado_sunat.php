<?php

use App\Estados\EstadoDevolucion;
use App\Estados\EstadoDocumento;
use App\Estados\EstadoPago;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa el estado comercial del estado de envio a SUNAT.
 *
 * La migracion es ADITIVA: no borra ni renombra columnas. Solo agrega campos
 * que no existian y reescribe valores con un mapeo explicito, uno a uno.
 *
 * Mapeo verificado contra los datos reales antes de escribirlo:
 *
 *   ventas.estado         COMPLETADA(318) -> APROBADA
 *                         PENDIENTE(6)    -> APROBADA + estado_pago=PENDIENTE
 *                                            (las 6 son tipo_venta=CREDITO con
 *                                             cuotas: era estado de COBRO, no
 *                                             de validez comercial)
 *                         ANULADA(4)      -> ANULADA
 *
 *   *.estado_sunat        PENDIENTE       -> NO_ENVIADO  (todas con
 *                                            enviado_sunat_at NULL: nunca se
 *                                            intento enviar)
 *                         ACEPTADO        -> sin cambio
 *
 *   guias_remision        gana `estado` (EMITIDA): era el unico documento sin
 *                         estado interno, usaba estado_sunat como si lo fuera.
 *
 * Si aparece un valor fuera del mapeo la migracion ABORTA en vez de adivinar.
 */
return new class extends Migration
{
    /** Valores conocidos. Cualquier otra cosa detiene la migracion. */
    private const MAPEO_VENTA = [
        'COMPLETADA' => EstadoVenta::APROBADA,
        'PENDIENTE' => EstadoVenta::APROBADA,
        'ANULADA' => EstadoVenta::ANULADA,
        // Ya migrado (la migracion es idempotente).
        'APROBADA' => EstadoVenta::APROBADA,
    ];

    private const MAPEO_SUNAT = [
        'PENDIENTE' => EstadoSunat::NO_ENVIADO,
        'ENVIADO' => EstadoSunat::ENVIANDO,
        'ACEPTADO' => EstadoSunat::ACEPTADO,
        'OBSERVADO' => EstadoSunat::OBSERVADO,
        'RECHAZADO' => EstadoSunat::RECHAZADO,
        'NO_ENVIADO' => EstadoSunat::NO_ENVIADO,
        'EN_COLA' => EstadoSunat::EN_COLA,
        'ENVIANDO' => EstadoSunat::ENVIANDO,
        'ERROR' => EstadoSunat::ERROR,
        'NO_APLICA' => EstadoSunat::NO_APLICA,
    ];

    private const TABLAS_SUNAT = ['ventas', 'notas_credito', 'notas_debito', 'guias_remision'];

    public function up(): void
    {
        $this->verificarValoresConocidos();

        Schema::table('ventas', function (Blueprint $tabla) {
            if (! Schema::hasColumn('ventas', 'estado_devolucion')) {
                $tabla->string('estado_devolucion', 20)
                    ->default(EstadoDevolucion::SIN_DEVOLUCION)
                    ->after('estado');
            }
            if (! Schema::hasColumn('ventas', 'estado_pago')) {
                $tabla->string('estado_pago', 20)
                    ->default(EstadoPago::PAGADA)
                    ->after('estado_devolucion');
            }
        });

        // Guias de remision: unico documento que no tenia estado interno.
        Schema::table('guias_remision', function (Blueprint $tabla) {
            if (! Schema::hasColumn('guias_remision', 'estado')) {
                $tabla->string('estado', 20)
                    ->default(EstadoDocumento::REGISTRADA)
                    ->after('fecha_traslado');
            }
        });

        DB::transaction(function () {
            $this->migrarVentas();
            $this->migrarEstadosSunat();
            $this->recalcularDevoluciones();
        });
    }

    /**
     * Aborta antes de tocar nada si hay un valor que el mapeo no contempla.
     * Adivinar en silencio es peor que fallar.
     */
    private function verificarValoresConocidos(): void
    {
        $desconocidos = DB::table('ventas')
            ->distinct()->pluck('estado')
            ->reject(fn ($v) => array_key_exists($v, self::MAPEO_VENTA))
            ->all();

        if ($desconocidos !== []) {
            throw new RuntimeException(
                'ventas.estado tiene valores sin mapeo: '.implode(', ', $desconocidos).
                '. Agregalos a MAPEO_VENTA antes de migrar.'
            );
        }

        foreach (self::TABLAS_SUNAT as $tabla) {
            if (! Schema::hasColumn($tabla, 'estado_sunat')) {
                continue;
            }

            $raros = DB::table($tabla)
                ->distinct()->pluck('estado_sunat')
                ->reject(fn ($v) => $v === null || array_key_exists($v, self::MAPEO_SUNAT))
                ->all();

            if ($raros !== []) {
                throw new RuntimeException(
                    "{$tabla}.estado_sunat tiene valores sin mapeo: ".implode(', ', $raros)
                );
            }
        }
    }

    private function migrarVentas(): void
    {
        // El cobro sale de las cuotas, que es la fuente real: una venta al
        // contado no tiene cuotas y queda PAGADA.
        foreach (DB::table('ventas')->select('id', 'estado')->cursor() as $venta) {
            $cuotas = DB::table('venta_cuotas')->where('venta_id', $venta->id)->count();
            $pagadas = DB::table('venta_cuotas')
                ->where('venta_id', $venta->id)->where('estado', 'PAGADA')->count();

            DB::table('ventas')->where('id', $venta->id)->update([
                'estado' => self::MAPEO_VENTA[$venta->estado],
                'estado_pago' => EstadoPago::desdeCuotas($cuotas, $pagadas),
            ]);
        }
    }

    private function migrarEstadosSunat(): void
    {
        foreach (self::TABLAS_SUNAT as $tabla) {
            if (! Schema::hasColumn($tabla, 'estado_sunat')) {
                continue;
            }

            foreach (self::MAPEO_SUNAT as $viejo => $nuevo) {
                if ($viejo === $nuevo) {
                    continue;
                }

                $consulta = DB::table($tabla)->where('estado_sunat', $viejo);

                // Un PENDIENTE que ya se intento enviar no es "no enviado":
                // es un envio que fallo y se puede reintentar.
                if ($viejo === 'PENDIENTE' && Schema::hasColumn($tabla, 'enviado_sunat_at')) {
                    (clone $consulta)->whereNotNull('enviado_sunat_at')
                        ->update(['estado_sunat' => EstadoSunat::ERROR]);

                    $consulta->whereNull('enviado_sunat_at');
                }

                $consulta->update(['estado_sunat' => $nuevo]);
            }
        }
    }

    /** Deja estado_devolucion coherente con las notas de credito ya emitidas. */
    private function recalcularDevoluciones(): void
    {
        $ventasConNota = DB::table('notas_credito')
            ->whereNotNull('venta_id')->distinct()->pluck('venta_id');

        foreach ($ventasConNota as $ventaId) {
            $vendidas = (int) DB::table('venta_detalles')->where('venta_id', $ventaId)->sum('cantidad');

            $devueltas = (int) DB::table('nota_credito_detalles as d')
                ->join('notas_credito as n', 'n.id', '=', 'd.nota_credito_id')
                ->where('n.venta_id', $ventaId)
                ->where('n.estado', EstadoDocumento::REGISTRADA)
                ->sum('d.cantidad');

            DB::table('ventas')->where('id', $ventaId)->update([
                'estado_devolucion' => EstadoDevolucion::desdeUnidades($vendidas, $devueltas),
            ]);
        }
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table('ventas')->where('estado', EstadoVenta::APROBADA)
                ->where('estado_pago', EstadoPago::PAGADA)
                ->update(['estado' => 'COMPLETADA']);

            DB::table('ventas')->where('estado', EstadoVenta::APROBADA)
                ->whereIn('estado_pago', [EstadoPago::PENDIENTE, EstadoPago::PARCIAL])
                ->update(['estado' => 'PENDIENTE']);

            foreach (self::TABLAS_SUNAT as $tabla) {
                if (Schema::hasColumn($tabla, 'estado_sunat')) {
                    DB::table($tabla)->whereIn('estado_sunat', [EstadoSunat::NO_ENVIADO, EstadoSunat::ERROR])
                        ->update(['estado_sunat' => 'PENDIENTE']);
                }
            }
        });

        Schema::table('ventas', function (Blueprint $tabla) {
            $tabla->dropColumn(['estado_devolucion', 'estado_pago']);
        });

        Schema::table('guias_remision', function (Blueprint $tabla) {
            $tabla->dropColumn('estado');
        });
    }
};
