<?php

namespace App\Estados;

/**
 * Estado COMERCIAL de una venta. Nada que ver con SUNAT.
 *
 * Una venta cerrada en el POS nace APROBADA y se queda APROBADA aunque SUNAT
 * este caido, sin configurar, en cola o la rechace. Eso vive en `estado_sunat`.
 *
 * Antes este campo cargaba con tres significados a la vez:
 *   COMPLETADA -> venta valida
 *   PENDIENTE  -> venta a credito sin cobrar   (cobranza, no validez)
 *   ANULADA    -> venta invalidada
 *
 * La cobranza se mudo a `estado_pago` y las devoluciones a `estado_devolucion`,
 * asi que aqui solo queda la pregunta que este campo deberia responder: ¿esta
 * venta cuenta o no cuenta?
 */
class EstadoVenta
{
    /** Operacion registrada correctamente. Cuenta para reportes y caja. */
    public const APROBADA = 'APROBADA';

    /** Invalidada por completo mediante un flujo permitido. No cuenta. */
    public const ANULADA = 'ANULADA';

    public const TODOS = [self::APROBADA, self::ANULADA];

    /**
     * Transiciones permitidas. Una venta anulada no vuelve atras: para
     * corregirla se emite un documento nuevo, no se reabre la anterior.
     */
    public const TRANSICIONES = [
        self::APROBADA => [self::ANULADA],
        self::ANULADA => [],
    ];

    public static function puedeTransicionar(?string $desde, string $hasta): bool
    {
        return in_array($hasta, self::TRANSICIONES[$desde] ?? [], true);
    }

    public static function badge(?string $estado): string
    {
        $badges = [
            self::APROBADA => ['success', 'Aprobada'],
            self::ANULADA  => ['danger', 'Anulada'],
        ];

        [$color, $texto] = $badges[$estado] ?? ['secondary', (string) $estado];

        return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
    }
}
