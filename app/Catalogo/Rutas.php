<?php

namespace App\Catalogo;

/**
 * Ubicaciones de la capa de datos del catalogo.
 *
 * Vive fuera de database/ a proposito: son insumos de trabajo (descargas,
 * revisiones a medias, reportes) y no forman parte del esquema ni del seeder.
 */
class Rutas
{
    public const BASE = 'data/catalogo-minimarket';

    public static function base(string $relativa = ''): string
    {
        $raiz = base_path(self::BASE);

        return $relativa === '' ? $raiz : $raiz . DIRECTORY_SEPARATOR . ltrim($relativa, '/');
    }

    public static function raw(string $archivo = ''): string
    {
        return self::base('raw' . ($archivo === '' ? '' : '/' . $archivo));
    }

    public static function procesados(string $archivo = ''): string
    {
        return self::base('procesados' . ($archivo === '' ? '' : '/' . $archivo));
    }

    public static function diccionario(string $archivo): string
    {
        return self::base('diccionarios/' . $archivo);
    }

    public static function maestro(): string
    {
        return self::base('catalogo_maestro.csv');
    }

    /**
     * Archivos RAW a procesar. El que empieza con "_" es plantilla y queda
     * fuera: no es data, es documentacion del formato.
     *
     * @return array<int,string>
     */
    public static function archivosRaw(): array
    {
        $archivos = array_filter(
            glob(self::raw('*.csv')) ?: [],
            fn (string $ruta) => ! str_starts_with(basename($ruta), '_')
        );

        sort($archivos);

        return array_values($archivos);
    }
}
