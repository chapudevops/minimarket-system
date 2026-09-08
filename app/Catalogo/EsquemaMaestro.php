<?php

namespace App\Catalogo;

use App\Sunat\Tributos;

/**
 * Esquema del catalogo normalizado.
 *
 * Lleva los campos que acepta ProductoController::store (menos estado, que
 * siempre entra activo). categoria, subcategoria, producto_tipo,
 * requiere_revision_tributaria, fuente y url_fuente son trazabilidad del
 * catalogo y no se guardan en productos: la tabla no tiene esas columnas.
 *
 * afecto_isc y afecto_ivap SI se guardan: son las dos columnas que esta fase
 * agrego a productos para dejar de meter todo dentro de `operacion`.
 *
 * Las columnas que quedan vacias a proposito en esta fase:
 *   codigo_barras      no hay EAN verificable en las fuentes publicas
 *   precio_compra      lo define la lista del proveedor, no la gondola
 *   precio_venta       depende del precio de compra
 *   fecha_vencimiento  la trae el lote al recepcionarlo
 *   stock_minimo       depende de la rotacion real de la tienda
 *   foto               se carga con su propio modulo
 */
class EsquemaMaestro
{
    public const COLUMNAS = [
        'codigo_interno',
        'codigo_barras',
        'descripcion',
        'categoria',
        'subcategoria',
        'producto_tipo',
        'unidad',
        'marca',
        'presentacion',
        'operacion',
        'afecto_isc',
        'afecto_ivap',
        'requiere_revision_tributaria',
        'precio_compra',
        'precio_venta',
        'fecha_vencimiento',
        'tipo_producto',
        'foto',
        'detraccion',
        'stock_minimo',
        'fuente',
        'url_fuente',
    ];

    /** Columnas que esta fase deja vacias porque no hay dato verificable. */
    public const PENDIENTES_DE_DATO = [
        'codigo_barras',
        'precio_compra',
        'precio_venta',
        'fecha_vencimiento',
        'stock_minimo',
        'foto',
    ];

    /**
     * Una fila esta lista para importarse a productos solo si tiene codigo,
     * operacion resuelta y los precios definidos por alguien.
     *
     * @param  array<string,mixed>  $fila
     * @return array<int,string>
     */
    public static function faltantesParaImportar(array $fila): array
    {
        $faltantes = [];

        foreach (['codigo_interno', 'descripcion', 'unidad', 'tipo_producto'] as $columna) {
            if (trim((string) ($fila[$columna] ?? '')) === '') {
                $faltantes[] = $columna;
            }
        }

        // PENDIENTE es exactamente lo que este control existe para frenar: un
        // producto sin afectacion de IGV decidida no entra al catalogo, y por
        // lo tanto no puede llegar a una venta.
        if (! Tributos::esAfectacionValida($fila['operacion'] ?? '')) {
            $faltantes[] = 'operacion';
        }

        foreach (['precio_compra', 'precio_venta'] as $columna) {
            if (! is_numeric(trim((string) ($fila[$columna] ?? '')))) {
                $faltantes[] = $columna;
            }
        }

        return $faltantes;
    }
}
