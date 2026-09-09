<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * El catalogo real (data/catalogo-minimarket) se importa SIN precio ni stock a
 * proposito: la fuente scrapeada no los trae y no se inventan datos en el
 * importador. Pero para demostrar el sistema hace falta que el POS, el
 * dashboard y los reportes tengan con que trabajar.
 *
 * Este seeder es esa capa de demo, separada del importador: pone precio y stock
 * SOLO a los productos que estan en cero, asi que nunca pisa un precio real
 * cargado a mano ni los 33 productos curados de CatalogoSeeder.
 */
class DemoPreciosCatalogoSeeder extends Seeder
{
    /** Margen bruto sobre el precio de compra. */
    private const MARGEN = 0.38;

    /**
     * Rango de precio de VENTA (soles, IGV incluido) por subcategoria.
     * Son rangos de minimarket peruano, no precios de una tienda concreta.
     */
    private const RANGOS = [
        'ACEITES' => [7.50, 28.00],
        'AZUCAR' => [3.50, 9.50],
        'CONSERVAS' => [3.80, 14.00],
        'DESAYUNO' => [4.50, 19.00],
        'FIDEOS' => [2.60, 8.50],
        'MENESTRAS' => [3.20, 11.00],
        'SAL' => [1.50, 4.50],
        'SALSAS Y SAZONADORES' => [2.20, 13.50],
        'INFUSIONES' => [3.50, 14.00],
        'AGUA' => [1.50, 7.50],
        'BEBIDAS DEPORTIVAS' => [3.00, 8.00],
        'ENERGIZANTES' => [3.50, 12.00],
        'GASEOSAS' => [2.00, 10.50],
        'JUGOS Y NECTARES' => [2.00, 9.50],
        'TE E INFUSIONES LISTAS' => [2.50, 7.00],
        'CERVEZA' => [4.50, 14.00],
        'ESPUMANTES' => [22.00, 89.00],
        'VINO' => [18.00, 75.00],
        'CHOCOLATES' => [1.20, 16.00],
        'DULCES' => [0.80, 9.00],
        'GALLETAS' => [1.00, 8.50],
        'GOLOSINAS' => [0.50, 6.50],
        'CREMAS' => [3.50, 12.00],
        'LECHE' => [3.00, 12.50],
        'MANTEQUILLA Y MARGARINA' => [3.80, 15.00],
        'YOGURT' => [1.80, 13.50],
        'EXTRUIDOS' => [1.00, 7.50],
        'SNACKS SALADOS' => [1.00, 9.50],
        'DETERGENTES' => [3.50, 26.00],
        'LAVAVAJILLAS' => [2.80, 14.00],
        'LEJIAS Y DESINFECTANTES' => [2.50, 16.00],
        'PAPEL Y CELULOSA' => [2.50, 22.00],
        'SUAVIZANTES' => [4.50, 24.00],
        'UTILES DE LIMPIEZA' => [2.00, 18.00],
        'AFEITADO' => [3.50, 32.00],
        'CUIDADO DENTAL' => [2.50, 19.00],
        'DESODORANTES' => [6.50, 26.00],
        'JABONES' => [1.80, 14.00],
        'PANALES Y TOALLAS' => [4.50, 45.00],
        'SHAMPOO Y ACONDICIONADOR' => [7.00, 38.00],
        'CARNES' => [12.00, 45.00],
        'EMBUTIDOS' => [5.00, 26.00],
        'HUEVOS' => [6.50, 22.00],
        'POLLO' => [9.00, 28.00],
        'VERDURAS' => [1.50, 9.00],
    ];

    /** Rango por defecto cuando la subcategoria no esta en la tabla. */
    private const RANGO_DEFECTO = [2.50, 15.00];

