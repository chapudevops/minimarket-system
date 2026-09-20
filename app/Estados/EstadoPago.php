<?php

namespace App\Estados;

/**
 * Situacion de cobro de una venta.
 *
 * Estaba metido dentro de `estado` como PENDIENTE, lo que hacia imposible
 * distinguir una venta a credito sin cobrar de una venta con problemas. Una
 * venta a credito es comercialmente valida: lo que falta es el dinero.
 */
class EstadoPago
{
    public const PAGADA = 'PAGADA';

    /** Venta a credito con cuotas pendientes. */
    public const PENDIENTE = 'PENDIENTE';

    /** Venta a credito con parte de las cuotas cobradas. */
    public const PARCIAL = 'PARCIAL';

    public const TODOS = [self::PAGADA, self::PENDIENTE, self::PARCIAL];

    /** Deduce el estado desde las cuotas: total, cuantas estan pagadas. */
    public static function desdeCuotas(int $cuotas, int $pagadas): string
    {
        if ($cuotas === 0 || $pagadas >= $cuotas) {
            return self::PAGADA;
        }

        return $pagadas > 0 ? self::PARCIAL : self::PENDIENTE;
    }

    public static function badge(?string $estado): string
    {
        $badges = [
            self::PAGADA    => ['success', 'Pagada'],
            self::PENDIENTE => ['warning', 'Por cobrar'],
            self::PARCIAL   => ['info', 'Cobro parcial'],
        ];

        [$color, $texto] = $badges[$estado] ?? ['secondary', (string) $estado];

        return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
    }
}
