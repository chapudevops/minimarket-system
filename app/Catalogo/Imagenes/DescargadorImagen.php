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
        private readonly ?ProcesadorImagen $procesador = null,
    ) {}

    /** Lado mayor de la imagen guardada. Lo fija ProcesadorImagen. */
    public const LADO_MAXIMO = ProcesadorImagen::LADO_MAXIMO;

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

        $relativa = "productos/{$productoId}.webp";

        ($this->procesador ?? new ProcesadorImagen())->guardarComoWebp(
            $cuerpo,
            rtrim($this->directorio, '/').'/'.$relativa,
            $tipo,
        );

        return $relativa;
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
