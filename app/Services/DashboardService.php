<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\AperturaCaja;
use App\Models\Empresa;
use App\Models\NotaCredito;
use App\Models\OrdenTraslado;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getMetrics(string $fechaInicio, string $fechaFin): array
    {
        $cacheKey = "dashboard_metrics_{$fechaInicio}_{$fechaFin}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($fechaInicio, $fechaFin) {
            $totalVentas = Venta::where('estado', 'COMPLETADA')
                ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin . ' 23:59:59'])
                ->sum('total');

            $totalVentasHoy = Venta::where('estado', 'COMPLETADA')
                ->whereDate('fecha_emision', today())
                ->sum('total');

            $cantidadVentas = Venta::where('estado', 'COMPLETADA')
                ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin . ' 23:59:59'])
                ->count();

            $cantidadVentasHoy = Venta::where('estado', 'COMPLETADA')
                ->whereDate('fecha_emision', today())
                ->count();

            $totalCompras = Compra::where('estado', 'REGISTRADA')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin . ' 23:59:59'])
                ->sum('total');

            $totalComprasHoy = Compra::where('estado', 'REGISTRADA')
                ->whereDate('created_at', today())
                ->sum('total');

            $totalGastos = Gasto::whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                ->sum('monto');

            $totalGastosHoy = Gasto::whereDate('fecha_emision', today())
                ->sum('monto');

            $beneficioNeto = $totalVentas - $totalCompras - $totalGastos;
            $beneficioNetoHoy = $totalVentasHoy - $totalComprasHoy - $totalGastosHoy;

            // La tarjeta solo muestra el numero, asi que se cuenta en la base
            // en vez de traer una fila por producto para contarlas en PHP.
            //
            // Ademas se agrega la guarda `stock_minimo > 0`, que las alertas ya
            // tenian y esta consulta no: sin ella, todo producto recien
            // importado (stock 0 y minimo 0) cumplia `0 <= 0` y la tarjeta
            // reportaba el catalogo entero como bajo stock mientras el panel de
            // alertas, correctamente, no mostraba ninguno.
            $productosBajoStock = DB::query()
                ->fromSub($this->productosBajoMinimo(), 'x')
                ->count();

            $ventasMesAnterior = Venta::where('estado', 'COMPLETADA')
                ->whereBetween('fecha_emision', [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month')) . ' 23:59:59'])
                ->sum('total');

            $ventasMesActual = Venta::where('estado', 'COMPLETADA')
                ->whereBetween('fecha_emision', [date('Y-m-01'), now()])
                ->sum('total');

            $porcentajeCambio = $ventasMesAnterior > 0
                ? (($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100
                : 0;

            $totalProductos = Producto::count();
            $totalClientes = Cliente::count();
            $totalProveedores = Proveedor::count();
            $totalUsuarios = User::count();

            return [
                'totalVentas' => $totalVentas,
                'totalVentasHoy' => $totalVentasHoy,
                'cantidadVentas' => $cantidadVentas,
                'cantidadVentasHoy' => $cantidadVentasHoy,
                'totalCompras' => $totalCompras,
                'totalComprasHoy' => $totalComprasHoy,
                'totalGastos' => $totalGastos,
                'totalGastosHoy' => $totalGastosHoy,
                'beneficioNeto' => $beneficioNeto,
                'beneficioNetoHoy' => $beneficioNetoHoy,
                'productosBajoStock' => $productosBajoStock,
                'totalProductos' => $totalProductos,
                'totalClientes' => $totalClientes,
                'totalProveedores' => $totalProveedores,
                'totalUsuarios' => $totalUsuarios,
                'porcentajeCambio' => $porcentajeCambio,
            ];
        });
    }

    public function getCajaAbierta()
    {
        return AperturaCaja::where('estado', 'ABIERTA')
            ->where('responsable_id', auth()->id())
            ->first();
    }

    public function getVentasMensuales(): array
    {
        $ventasPorMes = Venta::where('estado', 'COMPLETADA')
            ->whereYear('fecha_emision', '>=', date('Y') - 1)
            ->select(
                DB::raw('DATE_FORMAT(fecha_emision, "%Y-%m") as mes'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();

        $meses = [];
        $montos = [];
        foreach ($ventasPorMes as $item) {
            $meses[] = $item->mes;
            $montos[] = (float) $item->total;
        }

        return ['meses' => $meses, 'montosVentas' => $montos];
    }

    public function getComprasMensuales(): array
    {
        $comprasPorMes = Compra::where('estado', 'REGISTRADA')
            ->whereYear('created_at', '>=', date('Y') - 1)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();

        $meses = [];
        $montos = [];
        foreach ($comprasPorMes as $item) {
            $meses[] = $item->mes;
            $montos[] = (float) $item->total;
        }

        return ['meses' => $meses, 'montosCompras' => $montos];
    }

    public function getProductosMasVendidos()
    {
        return DB::table('venta_detalles')
            ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
            ->select(
                'productos.id',
                'productos.descripcion',
                'productos.codigo_interno',
                'productos.foto',
                DB::raw('SUM(venta_detalles.cantidad) as total_vendido'),
                DB::raw('SUM(venta_detalles.total) as total_monto')
            )
            ->groupBy('productos.id', 'productos.descripcion', 'productos.codigo_interno', 'productos.foto')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();
    }

    public function getUltimasVentas()
    {
        return Venta::with('cliente')
            ->where('estado', 'COMPLETADA')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
    }

    public function getUltimosGastos()
    {
        return Gasto::orderBy('id', 'desc')
            ->limit(5)
            ->get();
    }

    public function getVentasPorDia()
    {
        return Venta::where('estado', 'COMPLETADA')
            ->whereDate('fecha_emision', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(fecha_emision) as dia'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('dia')
            ->orderBy('dia', 'asc')
            ->get();
    }

    public function getEmpresa()
    {
        $empresa = Empresa::where('estado', 1)->first();
        return $empresa ?: Empresa::first();
    }

    // Alertas

    /** Cuantas alertas entrega el dashboard sin pedir "ver todas". */
    public const ALERTAS_POR_DEFECTO = 20;

    /**
     * Prioridad de cada tipo de alerta: 1 es lo que impide operar y 6 lo
     * meramente informativo. El orden sale de que tan bloqueante es el
     * problema, no de su color.
     */
    private const PRIORIDADES = [
        'caja' => 1,            // sin caja abierta no se puede vender
        'stock_agotado' => 2,   // producto operativo en cero
        'sin_precio' => 3,      // esta en catalogo pero el POS no lo puede cobrar
        'stock' => 4,           // se esta quedando sin mercaderia
        'cotizacion' => 5,
        'traslado' => 6,
    ];

    /**
     * Alertas del dashboard, ordenadas por prioridad y acotadas a $limite.
     *
     * Devuelve el total ademas de los items porque el panel muestra "Alertas
     * del sistema (137)" aunque solo pinte las primeras 20: sin el total, el
     * contador mentiria en cuanto hubiera mas alertas que el limite.
     *
     * Las alertas de catalogo (stock y precio) se agrupan en una sola entrada
     * con su cantidad. Antes salia una alerta por producto, y con un catalogo
     * de cientos de articulos el panel crecia hasta empujar el resto del
     * dashboard fuera de la pantalla.
     *
     * @return array{items:list<array<string,mixed>>,total:int,limite:int}
     */
    public function getAlertas(int $limite = self::ALERTAS_POR_DEFECTO): array
    {
        $alertas = array_merge(
            $this->alertasDeCaja(),
            $this->alertasDeCatalogo(),
            $this->alertasDePendientes(),
        );

        // Orden estable: primero la prioridad, y a igual prioridad se respeta
        // el orden en que se generaron.
        usort($alertas, fn (array $a, array $b) => $a['prioridad'] <=> $b['prioridad']);

        return [
            'items' => array_slice($alertas, 0, max(1, $limite)),
            'total' => count($alertas),
            'limite' => $limite,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function alertasDeCaja(): array
    {
        if ($this->getCajaAbierta()) {
            return [];
        }

        return [$this->alerta(
            tipo: 'caja',
            mensaje: 'No tienes una caja abierta. Abre una para registrar ventas.',
            icono: 'point_of_sale',
            color: 'warning',
            ruta: route('apertura-caja.index'),
        )];
    }

    /**
     * Stock y precio del catalogo, siempre agrupados por categoria.
     *
     * @return list<array<string,mixed>>
     */
    private function alertasDeCatalogo(): array
    {
        $alertas = [];
        $stock = $this->conteoDeStock();

        if ($stock->agotados > 0) {
            $alertas[] = $this->alerta(
                tipo: 'stock_agotado',
                mensaje: $stock->agotados === 1
                    ? 'Producto agotado: '.$this->nombreDeProducto('agotado')
                    : "{$stock->agotados} productos operativos se quedaron sin stock.",
                icono: 'production_quantity_limits',
                color: 'danger',
                ruta: route('productos.index'),
                cantidad: $stock->agotados,
                accion: 'Ver productos',
            );
        }

        if ($stock->bajos > 0) {
            $alertas[] = $this->alerta(
                tipo: 'stock',
                mensaje: $stock->bajos === 1
                    ? 'Stock bajo: '.$this->nombreDeProducto('bajo')
                    : "{$stock->bajos} productos por debajo de su stock mínimo.",
                icono: 'inventory_2',
                color: 'danger',
                ruta: route('productos.index'),
                cantidad: $stock->bajos,
                accion: 'Ver productos',
            );
        }

        $sinPrecio = $this->contarProductosSinPrecio();

        if ($sinPrecio > 0) {
            $alertas[] = $this->alerta(
                tipo: 'sin_precio',
                mensaje: $sinPrecio === 1
                    ? 'Hay 1 producto sin precio de venta: el POS no puede cobrarlo.'
                    : "{$sinPrecio} productos todavía no tienen precio de venta.",
                icono: 'sell',
                color: 'warning',
                ruta: route('productos.index'),
                cantidad: $sinPrecio,
                accion: 'Ver productos',
            );
        }

        return $alertas;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function alertasDePendientes(): array
    {
        $alertas = [];

        $cotizaciones = Cotizacion::where('estado', 'PENDIENTE')->count();

        if ($cotizaciones > 0) {
            $alertas[] = $this->alerta(
                tipo: 'cotizacion',
                mensaje: "Tienes {$cotizaciones} cotización(es) pendiente(s) por revisar.",
                icono: 'description',
                color: 'info',
                ruta: route('cotizaciones.index'),
                cantidad: $cotizaciones,
                accion: 'Ver cotizaciones',
            );
        }

        $traslados = OrdenTraslado::where('estado', 'PENDIENTE')->count();

        if ($traslados > 0) {
            $alertas[] = $this->alerta(
                tipo: 'traslado',
                mensaje: "Hay {$traslados} orden(es) de traslado pendiente(s) de aprobación.",
                icono: 'local_shipping',
                color: 'info',
                ruta: route('traslados.index'),
                cantidad: $traslados,
                accion: 'Ver traslados',
            );
        }

        return $alertas;
    }

    /**
     * Agotados y bajos en UNA sola pasada.
     *
     * Antes se traian todas las filas de productos bajo minimo para despues
     * contarlas en PHP (y getMetrics repetia la misma consulta). Con un
     * catalogo grande eso son cientos de modelos hidratados para terminar
     * haciendo un count.
     */
    private function conteoDeStock(): object
    {
        $conteo = DB::query()
            ->fromSub($this->productosBajoMinimo(), 'x')
            ->selectRaw('SUM(CASE WHEN stock_total <= 0 THEN 1 ELSE 0 END) as agotados')
            ->selectRaw('SUM(CASE WHEN stock_total > 0 THEN 1 ELSE 0 END) as bajos')
            ->first();

        return (object) [
            'agotados' => (int) ($conteo->agotados ?? 0),
            'bajos' => (int) ($conteo->bajos ?? 0),
        ];
    }

    /**
     * Productos operativos en o por debajo de su minimo.
     *
     * La guarda `stock_minimo > 0` ya existia en las alertas y es la que separa
     * el catalogo recien importado del producto que se maneja de verdad: el
     * importador deja stock_minimo en 0 cuando el CSV no lo trae, asi que un
     * articulo que nunca recibio mercaderia no alerta hasta que alguien le
     * define un minimo. Se conserva tal cual.
     */
    private function productosBajoMinimo(): \Illuminate\Database\Query\Builder
    {
        return DB::table('productos as p')
            ->leftJoin('producto_almacen as pa', 'pa.producto_id', '=', 'p.id')
            ->where('p.estado', 1)
            ->where('p.stock_minimo', '>', 0)
            ->groupBy('p.id', 'p.stock_minimo')
            ->havingRaw('COALESCE(SUM(pa.stock), 0) <= p.stock_minimo')
            ->select('p.id')
            ->selectRaw('COALESCE(SUM(pa.stock), 0) as stock_total');
    }

    /** Cuantos productos activos no se pueden cobrar por no tener precio. */
    private function contarProductosSinPrecio(): int
    {
        return DB::table('productos')
            ->where('estado', 1)
            ->where('precio_venta', '<=', 0)
            ->count();
    }

    /**
     * Nombre del unico producto en problemas. Solo se consulta cuando hay
     * exactamente uno: con dos o mas la alerta va agrupada y el nombre sobra.
     */
    private function nombreDeProducto(string $caso): string
    {
        $fila = DB::query()
            ->fromSub($this->productosBajoMinimo(), 'x')
            ->join('productos as p', 'p.id', '=', 'x.id')
            ->when($caso === 'agotado',
                fn ($q) => $q->where('x.stock_total', '<=', 0),
                fn ($q) => $q->where('x.stock_total', '>', 0),
            )
            ->select('p.descripcion', 'p.stock_minimo', 'x.stock_total')
            ->first();

        if (! $fila) {
            return 'producto sin identificar';
        }

        return "{$fila->descripcion} ({$fila->stock_total}/{$fila->stock_minimo})";
    }

    /**
     * @return array<string,mixed>
     */
    private function alerta(
        string $tipo,
        string $mensaje,
        string $icono,
        string $color,
        string $ruta,
        ?int $cantidad = null,
        ?string $accion = null,
    ): array {
        return [
            'tipo' => $tipo,
            'prioridad' => self::PRIORIDADES[$tipo] ?? 99,
            'mensaje' => $mensaje,
            'icono' => $icono,
            'color' => $color,
            'ruta' => $ruta,
            'cantidad' => $cantidad,
            'accion' => $accion,
        ];
    }

    public function getVentasPorTipoComprobante()
    {
        return Venta::where('estado', 'COMPLETADA')
            ->whereYear('fecha_emision', date('Y'))
            ->select('tipo_comprobante', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('tipo_comprobante')
            ->get();
    }
}
