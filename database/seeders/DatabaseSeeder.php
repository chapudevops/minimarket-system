<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // El orden importa: cajas y almacenes antes de los usuarios que los
        // referencian, y el catalogo antes de los movimientos.
        $this->call([
            RoleSeeder::class,
            EmpresaSeeder::class,
            AlmacenCajaSeeder::class,
            TaxonomiaSeeder::class,
            UserSeeder::class,
            CatalogoSeeder::class,
            MovimientoSeeder::class,
        ]);
    }
}
