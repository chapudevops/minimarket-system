<?php

namespace Tests\Support;

use App\Catalogo\Imagenes\CandidataImagen;
use App\Catalogo\Imagenes\FuenteImagen;
use RuntimeException;

/**
 * Fuente de imagenes con respuestas guardadas.
 *
 * Ningun test sale a internet: un test que depende de que una web este arriba
 * no prueba nuestro codigo, prueba la conexion. Ademas permite provocar a
 * voluntad lo que en produccion pasa de vez en cuando —timeout, respuesta
 * invalida, EAN desconocido— que es justo lo que hay que cubrir.
 */
class FuenteImagenFalsa implements FuenteImagen
{
    /** @var array<string,array<string,mixed>|null> */
    private array $respuestas = [];

    /** @var array<string,int> EAN => cuantas veces fallar antes de responder */
    private array $fallos = [];

    /** @var array<int,string> EAN consultados, en orden */
    public array $consultas = [];

    public function __construct(private readonly string $nombre = 'FUENTE_PRUEBA') {}

    public function nombre(): string
    {
        return $this->nombre;
    }

    /** @param array<string,mixed>|null $datos null = la fuente no lo conoce */
    public function responde(string $ean, ?array $datos): self
    {
        $this->respuestas[$ean] = $datos;

        return $this;
    }

    /** Falla $veces y despues responde normalmente. Para probar el reintento. */
    public function fallaVeces(string $ean, int $veces): self
    {
        $this->fallos[$ean] = $veces;

        return $this;
    }

    public function buscarPorEan(string $ean): ?CandidataImagen
    {
        $this->consultas[] = $ean;

        if (($this->fallos[$ean] ?? 0) > 0) {
            $this->fallos[$ean]--;

            throw new RuntimeException("timeout consultando {$ean}");
        }

        if (! array_key_exists($ean, $this->respuestas)) {
            return null;
        }

        $datos = $this->respuestas[$ean];

        return $datos === null ? null : CandidataImagen::desdeArray($this->nombre, $datos);
    }
}
