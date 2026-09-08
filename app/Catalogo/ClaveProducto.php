<?php

namespace App\Catalogo;

/**
 * Clave natural de un producto: marca + descripcion + presentacion, las tres
 * normalizadas.
 *
 * No se usa el codigo de barras porque las fuentes publicas casi nunca lo
 * exponen; y no se usa la descripcion sola porque el mismo producto viene
 * escrito distinto en cada supermercado.
 */
class ClaveProducto
{
    public function __construct(
        private readonly NormalizadorMarca $marcas,
        private readonly NormalizadorPresentacion $presentaciones,
    ) {}

    public function de(?string $marca, ?string $descripcion, ?string $presentacion): string
    {
        $claveMarca = $this->marcas->clave($marca);
        $clavePresentacion = $this->presentaciones->clave($presentacion);

        return implode('|', [
            $claveMarca,
            $this->claveDescripcion($descripcion, $claveMarca, $clavePresentacion),
            $clavePresentacion,
        ]);
    }

    /**
     * Clave debil: solo marca + presentacion.
     *
     * Sirve para levantar sospechas, no para fusionar. "Gaseosa Coca Cola
     * Original" y "Coca-Cola Original" son el mismo producto pero la palabra
     * "Gaseosa" hace que no colapsen, y adivinar que palabras sobran es como
     * se inventan productos. Se marcan para que alguien mire.
     */
    public function debil(?string $marca, ?string $presentacion): string
    {
        return $this->marcas->clave($marca) . '|' . $this->presentaciones->clave($presentacion);
    }

    /**
     * La descripcion pierde la marca y la presentacion que ya viajan en sus
     * propias columnas.
     *
     * Sin esto, "Coca-Cola 500 ml" (Metro, que repite todo en el nombre) y
     * "Coca Cola Original" (Tottus, que lo separa) no colapsarian, que es
     * justo el duplicado que hay que cazar.
     */
    private function claveDescripcion(?string $descripcion, string $claveMarca, string $clavePresentacion): string
    {
        $clave = Texto::clave($descripcion);

        foreach ([$claveMarca, $clavePresentacion] as $sobrante) {
            if ($sobrante !== '' && str_contains($clave, $sobrante)) {
                $clave = str_replace($sobrante, '', $clave);
            }
        }

        return $clave;
    }
}
