<?php

namespace Database\Seeders;

use App\Catalogo\Taxonomia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga categorias y subcategorias desde diccionarios/taxonomia.csv.
 *
 * Es estructural, no demo: describe como esta organizado el catalogo, igual
 * que los almacenes o las series. Por eso entra en DatabaseSeeder y no en el
 * seeder de datos de prueba, y por eso es idempotente —volver a correrlo
 * actualiza nombres y orden sin duplicar ni romper los productos que ya
 * apuntan a una subcategoria.
 *
 * La fuente es el CSV y no un array aca adentro: el mismo archivo lo usa el
 * pipeline de catalogo para clasificar, y tener dos copias de la taxonomia es
 * exactamente como se desincronizan.
 */
class TaxonomiaSeeder extends Seeder
{
    public function run(): void
    {
        $taxonomia = Taxonomia::desdeArchivo();
        $categorias = [];
        $ordenCategoria = 0;
        $ordenSub = [];

        DB::transaction(function () use ($taxonomia, &$categorias, &$ordenCategoria, &$ordenSub) {
            foreach ($taxonomia->todas() as $entrada) {
                $nombreCategoria = $entrada['categoria'];
                $codigoCategoria = $entrada['codigo_categoria'];

                if ($nombreCategoria === '' || $codigoCategoria === '') {
                    continue;
                }

                if (! isset($categorias[$codigoCategoria])) {
                    $ordenCategoria += 10;

                    DB::table('categorias')->updateOrInsert(
                        ['codigo' => $codigoCategoria],
                        [
                            'nombre'     => $nombreCategoria,
                            'orden'      => $ordenCategoria,
                            'estado'     => 1,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ],
                    );

                    $categorias[$codigoCategoria] = DB::table('categorias')
                        ->where('codigo', $codigoCategoria)
                        ->value('id');

                    $ordenSub[$codigoCategoria] = 0;
                }

                $categoriaId = $categorias[$codigoCategoria];
                $ordenSub[$codigoCategoria] += 10;

                DB::table('subcategorias')->updateOrInsert(
                    ['categoria_id' => $categoriaId, 'codigo' => $entrada['codigo_subcategoria']],
                    [
                        'nombre'          => $entrada['subcategoria'],
                        'unidad_sugerida' => $entrada['unidad_sugerida'] ?: 'UNIDAD',
                        'orden'           => $ordenSub[$codigoCategoria],
                        'estado'          => 1,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ],
                );
            }
        });

        $this->command?->info(sprintf(
            '  Taxonomía: %d categorías, %d subcategorías',
            DB::table('categorias')->count(),
            DB::table('subcategorias')->count(),
        ));
    }
}
