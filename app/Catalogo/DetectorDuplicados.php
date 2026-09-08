<?php

namespace App\Catalogo;

/**
 * Agrupa filas que describen el mismo producto.
 *
 * La primera aparicion gana y las siguientes quedan como repeticiones, con la
 * fuente de cada una: asi se ve de un vistazo si Metro y Plaza Vea publicaron
 * el mismo item o si una fuente se esta repitiendo a si misma.
 *
 * No borra nada por su cuenta. Devuelve los grupos y quien decide es el
 * comando de normalizacion, que ademas deja el reporte en procesados/.
 */
class DetectorDuplicados
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private array $grupos = [];

    /** @var array<string,array<string,array<int,array<string,mixed>>>> */
    private array $sospechas = [];

    public function __construct(private readonly ClaveProducto $claves) {}

    /**
     * @param  array<string,mixed>  $fila  fila RAW o normalizada
     * @return string la clave con la que se agrupo
     */
    public function agregar(array $fila): string
    {
        $clave = $this->claves->de(
            $fila['marca'] ?? null,
            $fila['descripcion'] ?? null,
            $fila['presentacion'] ?? null,
        );

        $this->grupos[$clave][] = $fila;

        $debil = $this->claves->debil($fila['marca'] ?? null, $fila['presentacion'] ?? null);
        $this->sospechas[$debil][$clave][] = $fila;

        return $clave;
    }

    /** @param iterable<array<string,mixed>> $filas */
    public function agregarTodas(iterable $filas): self
    {
        foreach ($filas as $fila) {
            $this->agregar($fila);
        }

        return $this;
    }

    public function esRepeticion(array $fila): bool
    {
        $clave = $this->claves->de(
            $fila['marca'] ?? null,
            $fila['descripcion'] ?? null,
            $fila['presentacion'] ?? null,
        );

        return isset($this->grupos[$clave]);
    }

    /** Solo la primera fila de cada grupo: el catalogo sin repetir. */
    public function unicos(): array
    {
        return array_map(fn (array $grupo) => $grupo[0], array_values($this->grupos));
    }

    /**
     * Grupos con mas de una fila.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function repetidos(): array
    {
        return array_filter($this->grupos, fn (array $grupo) => count($grupo) > 1);
    }

    /**
     * Misma marca y misma presentacion, pero descripcion distinta.
     *
     * No son duplicados probados: son candidatos a revision manual. Ahi caen
     * los casos que el texto no puede resolver solo, como "Gaseosa Coca Cola
     * Original" contra "Coca-Cola Original".
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function sospechosos(): array
    {
        $salida = [];

        foreach ($this->sospechas as $debil => $porClave) {
            if (count($porClave) < 2) {
                continue;
            }

            // Sin marca no hay sospecha que valga: agruparia medio catalogo.
            if (str_starts_with($debil, '|')) {
                continue;
            }

            $salida[$debil] = array_map(fn (array $grupo) => $grupo[0], array_values($porClave));
        }

        return $salida;
    }

    public function totalFilas(): int
    {
        return array_sum(array_map('count', $this->grupos));
    }

    public function totalUnicos(): int
    {
        return count($this->grupos);
    }

    public function totalRepetidas(): int
    {
        return $this->totalFilas() - $this->totalUnicos();
    }
}
