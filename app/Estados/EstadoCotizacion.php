<?php

namespace App\Estados;

/**
 * Estado de una cotizacion. NO tiene estado SUNAT: no es un comprobante
 * electronico, es una propuesta comercial y nunca se envia.
 *
 * Se conservan solo los estados que el sistema escribe de verdad:
 * CotizacionController crea en PENDIENTE y mueve a APROBADA o RECHAZADA.
 *
 * VENCIDA la pintaba el badge pero no la escribia nadie. Se mantiene porque la
 * tabla tiene `fecha_validez` y el concepto existe, pero hasta que haya un
 * proceso que la aplique sigue sin usarse: se documenta en vez de fingir que
 * el flujo existe.
 *
 * CONVERTIDA no se agrega: hoy NO hay ningun camino de cotizacion a venta en
 * el sistema, asi que seria un estado al que no se puede llegar.
 */
class EstadoCotizacion
{
    /** Emitida, esperando respuesta del cliente. */
    public const PENDIENTE = 'PENDIENTE';

    /** El cliente la acepto. */
    public const APROBADA = 'APROBADA';

    /** El cliente la rechazo. */
    public const RECHAZADA = 'RECHAZADA';

    /** Paso su fecha de validez. Ningun proceso la escribe todavia. */
    public const VENCIDA = 'VENCIDA';

    public const TODOS = [self::PENDIENTE, self::APROBADA, self::RECHAZADA, self::VENCIDA];

    /** Una cotizacion resuelta no vuelve a PENDIENTE. */
    public const TRANSICIONES = [
        self::PENDIENTE => [self::APROBADA, self::RECHAZADA, self::VENCIDA],
        self::APROBADA => [],
        self::RECHAZADA => [],
        self::VENCIDA => [],
    ];

    public static function puedeTransicionar(?string $desde, string $hasta): bool
    {
        return in_array($hasta, self::TRANSICIONES[$desde] ?? [], true);
    }

    public static function badge(?string $estado): string
    {
        $badges = [
            self::PENDIENTE => ['warning', 'Pendiente'],
            self::APROBADA  => ['success', 'Aprobada'],
            self::RECHAZADA => ['danger', 'Rechazada'],
            self::VENCIDA   => ['secondary', 'Vencida'],
        ];

        [$color, $texto] = $badges[$estado] ?? ['secondary', (string) $estado];

        return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
    }
}
