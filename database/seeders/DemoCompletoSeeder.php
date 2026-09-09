<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Deja el sistema con datos de demostracion en TODOS los modulos, coherentes
 * entre si, para poder mostrarlo o tomarle capturas.
 *
 *     php artisan db:seed --class=DemoCompletoSeeder
 *
 * El orden no es negociable: los precios tienen que estar antes que los
 * movimientos (una venta sobre un producto en S/ 0.00 sale en cero) y los
 * movimientos antes que los documentos derivados (una nota de credito necesita
 * la venta que corrige).
 */
class DemoCompletoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EmpresaSeeder::class,
            AlmacenCajaSeeder::class,
            TaxonomiaSeeder::class,
            UserSeeder::class,
            CatalogoSeeder::class,
            DemoPreciosCatalogoSeeder::class,
            MovimientoSeeder::class,
            DemoOperativoSeeder::class,
        ]);
    }
}
