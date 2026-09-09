<?php

namespace App\Catalogo\Imagenes;

use App\Catalogo\Csv;
use App\Catalogo\Rutas;

/**
 * Que se puede hacer con cada fuente de imagenes.
 *
 * Existe porque "la imagen esta publica en internet" no dice nada sobre si se
 * puede usar. Cada fuente se registra con su licencia, si su robots.txt permite
 * el acceso automatizado y si sus condiciones permiten guardar una copia o solo
 * enlazar. El enriquecedor consulta esto antes de pedir nada.
 */
class RegistroFuentesImagen
{
    public const DISPONIBLE = 'DISPONIBLE';
    public const SOLO_REFERENCIA = 'SOLO_REFERENCIA';
    public const BLOQUEADA = 'BLOQUEADA';
    public const CASO_POR_CASO = 'CASO_POR_CASO';

    /** @var array<string,array<string,string>> */
    private array $fuentes = [];

    /** @param array<int,array<string,string>> $filas */
    public function __construct(array $filas = [])
    {
        foreach ($filas as $fila) {
            $nombre = strtoupper(trim((string) ($fila['fuente'] ?? '')));

            if ($nombre !== '') {
                $this->fuentes[$nombre] = array_map(fn ($v) => trim((string) $v), $fila);
            }
        }
    }

    public static function desdeArchivo(?string $ruta = null): self
    {
        return new self(Csv::leer($ruta ?? Rutas::diccionario('fuentes_imagen.csv')));
    }

    /** @return array<string,string>|null */
    public function buscar(string $fuente): ?array
    {
        return $this->fuentes[strtoupper(trim($fuente))] ?? null;
    }

    /**
     * Si se le puede pedir una imagen de forma automatizada.
     *
     * Decide `acceso_permitido`, no `robots_permite`. Son cosas distintas y
     * mezclarlas obligaba a mentir en una de las dos columnas: el robots.txt de
     * Open Food Facts prohibe /api a los rastreadores, y al mismo tiempo OFF
     * publica una API para aplicaciones con su limite de tasa. `robots_permite`
     * guarda el hecho; `acceso_permitido` guarda la conclusion, con el motivo
     * escrito en `observacion`.
     *
     * Una fuente sin registrar NO se usa: el default es no, porque el lado
     * seguro para equivocarse es no tocar el sitio de otro.
     */
    public function sePuedeConsultar(string $fuente): bool
    {
        $registro = $this->buscar($fuente);

        if ($registro === null) {
            return false;
        }

        return ($registro['acceso_permitido'] ?? 'NO') === 'SI'
            && in_array($registro['estado'] ?? '', [self::DISPONIBLE, self::SOLO_REFERENCIA], true);
    }

    /** Valor que usa el registro cuando la licencia de la imagen no se sabe. */
    public const NO_DETERMINABLE = 'NO_DETERMINABLE';

    /**
     * Licencia de la IMAGEN declarada por la fuente, o null si no se sabe.
     *
     * No es lo mismo que la licencia de los DATOS. En Open Food Facts la base
     * es ODbL y las fotos son CC BY-SA: dos licencias distintas del mismo
     * sitio, con obligaciones distintas. Confundirlas seria atribuir mal.
     */
    public function licenciaImagen(string $fuente): ?string
    {
        $licencia = trim($this->buscar($fuente)['licencia_imagen'] ?? '');

        return ($licencia === '' || $licencia === self::NO_DETERMINABLE) ? null : $licencia;
    }

    /** Licencia de los datos (nombre, marca, cantidad). Informativa. */
    public function licenciaDatos(string $fuente): ?string
    {
        return trim($this->buscar($fuente)['licencia_datos'] ?? '') ?: null;
    }

    /**
     * Credito exacto que exige la licencia de la imagen, o null si no aplica.
     *
     * @return array{texto: string, url: string}|null
     */
    public function atribucion(string $fuente): ?array
    {
        $licencia = $this->licenciaImagen($fuente);
        $quien = trim($this->buscar($fuente)['atribucion'] ?? '');

        if ($licencia === null || $quien === '') {
            return null;
        }

        return [
            'texto' => "Imagen: {$quien} ({$licencia})",
            'url' => trim($this->buscar($fuente)['url_atribucion'] ?? ''),
        ];
    }

    /**
     * Si se puede usar una imagen de esta fuente.
     *
     * Poder acceder no alcanza. Sin una licencia determinable no hay con que
     * justificar el uso, y una licencia inventada es peor que no tener imagen.
     */
    public function sePuedeUsarLaImagen(string $fuente): bool
    {
        return $this->licenciaImagen($fuente) !== null;
    }

    /** Si sus condiciones permiten guardar una copia del archivo. */
    public function sePuedeAlmacenar(string $fuente): bool
    {
        return ($this->buscar($fuente)['permite_almacenar'] ?? 'NO') === 'SI';
    }

    /** @return array<int,string> fuentes que hoy se pueden consultar */
    public function consultables(): array
    {
        return array_values(array_filter(
            array_keys($this->fuentes),
            fn (string $f) => $this->sePuedeConsultar($f)
        ));
    }

    /** @return array<int,array<string,string>> */
    public function todas(): array
    {
        return array_values($this->fuentes);
    }
}
