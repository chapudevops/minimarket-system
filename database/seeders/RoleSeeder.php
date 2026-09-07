<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = [
                ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
                ['nombre' => 'Vendedor',      'descripcion' => 'Terminal de ventas, cotizaciones y clientes'],
                ['nombre' => 'Almacenero',    'descripcion' => 'Productos, compras, almacenes y traslados'],
            ];

            foreach ($roles as $rol) {
                DB::table('roles')->updateOrInsert(['nombre' => $rol['nombre']], $rol);
            }
        });
    }
}
