<?php

use App\Http\Controllers\Almacen\AlmacenController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Caja\AperturaCajaController;
use App\Http\Controllers\Caja\CajaController;
use App\Http\Controllers\Cliente\ClienteController;
use App\Http\Controllers\Compra\CompraController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Empresa\EmpresaController;
use App\Http\Controllers\Gasto\GastoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrdenTraslado\OrdenTrasladoController;
use App\Http\Controllers\Producto\ProductoController;
use App\Http\Controllers\Proveedor\ProveedorController;
use App\Http\Controllers\Serie\SerieController;
use App\Http\Controllers\Terminal\TerminalController;
use App\Http\Controllers\Usuario\UsuarioController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Auth::routes();

// Rutas de autenticación explícitas (sin usar Auth::routes())
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');



// Rutas protegidas con middleware auth
Route::middleware(['auth'])->group(function () {
    // Ruta para el dashboard, solo accesible para usuarios autenticados
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    // Rutas para la configuración de la empresa
    Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresa.index');
    Route::post('/empresa/{id}', [EmpresaController::class, 'update'])->name('empresa.update');
    // Rutas para Cajas
    Route::get('/cajas', [CajaController::class, 'index'])->name('cajas.index');
    Route::get('/cajas/data', [CajaController::class, 'getData'])->name('cajas.data');
    Route::post('/cajas', [CajaController::class, 'store'])->name('cajas.store');
    Route::put('/cajas/{id}', [CajaController::class, 'update'])->name('cajas.update');
    Route::delete('/cajas/{id}', [CajaController::class, 'destroy'])->name('cajas.destroy');
    // Rutas para Clientes
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/data', [ClienteController::class, 'getData'])->name('clientes.data');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    // Rutas para Proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/data', [ProveedorController::class, 'getData'])->name('proveedores.data');
    Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::put('/proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    // Rutas para Productos
    
    
    // Rutas para Productos (el orden es importante)
    Route::get('/productos/almacenes', [ProductoController::class, 'getAlmacenes'])->name('productos.almacenes'); // Esta debe ir PRIMERO
    Route::get('/productos/data', [ProductoController::class, 'getData'])->name('productos.data');
    Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/productos/{id}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::get('/productos/{id}', [ProductoController::class, 'show'])->name('productos.show');
    Route::put('/productos/{id}', [ProductoController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    
    
    
    
    // Rutas para Almacenes
    Route::get('/almacenes', [AlmacenController::class, 'index'])->name('almacenes.index');
    Route::get('/almacenes/data', [AlmacenController::class, 'getData'])->name('almacenes.data');
    Route::get('/almacenes/{id}', [AlmacenController::class, 'show'])->name('almacenes.show');
    Route::post('/almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
    Route::put('/almacenes/{id}', [AlmacenController::class, 'update'])->name('almacenes.update');
    Route::delete('/almacenes/{id}', [AlmacenController::class, 'destroy'])->name('almacenes.destroy');
   // Rutas para Terminal POS
    Route::get('/terminal', [TerminalController::class, 'index'])->name('terminal.index');
    Route::get('/terminal/search', [TerminalController::class, 'search'])->name('terminal.search');
    Route::get('/terminal/stock', [TerminalController::class, 'getStock'])->name('terminal.stock');
    Route::get('/terminal/search-clientes', [TerminalController::class, 'searchClientes'])->name('terminal.search.clientes');
    Route::get('/terminal/series', [TerminalController::class, 'getSeries'])->name('terminal.series');
    Route::post('/terminal/procesar-pago', [TerminalController::class, 'procesarPago'])->name('terminal.procesar.pago');
    Route::get('/terminal/venta/{id}', [TerminalController::class, 'getVenta'])->name('terminal.venta');
    
    // Rutas para Órdenes de Traslado
    Route::get('/traslados', [OrdenTrasladoController::class, 'index'])->name('traslados.index');
    Route::get('/traslados/create', [OrdenTrasladoController::class, 'create'])->name('traslados.create');
    Route::post('/traslados', [OrdenTrasladoController::class, 'store'])->name('traslados.store');
    Route::get('/traslados/{id}', [OrdenTrasladoController::class, 'show'])->name('traslados.show');
    Route::post('/traslados/{id}/aprobar', [OrdenTrasladoController::class, 'aprobar'])->name('traslados.aprobar');
    Route::post('/traslados/{id}/anular', [OrdenTrasladoController::class, 'anular'])->name('traslados.anular');
    Route::get('/traslados/search/productos', [OrdenTrasladoController::class, 'searchProductos'])->name('traslados.search.productos');
    Route::get('/traslados/stock/producto', [OrdenTrasladoController::class, 'getStockProducto'])->name('traslados.stock.producto');
    // Rutas para Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/data', [UsuarioController::class, 'getData'])->name('usuarios.data');
    Route::get('/usuarios/form-data', [UsuarioController::class, 'getFormData'])->name('usuarios.form-data');
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->name('usuarios.show');
    Route::get('/usuarios/{id}/edit', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('/usuarios/{id}/toggle-status', [UsuarioController::class, 'toggleStatus'])->name('usuarios.toggle-status');
    
    
    // Rutas para Compras 
    Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
    Route::get('/compras/create', [CompraController::class, 'create'])->name('compras.create');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
    Route::get('/compras/{id}', [CompraController::class, 'show'])->name('compras.show');
    Route::get('/compras/{id}/pdf', [CompraController::class, 'generarPdf'])->name('compras.pdf');
    Route::get('/compras/{id}/imprimir', [CompraController::class, 'imprimir'])->name('compras.imprimir');
    Route::post('/compras/{id}/anular', [CompraController::class, 'anular'])->name('compras.anular');
    Route::get('/compras/search/productos', [CompraController::class, 'searchProductos'])->name('compras.search.productos');
    // Rutas para Series
    Route::get('/series', [SerieController::class, 'index'])->name('series.index');
    Route::get('/series/data', [SerieController::class, 'getData'])->name('series.data');
    Route::get('/series/form-data', [SerieController::class, 'getFormData'])->name('series.form-data');
    Route::post('/series', [SerieController::class, 'store'])->name('series.store');
    Route::put('/series/{id}', [SerieController::class, 'update'])->name('series.update');
    Route::delete('/series/{id}', [SerieController::class, 'destroy'])->name('series.destroy');
    // Rutas para Gastos
    Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
    Route::get('/gastos/data', [GastoController::class, 'getData'])->name('gastos.data');
    Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    Route::delete('/gastos/{id}', [GastoController::class, 'destroy'])->name('gastos.destroy');
    // Rutas para Apertura de Cajas
    Route::get('/apertura-caja', [AperturaCajaController::class, 'index'])->name('apertura-caja.index');
    Route::get('/apertura-caja/data', [AperturaCajaController::class, 'getData'])->name('apertura-caja.data');
    Route::get('/apertura-caja/verificar', [AperturaCajaController::class, 'verificarCajaAbierta'])->name('apertura-caja.verificar');
    Route::post('/apertura-caja', [AperturaCajaController::class, 'store'])->name('apertura-caja.store');
    Route::post('/apertura-caja/{id}/cerrar', [AperturaCajaController::class, 'cerrar'])->name('apertura-caja.cerrar');
    
    // Tu ruta comodín - DEBE IR AL FINAL
    Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
});



