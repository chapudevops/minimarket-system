<?php

use App\Http\Controllers\Almacen\AlmacenController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Caja\CajaController;
use App\Http\Controllers\Cliente\ClienteController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Empresa\EmpresaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Producto\ProductoController;
use App\Http\Controllers\Proveedor\ProveedorController;
use App\Http\Controllers\Terminal\TerminalController;
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
    // Tu ruta comodín - DEBE IR AL FINAL
    Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
});



