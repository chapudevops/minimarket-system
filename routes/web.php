<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Empresa\EmpresaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
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
    
    // Tu ruta comodín - DEBE IR AL FINAL
    // Route::get('{routeName}/{name?}', [HomeController::class, 'pageView']);
});



