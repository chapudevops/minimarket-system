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
        'precio_compra',
        'precio_venta',
        'fecha_vencimiento',
        'stock_minimo',
        'foto',
    ];

    /**
     * Una fila esta lista para importarse si tiene codigo, descripcion, unidad
     * y una afectacion de IGV resuelta.
     *
     * Los precios NO se exigen, y es deliberado. Exigirlos junto con la regla
     * de que no se inventan —precio_compra sale de la lista del proveedor,
     * precio_venta lo decide el comercio— dejaba el catalogo imposible de
     * cargar: las dos reglas juntas no se pueden cumplir.
     *
     * La salida es la misma que se uso para el stock: el producto entra al
     * catalogo, se puede buscar y aparece en el POS, pero no se puede vender
     * hasta que alguien le ponga precio. Un producto sin precio no es un
     * producto roto, es un producto que todavia no se puso a la venta.
     *
     * Quien impide la venta es Producto::estaListoParaVender(), no este
     * control: aca se decide que entra al catalogo, no que se puede cobrar.
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

        return $faltantes;
    }
}
