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
     * Una fuente sin registrar NO se usa: el default es no, porque el lado
     * seguro para equivocarse es no tocar el sitio de otro.
     */
    public function sePuedeConsultar(string $fuente): bool
    {
        $registro = $this->buscar($fuente);

        if ($registro === null) {
            return false;
        }

        return ($registro['robots_permite'] ?? 'NO') === 'SI'
            && in_array($registro['estado'] ?? '', [self::DISPONIBLE, self::SOLO_REFERENCIA], true);
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
