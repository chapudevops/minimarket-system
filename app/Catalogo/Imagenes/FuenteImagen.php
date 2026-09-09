<?php

namespace App\Catalogo\Imagenes;

/**
 * Una fuente de la que se pueden pedir imagenes por codigo de barras.
 *
 * La interfaz existe para que el enriquecedor no sepa de donde salen las
 * imagenes: hoy ninguna fuente externa esta habilitada, y los tests corren
 * contra una implementacion con respuestas guardadas. El dia que haya una
 * fuente autorizada se enchufa aca sin tocar el resto.
 */
interface FuenteImagen
{
    /** Nombre con el que figura en diccionarios/fuentes_imagen.csv. */
    public function nombre(): string;

    /** null cuando la fuente no conoce ese codigo. */
    public function buscarPorEan(string $ean): ?CandidataImagen;
}
