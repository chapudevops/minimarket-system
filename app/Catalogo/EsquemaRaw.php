<?php

namespace App\Catalogo;

/**
 * Esquema del CSV RAW: lo que se copia de un catalogo publico, tal cual se
 * publica, sin interpretar.
 *
 * precio_referencia es el precio de gondola de la fuente. NO es el precio de
 * compra del minimarket y no se convierte en uno: viaja para poder comparar
 * despues contra las listas del proveedor.
 *
 * codigo_barras es el EAN/UPC tal como lo publica la fuente. Vacio es un valor
 * legitimo y frecuente: significa que la fuente no lo publica, no que el
 * producto no tenga codigo. Nunca se rellena.
 *
 * producto_tipo es opcional y solo hace falta donde la subcategoria no alcanza
 * para clasificar tributariamente: "leche cruda entera" y "leche evaporada"
 * caen en la misma subcategoria y NO tributan igual. Vacio significa "no se
 * precisó", y en esas familias la afectacion sale PENDIENTE a proposito.
 */
class EsquemaRaw
{
    public const COLUMNAS = [
        'fuente',
        'categoria',
        'subcategoria',
        'producto_tipo',
        'marca',
        'descripcion',
        'presentacion',
        'codigo_barras',
        'precio_referencia',
        'url_fuente',
        'fecha_consulta',
    ];

    /** Sin estas no se puede clasificar ni codificar la fila. */
    public const OBLIGATORIAS = ['fuente', 'categoria', 'subcategoria', 'descripcion', 'url_fuente', 'fecha_consulta'];

    /**
     * @param  array<string,mixed>  $fila
     * @return array<int,string> lista de problemas; vacia si la fila sirve
     */
    public static function validar(array $fila, ?Taxonomia $taxonomia = null): array
    {
        $errores = [];

        foreach (self::OBLIGATORIAS as $columna) {
            if (trim((string) ($fila[$columna] ?? '')) === '') {
                $errores[] = "falta {$columna}";
            }
        }

        $precio = trim((string) ($fila['precio_referencia'] ?? ''));

        if ($precio !== '' && ! is_numeric(str_replace(',', '.', $precio))) {
            $errores[] = 'precio_referencia no es numerico';
        }

        $fecha = trim((string) ($fila['fecha_consulta'] ?? ''));

        if ($fecha !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $errores[] = 'fecha_consulta debe ser AAAA-MM-DD';
        }

        $barras = trim((string) ($fila['codigo_barras'] ?? ''));

        // Vacio esta bien. Un codigo presente pero que no cierra el digito
        // verificador NO: significa que alguien lo tipeo o lo invento, y un
        // EAN equivocado hace que el escaner traiga otro producto.
        if ($barras !== '' && ! CodigoBarras::esValido($barras)) {
            $errores[] = "codigo_barras no es un GTIN valido: {$barras}";
        }

        $url = trim((string) ($fila['url_fuente'] ?? ''));

        // La url es la prueba de donde salio el dato: si no se puede volver a
        // ella, la fila no es verificable y no entra al maestro.
        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $errores[] = 'url_fuente debe empezar con http:// o https://';
        }

        if ($taxonomia !== null && $taxonomia->prefijo($fila['categoria'] ?? null, $fila['subcategoria'] ?? null) === null) {
            $errores[] = sprintf(
                'categoria/subcategoria no declarada en diccionarios/taxonomia.csv (%s / %s)',
                $fila['categoria'] ?? '',
                $fila['subcategoria'] ?? ''
            );
        }

        return $errores;
    }
}
