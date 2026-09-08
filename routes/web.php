<?php

use App\Http\Controllers\Almacen\AlmacenController;
use App\Http\Controllers\Auditoria\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Caja\AperturaCajaController;
use App\Http\Controllers\Caja\CajaController;
use App\Http\Controllers\Cliente\ClienteController;
use App\Http\Controllers\Compra\CompraController;
use App\Http\Controllers\Cotizacion\CotizacionController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Empresa\EmpresaController;
use App\Http\Controllers\Gasto\GastoController;
use App\Http\Controllers\GuiaRemision\GuiaRemisionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\NotaCredito\NotaCreditoController;
use App\Http\Controllers\NotaDebito\NotaDebitoController;
use App\Http\Controllers\NotaVenta\NotaVentaController;
use App\Http\Controllers\OrdenTraslado\OrdenTrasladoController;
use App\Http\Controllers\Producto\ProductoController;
use App\Http\Controllers\Proveedor\ProveedorController;
use App\Http\Controllers\Serie\SerieController;
use App\Http\Controllers\Terminal\TerminalController;
use App\Http\Controllers\Usuario\UsuarioController;
use App\Http\Controllers\Combo\ComboController;
use App\Http\Controllers\Venta\VentaController;
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
Auth::routes(['register' => false]);
// Rutas de autenticación explícitas
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');
// Rutas protegidas con middleware auth
Route::middleware(['auth'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    // Ruta para el dashboard, solo accesible para usuarios autenticados
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/store-location', [DashboardController::class, 'getStoreLocation'])->name('store.location');
    // Rutas para Auditoría
    // Solo Administrador: la bitacora dice quien hizo que, y quien la lee no
    // deberia poder ser cualquiera.
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/auditoria/data', [AuditoriaController::class, 'getData'])->name('auditoria.data');
    });

    // Rutas para la configuración de la empresa
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresa.index');
        Route::post('/empresa/{id}', [EmpresaController::class, 'update'])->name('empresa.update');
    });
    // Rutas para Cajas
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/cajas', [CajaController::class, 'index'])->name('cajas.index');
        Route::get('/cajas/data', [CajaController::class, 'getData'])->name('cajas.data');
        Route::post('/cajas', [CajaController::class, 'store'])->name('cajas.store');
        Route::put('/cajas/{id}', [CajaController::class, 'update'])->name('cajas.update');
        Route::delete('/cajas/{id}', [CajaController::class, 'destroy'])->name('cajas.destroy');
    });
    // Rutas para Clientes
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/data', [ClienteController::class, 'getData'])->name('clientes.data');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    });
    // Rutas para Proveedores
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('/proveedores/data', [ProveedorController::class, 'getData'])->name('proveedores.data');
        Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::put('/proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
    });
    // Rutas para Productos
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/productos/almacenes', [ProductoController::class, 'getAlmacenes'])->name('productos.almacenes'); // Esta debe ir PRIMERO
        Route::get('/productos/data', [ProductoController::class, 'getData'])->name('productos.data');
        Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/productos/{id}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::get('/productos/{id}', [ProductoController::class, 'show'])->name('productos.show');
        Route::put('/productos/{id}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    });
    // Rutas para Almacenes
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/almacenes', [AlmacenController::class, 'index'])->name('almacenes.index');
        Route::get('/almacenes/data', [AlmacenController::class, 'getData'])->name('almacenes.data');
        Route::get('/almacenes/{id}', [AlmacenController::class, 'show'])->name('almacenes.show');
        Route::post('/almacenes', [AlmacenController::class, 'store'])->name('almacenes.store');
        Route::put('/almacenes/{id}', [AlmacenController::class, 'update'])->name('almacenes.update');
        Route::delete('/almacenes/{id}', [AlmacenController::class, 'destroy'])->name('almacenes.destroy');
    });
   // Rutas para Combos
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/combos', [ComboController::class, 'index'])->name('combos.index');
        Route::get('/combos/create', [ComboController::class, 'create'])->name('combos.create');
        Route::get('/combos/data', [ComboController::class, 'getData'])->name('combos.data');
        Route::get('/combos/search-productos', [ComboController::class, 'searchProductos'])->name('combos.search.productos');
        Route::post('/combos', [ComboController::class, 'store'])->name('combos.store');
        Route::get('/combos/{id}', [ComboController::class, 'show'])->name('combos.show');
        Route::get('/combos/{id}/edit', [ComboController::class, 'edit'])->name('combos.edit');
        Route::put('/combos/{id}', [ComboController::class, 'update'])->name('combos.update');
        Route::delete('/combos/{id}', [ComboController::class, 'destroy'])->name('combos.destroy');
        Route::get('/combos-terminal', [ComboController::class, 'getCombosTerminal'])->name('combos.terminal');
        Route::get('/store', [StoreController::class, 'index'])->name('store.index');
        Route::post('/store/cart/add', [StoreController::class, 'addToCart'])->name('store.cart.add');
        Route::get('/store/cart', [StoreController::class, 'cart'])->name('store.cart');
        Route::post('/store/checkout', [StoreController::class, 'checkout'])->name('store.checkout');
    });
   // Rutas para Terminal POS
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/terminal', [TerminalController::class, 'index'])->name('terminal.index');
        Route::get('/terminal/search', [TerminalController::class, 'search'])->name('terminal.search');
        Route::get('/terminal/stock', [TerminalController::class, 'getStock'])->name('terminal.stock');
        Route::get('/terminal/search-clientes', [TerminalController::class, 'searchClientes'])->name('terminal.search.clientes');
        Route::get('/terminal/series', [TerminalController::class, 'getSeries'])->name('terminal.series');
        Route::post('/terminal/procesar-pago', [TerminalController::class, 'procesarPago'])->name('terminal.procesar.pago');
        Route::get('/terminal/venta/{id}', [TerminalController::class, 'getVenta'])->name('terminal.venta');
        Route::get('/terminal/productos', [TerminalController::class, 'getProductos'])->name('terminal.productos');
    });
    // Rutas para Órdenes de Traslado
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/traslados', [OrdenTrasladoController::class, 'index'])->name('traslados.index');
        Route::get('/traslados/create', [OrdenTrasladoController::class, 'create'])->name('traslados.create');
        Route::post('/traslados', [OrdenTrasladoController::class, 'store'])->name('traslados.store');
        Route::get('/traslados/{id}', [OrdenTrasladoController::class, 'show'])->name('traslados.show');
        Route::post('/traslados/{id}/aprobar', [OrdenTrasladoController::class, 'aprobar'])->name('traslados.aprobar');
        Route::post('/traslados/{id}/anular', [OrdenTrasladoController::class, 'anular'])->name('traslados.anular');
        Route::get('/traslados/search/productos', [OrdenTrasladoController::class, 'searchProductos'])->name('traslados.search.productos');
        Route::get('/traslados/stock/producto', [OrdenTrasladoController::class, 'getStockProducto'])->name('traslados.stock.producto');
    });
    // Rutas para Usuarios
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/data', [UsuarioController::class, 'getData'])->name('usuarios.data');
        Route::get('/usuarios/form-data', [UsuarioController::class, 'getFormData'])->name('usuarios.form-data');
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::get('/usuarios/{id}/edit', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        Route::post('/usuarios/{id}/toggle-status', [UsuarioController::class, 'toggleStatus'])->name('usuarios.toggle-status');
    });
    // Rutas para Compras 
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
        Route::get('/compras/create', [CompraController::class, 'create'])->name('compras.create');
        Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
        Route::get('/compras/{id}', [CompraController::class, 'show'])->name('compras.show');
        Route::get('/compras/{id}/pdf', [CompraController::class, 'generarPdf'])->name('compras.pdf');
        Route::get('/compras/{id}/imprimir', [CompraController::class, 'imprimir'])->name('compras.imprimir');
        Route::post('/compras/{id}/anular', [CompraController::class, 'anular'])->name('compras.anular');
        Route::get('/compras/search/productos', [CompraController::class, 'searchProductos'])->name('compras.search.productos');
    });
    // Rutas para Series
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/series', [SerieController::class, 'index'])->name('series.index');
        Route::get('/series/data', [SerieController::class, 'getData'])->name('series.data');
        Route::get('/series/form-data', [SerieController::class, 'getFormData'])->name('series.form-data');
        Route::post('/series', [SerieController::class, 'store'])->name('series.store');
        Route::put('/series/{id}', [SerieController::class, 'update'])->name('series.update');
        Route::delete('/series/{id}', [SerieController::class, 'destroy'])->name('series.destroy');
    });
    // Rutas para Gastos
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/gastos/data', [GastoController::class, 'getData'])->name('gastos.data');
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
        Route::delete('/gastos/{id}', [GastoController::class, 'destroy'])->name('gastos.destroy');
    });
    // Rutas para Apertura de Cajas
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/apertura-caja', [AperturaCajaController::class, 'index'])->name('apertura-caja.index');
        Route::get('/apertura-caja/data', [AperturaCajaController::class, 'getData'])->name('apertura-caja.data');
        Route::get('/apertura-caja/verificar', [AperturaCajaController::class, 'verificarCajaAbierta'])->name('apertura-caja.verificar');
        Route::post('/apertura-caja', [AperturaCajaController::class, 'store'])->name('apertura-caja.store');
        Route::post('/apertura-caja/{id}/cerrar', [AperturaCajaController::class, 'cerrar'])->name('apertura-caja.cerrar');
        Route::get('/apertura-caja/{id}/detalle', [AperturaCajaController::class, 'getDetalle'])->name('apertura-caja.detalle');
        Route::get('/apertura-caja/{id}/resumen', [AperturaCajaController::class, 'getResumen'])->name('apertura-caja.resumen');
        Route::get('/apertura-caja/{id}/reporte', [AperturaCajaController::class, 'generarReporte'])->name('apertura-caja.reporte');
        Route::get('/apertura-caja/{id}/excel', [AperturaCajaController::class, 'exportarExcel'])->name('apertura-caja.excel');   
    });
    // Rutas para Ventas
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/data', [VentaController::class, 'getData'])->name('ventas.data');
        Route::get('/ventas/{id}', [VentaController::class, 'show'])->name('ventas.show');
        Route::get('/ventas/{id}/pdf', [VentaController::class, 'generarPdf'])->name('ventas.pdf');
        Route::get('/ventas/{id}/ticket', [VentaController::class, 'imprimirTicket'])->name('ventas.ticket');
        Route::post('/ventas/{id}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
    });
   // Rutas para Notas de Crédito
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/notas-credito', [NotaCreditoController::class, 'index'])->name('notas-credito.index');
        Route::get('/notas-credito/data', [NotaCreditoController::class, 'getData'])->name('notas-credito.data');
        Route::get('/notas-credito/create', [NotaCreditoController::class, 'create'])->name('notas-credito.create');
        Route::get('/notas-credito/serie', [NotaCreditoController::class, 'getSerie'])->name('notas-credito.serie');
        Route::get('/notas-credito/venta/{id}', [NotaCreditoController::class, 'getVenta'])->name('notas-credito.venta');
        Route::post('/notas-credito', [NotaCreditoController::class, 'store'])->name('notas-credito.store');
        Route::get('/notas-credito/{id}', [NotaCreditoController::class, 'show'])->name('notas-credito.show');
        Route::get('/notas-credito/{id}/pdf', [NotaCreditoController::class, 'generarPdf'])->name('notas-credito.pdf');
    });
    // Rutas para Notas de Débito
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/notas-debito', [NotaDebitoController::class, 'index'])->name('notas-debito.index');
        Route::get('/notas-debito/data', [NotaDebitoController::class, 'getData'])->name('notas-debito.data');
        Route::get('/notas-debito/create', [NotaDebitoController::class, 'create'])->name('notas-debito.create');
        Route::get('/notas-debito/serie', [NotaDebitoController::class, 'getSerie'])->name('notas-debito.serie');
        Route::get('/notas-debito/venta/{id}', [NotaDebitoController::class, 'getVenta'])->name('notas-debito.venta');
        Route::post('/notas-debito', [NotaDebitoController::class, 'store'])->name('notas-debito.store');
        Route::get('/notas-debito/{id}', [NotaDebitoController::class, 'show'])->name('notas-debito.show');
        Route::get('/notas-debito/{id}/pdf', [NotaDebitoController::class, 'generarPdf'])->name('notas-debito.pdf');
    });
    // Rutas para Notas de Venta
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/notas-venta', [NotaVentaController::class, 'index'])->name('notas-venta.index');
        Route::get('/notas-venta/data', [NotaVentaController::class, 'getData'])->name('notas-venta.data');
        Route::get('/notas-venta/create', [NotaVentaController::class, 'create'])->name('notas-venta.create');
        Route::get('/notas-venta/serie', [NotaVentaController::class, 'getSerie'])->name('notas-venta.serie');
        Route::get('/notas-venta/search/productos', [NotaVentaController::class, 'searchProductos'])->name('notas-venta.search.productos');
        Route::get('/notas-venta/stock', [NotaVentaController::class, 'getStock'])->name('notas-venta.stock');
        Route::post('/notas-venta', [NotaVentaController::class, 'store'])->name('notas-venta.store');
        Route::get('/notas-venta/{id}', [NotaVentaController::class, 'show'])->name('notas-venta.show');
        Route::get('/notas-venta/{id}/pdf', [NotaVentaController::class, 'generarPdf'])->name('notas-venta.pdf');
    });
    // Rutas para Cotizaciones
    Route::middleware('role:Administrador,Vendedor')->group(function () {
        Route::get('/cotizaciones', [CotizacionController::class, 'index'])->name('cotizaciones.index');
        Route::get('/cotizaciones/data', [CotizacionController::class, 'getData'])->name('cotizaciones.data');
        Route::get('/cotizaciones/create', [CotizacionController::class, 'create'])->name('cotizaciones.create');
        Route::get('/cotizaciones/serie', [CotizacionController::class, 'getSerie'])->name('cotizaciones.serie');
        Route::get('/cotizaciones/search/productos', [CotizacionController::class, 'searchProductos'])->name('cotizaciones.search.productos');
        Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
        Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show'])->name('cotizaciones.show');
        Route::get('/cotizaciones/{id}/pdf', [CotizacionController::class, 'generarPdf'])->name('cotizaciones.pdf');
        Route::post('/cotizaciones/{id}/aprobar', [CotizacionController::class, 'aprobar'])->name('cotizaciones.aprobar');
        Route::post('/cotizaciones/{id}/rechazar', [CotizacionController::class, 'rechazar'])->name('cotizaciones.rechazar');
    });
    // Rutas para Guías de Remisión
    Route::middleware('role:Administrador,Almacenero')->group(function () {
        Route::get('/guias-remision', [GuiaRemisionController::class, 'index'])->name('guias-remision.index');
        Route::get('/guias-remision/data', [GuiaRemisionController::class, 'getData'])->name('guias-remision.data');
        Route::get('/guias-remision/create', [GuiaRemisionController::class, 'create'])->name('guias-remision.create');
        Route::get('/guias-remision/serie', [GuiaRemisionController::class, 'getSerie'])->name('guias-remision.serie');
        Route::get('/guias-remision/search/productos', [GuiaRemisionController::class, 'searchProductos'])->name('guias-remision.search.productos');
        Route::post('/guias-remision', [GuiaRemisionController::class, 'store'])->name('guias-remision.store');
        Route::get('/guias-remision/{id}', [GuiaRemisionController::class, 'show'])->name('guias-remision.show');
        Route::get('/guias-remision/{id}/pdf', [GuiaRemisionController::class, 'generarPdf'])->name('guias-remision.pdf');
        Route::get('/guias-remision/conductores', [GuiaRemisionController::class, 'getConductores'])->name('guias-remision.conductores');
        Route::post('/guias-remision/conductores', [GuiaRemisionController::class, 'storeConductor'])->name('guias-remision.store.conductor');
        Route::get('/guias-remision/vehiculos', [GuiaRemisionController::class, 'getVehiculos'])->name('guias-remision.vehiculos');
        Route::post('/guias-remision/vehiculos', [GuiaRemisionController::class, 'storeVehiculo'])->name('guias-remision.store.vehiculo');
        Route::get('/guias-remision/ubigeo/search', [GuiaRemisionController::class, 'searchUbigeo'])->name('guias-remision.ubigeo');
    });
    // La ruta comodin renderizaba cualquier vista por nombre, saltandose las
    // restricciones de rol de arriba: un Vendedor con 403 en /usuarios entraba
    // igual por /usuario/index. Solo servia las paginas de demo que trae la
    // plantilla, que no enlaza nadie, asi que se elimina.
});



