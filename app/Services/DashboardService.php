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

            $productosBajoStock = DB::table('productos')
                ->leftJoin('producto_almacen', 'productos.id', '=', 'producto_almacen.producto_id')
                ->select('productos.id', 'productos.descripcion', 'productos.codigo_interno', 'productos.stock_minimo', DB::raw('COALESCE(SUM(producto_almacen.stock), 0) as stock_total'))
                ->groupBy('productos.id', 'productos.descripcion', 'productos.codigo_interno', 'productos.stock_minimo')
                ->havingRaw('COALESCE(SUM(producto_almacen.stock), 0) <= productos.stock_minimo')
                ->get();

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
    public function getAlertas(): array
    {
        $alertas = [];

        $productosBajoStock = DB::table('productos')
            ->leftJoin('producto_almacen', 'productos.id', '=', 'producto_almacen.producto_id')
            ->select('productos.id', 'productos.descripcion', 'productos.codigo_interno', 'productos.stock_minimo', DB::raw('COALESCE(SUM(producto_almacen.stock), 0) as stock_total'))
            ->groupBy('productos.id', 'productos.descripcion', 'productos.codigo_interno', 'productos.stock_minimo')
            ->havingRaw('COALESCE(SUM(producto_almacen.stock), 0) <= productos.stock_minimo AND productos.stock_minimo > 0')
            ->get();

        foreach ($productosBajoStock as $p) {
            $alertas[] = [
                'tipo' => 'stock',
                'mensaje' => "Stock bajo: {$p->descripcion} ({$p->stock_total}/{$p->stock_minimo})",
                'icono' => 'inventory_2',
                'color' => 'danger',
                'ruta' => route('productos.index'),
            ];
        }

        $cajaAbierta = $this->getCajaAbierta();
        if (!$cajaAbierta) {
            $alertas[] = [
                'tipo' => 'caja',
                'mensaje' => 'No tienes una caja abierta. Abre una para registrar ventas.',
                'icono' => 'point_of_sale',
                'color' => 'warning',
                'ruta' => route('apertura-caja.index'),
            ];
        }

        $cotizacionesPendientes = Cotizacion::where('estado', 'PENDIENTE')->count();
        if ($cotizacionesPendientes > 0) {
            $alertas[] = [
                'tipo' => 'cotizacion',
                'mensaje' => "Tienes {$cotizacionesPendientes} cotización(es) pendiente(s) por revisar.",
                'icono' => 'description',
                'color' => 'info',
                'ruta' => route('cotizaciones.index'),
            ];
        }

        $trasladosPendientes = OrdenTraslado::where('estado', 'PENDIENTE')->count();
        if ($trasladosPendientes > 0) {
            $alertas[] = [
                'tipo' => 'traslado',
                'mensaje' => "Hay {$trasladosPendientes} orden(es) de traslado pendiente(s) de aprobación.",
                'icono' => 'local_shipping',
                'color' => 'info',
                'ruta' => route('traslados.index'),
            ];
        }

        return $alertas;
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
