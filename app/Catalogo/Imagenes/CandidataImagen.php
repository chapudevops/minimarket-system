<?php

namespace App\Catalogo\Imagenes;

/**
 * Lo que devuelve una fuente de imagenes para un EAN.
 *
 * Trae la imagen y ademas los datos del producto segun la fuente, porque sin
 * ellos no hay forma de comprobar que la foto sea de este producto y no de su
 * hermano de 1.5 L.
 */
class CandidataImagen
{
    public function __construct(
        public readonly string $fuente,
        public readonly string $url,
        public readonly ?string $marca = null,
        public readonly ?string $nombre = null,
        public readonly ?string $presentacion = null,
        public readonly ?string $ean = null,
    ) {}

    /** @param array<string,mixed> $datos */
    public static function desdeArray(string $fuente, array $datos): self
    {
        return new self(
            fuente: $fuente,
            url: (string) ($datos['url'] ?? ''),
            marca: $datos['marca'] ?? null,
            nombre: $datos['nombre'] ?? null,
            presentacion: $datos['presentacion'] ?? null,
            ean: $datos['ean'] ?? null,
        );
    }
}