    public function run(): void
    {
        DB::transaction(function () {
            $almacenes = DB::table('almacenes')->orderBy('id')->pluck('id')->all();

            if ($almacenes === []) {
                $this->command?->warn('  No hay almacenes: corre AlmacenCajaSeeder primero.');

                return;
            }

            $subcategorias = DB::table('subcategorias')->pluck('nombre', 'id')->all();

            $productos = DB::table('productos')
                ->where('precio_venta', '<=', 0)
                ->get(['id', 'subcategoria_id', 'unidad']);

            if ($productos->isEmpty()) {
                $this->command?->info('  Todos los productos ya tienen precio. Nada que hacer.');

                return;
            }

            $precios = [];
            $reparto = [];

            // Uno de cada 12 queda bajo el stock minimo para que el panel de
            // alertas del dashboard tenga casos reales que mostrar.
            $i = 0;

            foreach ($productos as $producto) {
                $sub = $subcategorias[$producto->subcategoria_id] ?? null;
                [$min, $max] = self::RANGOS[$sub] ?? self::RANGO_DEFECTO;

                $venta = $this->redondearPrecio($min + (mt_rand() / mt_getrandmax()) * ($max - $min));
                $compra = round($venta / (1 + self::MARGEN), 2);

                $minimo = $venta > 25 ? random_int(4, 8) : random_int(10, 30);
                $bajo = ++$i % 12 === 0;
                $stock = $bajo
                    ? random_int(0, max(1, (int) floor($minimo * 0.4)))
                    : random_int($minimo * 2, $minimo * 8);

                $precios[] = [
                    'id' => $producto->id,
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'stock_minimo' => $minimo,
                ];

                // 70% en el almacen principal, el resto en la tienda: asi los
                // traslados y el filtro por almacen del POS tienen sentido.
                $principal = (int) round($stock * 0.7);
                $reparto[] = ['producto_id' => $producto->id, 'almacen_id' => $almacenes[0], 'stock' => $principal];

                if (isset($almacenes[1])) {
                    $reparto[] = ['producto_id' => $producto->id, 'almacen_id' => $almacenes[1], 'stock' => $stock - $principal];
                }
            }

            $this->aplicarPrecios($precios);
            $this->aplicarStock($reparto);

            $this->command?->info(sprintf(
                '  Precio y stock de demo asignados a %d productos.',
                count($precios)
            ));
        });
    }

    /**
     * Precios "de gondola": termina en 0, 0.50 o 0.90, nunca en 7.3841.
     */
    private function redondearPrecio(float $valor): float
    {
        $terminaciones = [0.00, 0.50, 0.90];
        $entero = floor($valor);
        $centavos = $terminaciones[array_rand($terminaciones)];

        return max(0.50, round($entero + $centavos, 2));
    }

    /**
     * Un UPDATE por producto son ~750 round-trips. Con CASE los precios entran
     * en un par de sentencias.
     *
     * @param  list<array{id:int,precio_compra:float,precio_venta:float,stock_minimo:int}>  $precios
     */
    private function aplicarPrecios(array $precios): void
    {
        foreach (array_chunk($precios, 200) as $lote) {
            $ids = array_column($lote, 'id');
            $casos = ['precio_compra' => '', 'precio_venta' => '', 'stock_minimo' => ''];

            foreach ($lote as $fila) {
                foreach ($casos as $columna => $_) {
                    $casos[$columna] .= sprintf(' WHEN %d THEN %s', $fila['id'], $fila[$columna]);
                }
            }

            DB::update(sprintf(
                'UPDATE productos SET precio_compra = CASE id%s END, precio_venta = CASE id%s END, stock_minimo = CASE id%s END WHERE id IN (%s)',
                $casos['precio_compra'],
                $casos['precio_venta'],
                $casos['stock_minimo'],
                implode(',', $ids)
            ));
        }
    }

    /**
     * @param  list<array{producto_id:int,almacen_id:int,stock:int}>  $reparto
     */
    private function aplicarStock(array $reparto): void
    {
        $ahora = now();

        foreach (array_chunk($reparto, 400) as $lote) {
            DB::table('producto_almacen')->upsert(
                array_map(fn (array $f) => $f + ['created_at' => $ahora, 'updated_at' => $ahora], $lote),
                ['producto_id', 'almacen_id'],
                ['stock', 'updated_at']
            );
        }
    }
}
