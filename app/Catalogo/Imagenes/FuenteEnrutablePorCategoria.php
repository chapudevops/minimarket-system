<?php

namespace App\Catalogo\Imagenes;

/**
 * Fuente que consulta un sitio distinto segun el tipo de producto.
 *
 * Open Food Facts es el caso: alimentos, cosmetica, limpieza y mascotas viven
 * en cuatro proyectos hermanos con la misma API y distinto host. El enriquecedor
 * le pasa la categoria antes de cada consulta.
 */
interface FuenteEnrutablePorCategoria
{
    public function paraCategoria(string $categoria): self;
}
