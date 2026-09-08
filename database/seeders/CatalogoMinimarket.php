<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Motor del catálogo maestro.
 *
 * Los archivos de database/seeders/catalogo/ no listan productos uno por uno:
 * declaran FAMILIAS (marca + línea comercial) con sus presentaciones reales.
 * Acá se expanden a productos individuales, cada uno con su propio código.
 *
 * Se hizo así porque un archivo con 1.500 filas planas es imposible de
 * mantener: agregar un tamaño de Coca-Cola debe ser una línea, no un bloque.
 *
 * El sistema no tiene tablas de categorías ni de marcas, así que la
 * clasificación vive en el código interno: FAM-SUB-NNNNNN.
 */
class CatalogoMinimarket
{
    /** Unidades que ofrece el formulario de productos. */
    public const UNIDADES = ['UNIDAD', 'KG', 'LITRO', 'DOCENA', 'CAJA', 'HORA', 'MES'];

    /** Stock mínimo sugerido según qué tan rápido rota el producto. */
    private const STOCK_POR_ROTACION = [
        'alta' => [10, 30],
        'media' => [5, 15],
        'baja' => [2, 5],
    ];

    /** Días hasta el vencimiento típico, por perecibilidad. */
    private const VIDA_UTIL = [
        'corta' => [3, 15],     // pan, lácteos frescos, embutidos
        'media' => [60, 180],   // galletas, snacks, conservas abiertas
        'larga' => [365, 720],  // conservas, abarrotes secos
        'ninguna' => null,        // no perecible: la fecha va NULL
    ];

    /** @var array<string,int> Correlativo por prefijo FAM-SUB. */
    private array $correlativos = [];

    /** @var array<string,true> Para detectar duplicados dentro de la corrida. */
    private array $vistos = [];

    /**
     * Expande un archivo de categoría a productos listos para insertar.
     *
     * @param  array  $categoria  Lo que devuelve un archivo de catalogo/
     * @return array<int,array<string,mixed>>
     */
    public function expandir(array $categoria): array
    {
        $productos = [];

        foreach ($categoria['familias'] as $familia) {
            foreach ($familia['presentaciones'] as $presentacion => $precioCompra) {
                $producto = $this->armar($categoria, $familia, (string) $presentacion, (float) $precioCompra);

                if ($producto !== null) {
                    $productos[] = $producto;
                }
            }
        }

        return $productos;
    }

    private function armar(array $categoria, array $familia, string $presentacion, float $precioCompra): ?array
    {
        $descripcion = trim($familia['linea'].' '.$presentacion);

        // Clave natural secundaria: marca + descripcion + presentacion,
        // normalizada para que no se cuelen duplicados por mayusculas o
        // espacios de mas.
        $clave = $this->normalizar(($familia['marca'] ?? '').'|'.$descripcion.'|'.$presentacion);

        if (isset($this->vistos[$clave])) {
            return null;
        }

        $this->vistos[$clave] = true;

        $familiaCodigo = $categoria['codigo'];
        $sub = $familia['sub'];
        $prefijo = "{$familiaCodigo}-{$sub}";
        $this->correlativos[$prefijo] = ($this->correlativos[$prefijo] ?? 0) + 1;

        $codigo = sprintf('%s-%06d', $prefijo, $this->correlativos[$prefijo]);
        $rotacion = $familia['rotacion'] ?? $categoria['rotacion'] ?? 'media';

        return [
            'codigo_interno' => $codigo,
            // Prohibido inventar EAN: sin fuente verificable va NULL.
            'codigo_barras' => null,
            'descripcion' => $descripcion,
            'marca' => $familia['marca'] ?? null,
            'presentacion' => $presentacion,
            'unidad' => $familia['unidad'] ?? $categoria['unidad'] ?? 'UNIDAD',
            'operacion' => $familia['operacion'] ?? $categoria['operacion'] ?? 'GRAVADO',
            'tipo_producto' => $categoria['tipo'] ?? 'PRODUCTO',
            'precio_compra' => round($precioCompra, 2),
            'precio_venta' => $this->precioVenta($precioCompra, $categoria['margen'], $codigo),
            'stock_minimo' => $this->stockMinimo($rotacion, $codigo),
            'fecha_vencimiento' => $this->vencimiento($familia['vida'] ?? $categoria['vida'] ?? 'ninguna', $codigo),
            // La detraccion no aplica a productos de minimarket.
            'detraccion' => 0,
            // Las fotos reales se cargan despues, con su propio modulo.
            'foto' => null,
            'estado' => 1,
        ];
    }

    /**
     * Precio de venta con margen variable dentro del rango de la categoría.
     *
     * El margen se deriva del código y no de rand(): así el catálogo es
     * reproducible y volver a sembrarlo no cambia los precios.
     */
    private function precioVenta(float $compra, array $margen, string $codigo): float
    {
        [$min, $max] = $margen;
        $paso = (crc32($codigo) % 101) / 100;
        $aplicado = $min + ($max - $min) * $paso;

        $venta = round($compra * (1 + $aplicado), 2);

        // Garantia dura: la venta siempre por encima de la compra.
        return max($venta, round($compra + 0.10, 2));
    }

    private function stockMinimo(string $rotacion, string $codigo): int
    {
        [$min, $max] = self::STOCK_POR_ROTACION[$rotacion] ?? self::STOCK_POR_ROTACION['media'];

        return $min + (crc32($codigo.'stk') % ($max - $min + 1));
    }

    /** Los no perecibles quedan en NULL; el resto recibe fechas escalonadas. */
    private function vencimiento(string $vida, string $codigo): ?string
    {
        $rango = self::VIDA_UTIL[$vida] ?? null;

        if ($rango === null) {
            return null;
        }

        [$min, $max] = $rango;
        $dias = $min + (crc32($codigo.'vto') % ($max - $min + 1));

        return Carbon::now()->addDays($dias)->toDateString();
    }

    private function normalizar(string $valor): string
    {
        return Str::of($valor)->lower()->squish()->ascii()->toString();
    }
}
