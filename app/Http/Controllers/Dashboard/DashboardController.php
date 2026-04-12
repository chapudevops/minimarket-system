<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Producto;
use App\Models\ProductoAlmacen;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Caja;
use App\Models\User;
use App\Models\AperturaCaja;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Fechas para filtros
        $fechaInicio = request()->get('fecha_inicio', date('Y-m-01'));
        $fechaFin = request()->get('fecha_fin', date('Y-m-d'));
        
        // Totales de ventas
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
        
        // Totales de compras
        $totalCompras = Compra::where('estado', 'REGISTRADA')
                              ->whereBetween('created_at', [$fechaInicio, $fechaFin . ' 23:59:59'])
                              ->sum('total');
        
        $totalComprasHoy = Compra::where('estado', 'REGISTRADA')
                                 ->whereDate('created_at', today())
                                 ->sum('total');
        
        // Totales de gastos
        $totalGastos = Gasto::whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                            ->sum('monto');
        
        $totalGastosHoy = Gasto::whereDate('fecha_emision', today())
                               ->sum('monto');
        
        // Beneficio neto (Ventas - Compras - Gastos)
        $beneficioNeto = $totalVentas - $totalCompras - $totalGastos;
        $beneficioNetoHoy = $totalVentasHoy - $totalComprasHoy - $totalGastosHoy;
        
        // Productos - Usando producto_almacen para calcular stock total
        $totalProductos = Producto::count();
        
        // Calcular productos con bajo stock (stock_total <= stock_minimo)
        // Agrupar por producto_id y sumar stock de todos los almacenes
        $productosBajoStock = DB::table('productos')
            ->leftJoin('producto_almacen', 'productos.id', '=', 'producto_almacen.producto_id')
            ->select('productos.id', 'productos.stock_minimo', DB::raw('COALESCE(SUM(producto_almacen.stock), 0) as stock_total'))
            ->groupBy('productos.id', 'productos.stock_minimo')
            ->havingRaw('COALESCE(SUM(producto_almacen.stock), 0) <= productos.stock_minimo')
            ->count();
        
        // Clientes y proveedores
        $totalClientes = Cliente::count();
        $totalProveedores = Proveedor::count();
        
        // Usuarios y cajas
        $totalUsuarios = User::count();
        $cajaAbierta = AperturaCaja::where('estado', 'ABIERTA')
                                   ->where('responsable_id', auth()->id())
                                   ->first();
        
        // Ventas por mes (últimos 12 meses)
        $ventasPorMes = Venta::where('estado', 'COMPLETADA')
                             ->whereYear('fecha_emision', '>=', date('Y') - 1)
                             ->select(
                                 DB::raw('DATE_FORMAT(fecha_emision, "%Y-%m") as mes'),
                                 DB::raw('SUM(total) as total')
                             )
                             ->groupBy('mes')
                             ->orderBy('mes', 'asc')
                             ->get();
        
        // Productos más vendidos
        $productosMasVendidos = DB::table('venta_detalles')
                                 ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                                 ->select(
                                     'productos.id',
                                     'productos.descripcion',
                                     'productos.codigo_interno',
                                     DB::raw('SUM(venta_detalles.cantidad) as total_vendido')
                                 )
                                 ->groupBy('productos.id', 'productos.descripcion', 'productos.codigo_interno')
                                 ->orderBy('total_vendido', 'desc')
                                 ->limit(5)
                                 ->get();
        
        // Últimas ventas
        $ultimasVentas = Venta::with('cliente')
                              ->where('estado', 'COMPLETADA')
                              ->orderBy('id', 'desc')
                              ->limit(5)
                              ->get();
        
        // Últimos gastos
        $ultimosGastos = Gasto::orderBy('id', 'desc')
                              ->limit(5)
                              ->get();
        
        // Datos para gráficos
        $meses = [];
        $montosVentas = [];
        foreach ($ventasPorMes as $item) {
            $meses[] = $item->mes;
            $montosVentas[] = $item->total;
        }
        
        // Porcentaje de cambio
        $ventasMesAnterior = Venta::where('estado', 'COMPLETADA')
                                  ->whereBetween('fecha_emision', [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month')) . ' 23:59:59'])
                                  ->sum('total');
        
        $ventasMesActual = Venta::where('estado', 'COMPLETADA')
                                ->whereBetween('fecha_emision', [date('Y-m-01'), now()])
                                ->sum('total');
        
        $porcentajeCambio = $ventasMesAnterior > 0 
                            ? (($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100 
                            : 0;
        
        $data = [
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
            'totalProductos' => $totalProductos,
            'productosBajoStock' => $productosBajoStock,
            'totalClientes' => $totalClientes,
            'totalProveedores' => $totalProveedores,
            'totalUsuarios' => $totalUsuarios,
            'cajaAbierta' => $cajaAbierta,
            'meses' => $meses,
            'montosVentas' => $montosVentas,
            'productosMasVendidos' => $productosMasVendidos,
            'ultimasVentas' => $ultimasVentas,
            'ultimosGastos' => $ultimosGastos,
            'porcentajeCambio' => $porcentajeCambio,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ];
        
        return view('dashboard.index', $data);
    }
    
    public function home()
    {
        return $this->index();
    }
}