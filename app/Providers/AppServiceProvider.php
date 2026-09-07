<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // @rol('Vendedor', 'Almacenero') ... @endrol
        // Comparte la regla con el middleware 'role', asi el menu no ofrece
        // secciones que la ruta va a rechazar con 403.
        Blade::if('rol', function (...$roles) {
            return auth()->check() && auth()->user()->puedeConRol($roles);
        });
    }
}
