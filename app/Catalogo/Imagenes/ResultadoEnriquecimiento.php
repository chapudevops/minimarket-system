<?php

namespace App\Catalogo\Imagenes;

/** Lo que dejo una corrida del enriquecedor de imagenes. */
class ResultadoEnriquecimiento
{
    public int $consultados = 0;
    public int $verificadas = 0;
    public int $aRevisar = 0;
    public int $sinImagen = 0;
    public int $omitidos = 0;
    public int $errores = 0;

    /** @var array<int,array<string,string>> */
    public array $detalle = [];

    public function anotar(array $fila): void
    {
        $this->detalle[] = $fila;
    }

    public function resueltos(): int
    {
        return $this->verificadas + $this->aRevisar + $this->sinImagen;
    }
}
