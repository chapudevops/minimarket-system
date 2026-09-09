<?php

namespace App\Catalogo\Imagenes;

use RuntimeException;

/**
 * Valida, achica y convierte una imagen a WebP.
 *
 * Lo usan las dos vias por las que entra una foto al sistema —la descarga
 * automatica y la que sube una persona— para que las dos terminen igual: mismo
 * tope de dimension, mismo formato, mismo criterio de que es una imagen valida.
 *
 * Antes la foto subida a mano se guardaba tal cual: un JPEG de 4 MB del celular
 * quedaba en disco entero y el POS lo bajaba en cada pantalla.
 *
 * Usa GD, que ya viene con el PHP del proyecto y soporta WebP.
 */
class ProcesadorImagen
{
    /** Lado mayor de la imagen guardada. Un POS no necesita mas. */
    public const LADO_MAXIMO = 600;

    /** Por encima de esta calidad el peso sube sin que se vea mejor. */
    private const CALIDAD = 82;

    /** Tope de entrada. Las fotos de catalogo no llegan ni cerca. */
    public const PESO_MAXIMO = 5_242_880;

    private const TIPOS = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * Escribe el binario como WebP en la ruta indicada.
     *
     * @param  string|null  $tipoDeclarado  Content-Type, si la fuente lo dio
     *
     * @throws RuntimeException si el contenido no es una imagen utilizable
     */
    public function guardarComoWebp(string $binario, string $destino, ?string $tipoDeclarado = null): void
    {
        if ($binario === '') {
            throw new RuntimeException('el contenido está vacío');
        }

        if (strlen($binario) > self::PESO_MAXIMO) {
            throw new RuntimeException('la imagen supera el tope de peso');
        }

        // El Content-Type puede faltar o mentir; el juez final es GD. Pero si
        // viene y dice que es HTML, no hace falta ni intentarlo: una pagina de
        // error con HTTP 200 guardada como .webp deja un archivo roto.
        if ($tipoDeclarado !== null && ! $this->esTipoDeImagen($tipoDeclarado)) {
            throw new RuntimeException("el contenido no es una imagen ({$tipoDeclarado})");
        }

        $imagen = @imagecreatefromstring($binario);

        if ($imagen === false) {
            throw new RuntimeException('el contenido no se pudo abrir como imagen');
        }

        try {
            $redimensionada = $this->redimensionar($imagen);
            $carpeta = dirname($destino);

            if (! is_dir($carpeta) && ! mkdir($carpeta, 0775, true) && ! is_dir($carpeta)) {
                throw new RuntimeException("no se pudo crear {$carpeta}");
            }

            if (! imagewebp($redimensionada, $destino, self::CALIDAD)) {
                throw new RuntimeException('no se pudo escribir el WebP');
            }

            if ($redimensionada !== $imagen) {
                imagedestroy($redimensionada);
            }
        } finally {
            imagedestroy($imagen);
        }
    }

    /** Achica al lado maximo conservando la proporcion. No agranda nunca. */
    private function redimensionar(\GdImage $imagen): \GdImage
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $lado = max($ancho, $alto);

        if ($lado <= self::LADO_MAXIMO) {
            return $imagen;
        }

        $escala = self::LADO_MAXIMO / $lado;
        $nueva = imagescale($imagen, (int) round($ancho * $escala), (int) round($alto * $escala));

        return $nueva === false ? $imagen : $nueva;
    }

    private function esTipoDeImagen(string $tipo): bool
    {
        $tipo = strtolower(trim(explode(';', $tipo)[0]));

        return in_array($tipo, self::TIPOS, true);
    }
}
