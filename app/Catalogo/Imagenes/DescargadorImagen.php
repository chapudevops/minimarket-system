<?php

namespace App\Catalogo\Imagenes;

use Closure;
use RuntimeException;

/**
 * Baja la imagen, la achica y la guarda como WebP.
 *
 * Es la pieza que faltaba del pipeline: EnriquecedorImagenes ya decidia el
 * nombre del archivo pero nadie lo escribia.
 *
 * Usa GD, que ya viene con el PHP del proyecto y soporta WebP. No hace falta
 * intervention/image ni imagick para redimensionar y transcodificar.
 *
 * Dos cuidados que importan mas de lo que parecen:
 *
 *   - Se valida el Content-Type y el peso ANTES de aceptar el cuerpo. Una URL
 *     puede devolver una pagina de error de 300 KB con HTTP 200, y guardarla
 *     como .webp deja un archivo roto que el POS no puede pintar.
 *   - El nombre del archivo es interno ({id}.webp), nunca el del tercero.
 */
class DescargadorImagen
{
    /** Lado mayor de la imagen guardada. Un POS no necesita mas. */
    public const LADO_MAXIMO = 600;

    /** Calidad WebP: por encima de esto el peso sube sin verse mejor. */
    private const CALIDAD = 82;

    /** Tope de descarga. Las imagenes de catalogo no llegan ni cerca. */
    private const PESO_MAXIMO = 5_242_880;

    private const TIPOS = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * @param  string  $directorio  donde se guardan los .webp
     * @param  Closure|null  $descargar  fn(string $url): array{0:int,1:?string,2:?string}
     *                                   codigo, cuerpo, content-type. Se inyecta
     *                                   en los tests para no salir a internet.
     */
    public function __construct(
        private readonly string $directorio,
        private readonly ?Closure $descargar = null,
        private readonly string $agente = Fuentes\ClienteOpenFoodFacts::AGENTE,
    ) {}

    /**
     * Guarda la imagen del producto y devuelve la ruta relativa.
     *
     * @return string ruta relativa para la columna `foto` (productos/12.webp)
     *
     * @throws RuntimeException si no se pudo obtener una imagen utilizable
     */
    public function guardar(string $url, int $productoId): string
    {
        [$codigo, $cuerpo, $tipo] = $this->pedir($url);

        if ($codigo !== 200 || $cuerpo === null || $cuerpo === '') {
            throw new RuntimeException("la descarga devolvió HTTP {$codigo}");
        }

        if (strlen($cuerpo) > self::PESO_MAXIMO) {
            throw new RuntimeException('la imagen supera el tope de peso');
        }

        // El Content-Type puede faltar; en ese caso decide GD, que es el juez
        // que importa: si no puede abrirlo, no es una imagen.
        if ($tipo !== null && ! $this->esTipoDeImagen($tipo)) {
            throw new RuntimeException("el contenido no es una imagen ({$tipo})");
        }

        $imagen = @imagecreatefromstring($cuerpo);

        if ($imagen === false) {
            throw new RuntimeException('el contenido no se pudo abrir como imagen');
        }

        try {
            $redimensionada = $this->redimensionar($imagen);

            $relativa = "productos/{$productoId}.webp";
            $destino = rtrim($this->directorio, '/').'/'.$relativa;

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

            return $relativa;
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

    /** @return array{0:int,1:?string,2:?string} */
    private function pedir(string $url): array
    {
        if ($this->descargar !== null) {
            return ($this->descargar)($url);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_USERAGENT => $this->agente,
            CURLOPT_HTTPHEADER => ['Accept: image/webp,image/*'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tipo = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return [$codigo, $cuerpo === false ? null : (string) $cuerpo, $tipo ?: null];
    }
}
