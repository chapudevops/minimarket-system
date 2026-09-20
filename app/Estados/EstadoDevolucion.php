<?php

namespace App\Estados;

/**
 * Cuanto de una venta se revirtio mediante notas de credito.
 *
 * Va en un campo aparte de `estado` a proposito: una venta con devolucion total
 * SIGUE siendo una venta que ocurrio, con su comprobante emitido y su asiento.
 * Si se marcara como ANULADA se perderia la diferencia entre "esto nunca debio
 * existir" y "se vendio y despues el cliente devolvio", que contablemente son
 * cosas distintas.
 *
 * Se calcula desde las notas de credito, no se escribe a mano.
 */
class EstadoDevolucion
{
    public const SIN_DEVOLUCION = 'SIN_DEVOLUCION';

    /** Se devolvio parte de la venta. La venta sigue siendo valida. */
    public const PARCIAL = 'PARCIAL';

    /** Se devolvieron todas las unidades vendidas. */
    public const TOTAL = 'TOTAL';

    public const TODOS = [self::SIN_DEVOLUCION, self::PARCIAL, self::TOTAL];

    /**
     * Deduce el estado a partir de unidades vendidas y devueltas.
     *
     * Se compara por unidades y no por importe: un descuento posterior via nota
     * de credito baja el importe sin que se devuelva mercaderia, y eso no es
     * una devolucion total aunque el monto coincida.
     */
    public static function desdeUnidades(int $vendidas, int $devueltas): string
    {
        if ($devueltas <= 0) {
            return self::SIN_DEVOLUCION;
        }

        return $devueltas >= $vendidas ? self::TOTAL : self::PARCIAL;
    }

    public static function badge(?string $estado): string
    {
        $badges = [
            self::SIN_DEVOLUCION => ['secondary', '—'],
            self::PARCIAL        => ['warning', 'Dev. parcial'],
            self::TOTAL          => ['danger', 'Dev. total'],
        ];

        [$color, $texto] = $badges[$estado] ?? ['secondary', (string) $estado];

        return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
    }
}
