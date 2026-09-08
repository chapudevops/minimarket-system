<?php

namespace App\Catalogo;

use RuntimeException;

/**
 * Lectura y escritura de los CSV de la capa de catalogo.
 *
 * No usa maatwebsite/excel a proposito: ese paquete ya esta en el proyecto
 * para exportar reportes de caja a Excel, pero aca se trabaja con archivos
 * planos que se versionan y se revisan a mano, y arrastrar PhpSpreadsheet para
 * eso solo agrega peso y una dependencia de memoria innecesaria.
 */
class Csv
{
    /**
     * Todo el archivo en memoria. Comodo para diccionarios y para el RAW.
     *
     * @return array<int,array<string,string>> filas indexadas por cabecera
     */
    public static function leer(string $ruta): array
    {
        return iterator_to_array(self::porFilas($ruta), false);
    }

    /**
     * El archivo fila por fila, sin cargarlo entero.
     *
     * Es lo que usa el importador masivo: un catalogo de varios miles de
     * referencias no tiene por que caber en memoria de golpe, y con un
     * generador el consumo no depende del tamano del archivo.
     *
     * @return \Generator<int,array<string,string>>
     */
    public static function porFilas(string $ruta): \Generator
    {
        if (! is_file($ruta)) {
            throw new RuntimeException("No existe el archivo CSV: {$ruta}");
        }

        $manejador = fopen($ruta, 'r');

        if ($manejador === false) {
            throw new RuntimeException("No se pudo abrir el CSV: {$ruta}");
        }

        try {
            $cabecera = fgetcsv($manejador, 0, ",", "\"", "\\");

            if ($cabecera === false) {
                return;
            }

            // Excel y LibreOffice agregan BOM al guardar: sin quitarlo la
            // primera columna se llamaria "\xEF\xBB\xBFfuente".
            $cabecera[0] = preg_replace('/^\x{FEFF}/u', '', (string) $cabecera[0]);
            $cabecera = array_map(fn ($columna) => trim((string) $columna), $cabecera);

            while (($datos = fgetcsv($manejador, 0, ",", "\"", "\\")) !== false) {
                // Linea en blanco al final del archivo.
                if ($datos === [null] || $datos === ['']) {
                    continue;
                }

                $datos = array_pad(array_slice($datos, 0, count($cabecera)), count($cabecera), '');

                yield array_map(fn ($valor) => trim((string) $valor), array_combine($cabecera, $datos));
            }
        } finally {
            fclose($manejador);
        }
    }

    /** Cuenta las filas de datos sin cargarlas: solo para barras de progreso. */
    public static function contar(string $ruta): int
    {
        $total = 0;

        foreach (self::porFilas($ruta) as $ignorada) {
            $total++;
        }

        return $total;
    }

    /**
     * @param  array<int,string>  $columnas
     * @param  iterable<array<string,mixed>>  $filas
     */
    public static function escribir(string $ruta, array $columnas, iterable $filas): int
    {
        $directorio = dirname($ruta);

        if (! is_dir($directorio) && ! mkdir($directorio, 0775, true) && ! is_dir($directorio)) {
            throw new RuntimeException("No se pudo crear el directorio: {$directorio}");
        }

        $manejador = fopen($ruta, 'w');

        if ($manejador === false) {
            throw new RuntimeException("No se pudo escribir el CSV: {$ruta}");
        }

        try {
            fputcsv($manejador, $columnas);
            $escritas = 0;

            foreach ($filas as $fila) {
                // Un null en el CSV se escribe como celda vacia; el importador
                // lo vuelve a leer como null. Nunca como la cadena "NULL".
                fputcsv($manejador, array_map(
                    fn (string $columna) => $fila[$columna] ?? '',
                    $columnas
                ));
                $escritas++;
            }

            return $escritas;
        } finally {
            fclose($manejador);
        }
    }
}
