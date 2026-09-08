<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo maestro de un minimarket peruano.
 *
 * Lee los archivos de database/seeders/catalogo/, los expande con
 * CatalogoMinimarket y los inserta.
 *
 * Es idempotente: la clave natural es codigo_interno, así que volver a
 * sembrarlo actualiza en vez de duplicar.
 */
class CatalogoMinimarketSeeder extends Seeder
{
    /** Orden de carga. El nombre del archivo, sin extensión. */
    private const CATEGORIAS = [
        'bebidas', 'alcohol', 'snacks', 'abarrotes', 'lacteos',
        'desayuno', 'limpieza', 'cuidado-personal', 'hogar',
        'frescos', 'carnes', 'estacionales', 'servicios',
    ];

    public function run(): void
    {
        $motor = new CatalogoMinimarket;
        $resumen = [];
        $todos = [];

        foreach (self::CATEGORIAS as $nombre) {
            $definicion = require database_path("seeders/catalogo/{$nombre}.php");
            $productos = $motor->expandir($definicion);

            $this->guardar($productos);

            $resumen[$nombre] = count($productos);
            $todos = array_merge($todos, $productos);
        }

        $this->validar($todos);
        $this->reportar($resumen, $todos);
    }

    /**
     * Inserta por lotes. updateOrInsert sobre codigo_interno hace que volver a
     * correr el seeder actualice precios en lugar de duplicar productos.
     */
    private function guardar(array $productos): void
    {
        DB::transaction(function () use ($productos) {
            foreach ($productos as $producto) {
                Producto::updateOrCreate(
                    ['codigo_interno' => $producto['codigo_interno']],
                    $producto
                );
            }
        });
    }

    /**
     * Controles de calidad. Si algo no cuadra el seeder falla: es preferible
     * a dejar un catálogo silenciosamente corrupto.
     */
    private function validar(array $productos): void
    {
        $errores = [];

        $codigos = array_column($productos, 'codigo_interno');
        $repetidos = array_diff_assoc($codigos, array_unique($codigos));
        if ($repetidos) {
            $errores[] = 'Códigos internos repetidos: '.implode(', ', array_unique($repetidos));
        }

        foreach ($productos as $p) {
            $ref = $p['codigo_interno'];

            if ($p['precio_compra'] <= 0) {
                $errores[] = "{$ref}: precio de compra en cero o negativo";
            }

            if ($p['precio_venta'] <= $p['precio_compra']) {
                $errores[] = "{$ref}: el precio de venta no supera al de compra";
            }

            if (trim($p['descripcion']) === '') {
                $errores[] = "{$ref}: sin descripción";
            }

            if (! in_array($p['unidad'], CatalogoMinimarket::UNIDADES, true)) {
                $errores[] = "{$ref}: unidad inválida ({$p['unidad']})";
            }

            if (! in_array($p['operacion'], ['GRAVADO', 'EXONERADO', 'INAFECTO'], true)) {
                $errores[] = "{$ref}: operación inválida ({$p['operacion']})";
            }

            if (! in_array($p['tipo_producto'], ['PRODUCTO', 'SERVICIO'], true)) {
                $errores[] = "{$ref}: tipo inválido ({$p['tipo_producto']})";
            }

            if ($p['stock_minimo'] < 0) {
                $errores[] = "{$ref}: stock mínimo negativo";
            }
        }

        if ($errores) {
            throw new \RuntimeException(
                "El catálogo no pasó las validaciones:\n  - ".implode("\n  - ", array_slice($errores, 0, 20))
            );
        }
    }

    private function reportar(array $resumen, array $todos): void
    {
        $marcas = count(array_unique(array_filter(array_column($todos, 'marca'))));
        $presentaciones = count(array_unique(array_filter(array_column($todos, 'presentacion'))));
        $conBarras = count(array_filter(array_column($todos, 'codigo_barras')));
        $servicios = count(array_filter($todos, fn ($p) => $p['tipo_producto'] === 'SERVICIO'));
        $porKilo = count(array_filter($todos, fn ($p) => $p['unidad'] === 'KG'));

        $this->command?->newLine();
        $this->command?->info(sprintf('CATÁLOGO MAESTRO: %s referencias', number_format(count($todos))));
        $this->command?->newLine();

        foreach ($resumen as $categoria => $cantidad) {
            $this->command?->line(sprintf('  %-20s %s', $categoria, number_format($cantidad)));
        }

        $this->command?->newLine();
        $this->command?->line(sprintf('  %-20s %s', 'marcas distintas', $marcas));
        $this->command?->line(sprintf('  %-20s %s', 'presentaciones', $presentaciones));
        $this->command?->line(sprintf('  %-20s %s', 'con código de barras', $conBarras));
        $this->command?->line(sprintf('  %-20s %s', 'sin código de barras', count($todos) - $conBarras));
        $this->command?->line(sprintf('  %-20s %s', 'servicios', $servicios));
        $this->command?->line(sprintf('  %-20s %s', 'vendidos por kilo', $porKilo));
    }
}
