<?php

namespace App\Estados;

/**
 * Estado del envio electronico a SUNAT. NO es el estado del documento.
 *
 * Esta es la separacion que da sentido a todo el modulo: un comprobante puede
 * estar RECHAZADO por SUNAT y seguir siendo una venta valida en el ERP. Lo
 * contrario tambien: el negocio no se detiene porque SUNAT este caido, sin
 * configurar o sin responder.
 *
 * Antes estos valores eran strings sueltos repartidos por controllers, jobs,
 * modelos y vistas, con dos vocabularios distintos: EnviadorSunat emitia
 * OBSERVADO y GuiaRemision pintaba ENVIADO, que nadie escribia nunca.
 */
class EstadoSunat
{
    /** El documento no participa en facturacion electronica (nota de venta, cotizacion). */
    public const NO_APLICA = 'NO_APLICA';

    /** Participa, pero todavia no se intento enviar. Es el estado inicial. */
    public const NO_ENVIADO = 'NO_ENVIADO';

    /** Encolado para envio. */
    public const EN_COLA = 'EN_COLA';

    /** El job lo tomo y esta hablando con SUNAT. */
    public const ENVIANDO = 'ENVIANDO';

    /** SUNAT lo acepto (CDR codigo 0). */
    public const ACEPTADO = 'ACEPTADO';

    /** Aceptado con observaciones (codigo 0 con notas). Cuenta como aceptado. */
    public const OBSERVADO = 'OBSERVADO';

    /** Rechazo definitivo (codigos 2xxx y 3xxx). Reintentar no cambia nada. */
    public const RECHAZADO = 'RECHAZADO';

    /** Fallo reintentable: red, timeout, servicio caido, credenciales ausentes. */
    public const ERROR = 'ERROR';

    public const TODOS = [
        self::NO_APLICA, self::NO_ENVIADO, self::EN_COLA, self::ENVIANDO,
        self::ACEPTADO, self::OBSERVADO, self::RECHAZADO, self::ERROR,
    ];

    /** Estados finales: el job no debe reintentar. */
    public const FINALES = [self::ACEPTADO, self::OBSERVADO, self::RECHAZADO, self::NO_APLICA];

    /** SUNAT ya dio el visto bueno (con o sin observaciones). */
    public static function esAceptado(?string $estado): bool
    {
        return in_array($estado, [self::ACEPTADO, self::OBSERVADO], true);
    }

    /** Se puede volver a intentar el envio. */
    public static function esReintentable(?string $estado): bool
    {
        return ! in_array($estado, self::FINALES, true);
    }

    /**
     * Necesita que alguien lo regularice: SUNAT lo rechazo o fallo el envio.
     * Alimenta las alertas, no cambia el estado comercial del documento.
     */
    public static function necesitaAtencion(?string $estado): bool
    {
        return in_array($estado, [self::RECHAZADO, self::ERROR], true);
    }

    public static function badge(?string $estado): string
    {
        $badges = [
            self::NO_APLICA  => ['secondary', 'No aplica'],
            self::NO_ENVIADO => ['secondary', 'No enviado'],
            self::EN_COLA    => ['info', 'En cola'],
            self::ENVIANDO   => ['info', 'Enviando'],
            self::ACEPTADO   => ['success', 'Aceptado'],
            self::OBSERVADO  => ['warning', 'Observado'],
            self::RECHAZADO  => ['danger', 'Rechazado'],
            self::ERROR      => ['danger', 'Error de envío'],
        ];

        [$color, $texto] = $badges[$estado] ?? ['secondary', (string) $estado];

        return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
    }
}
