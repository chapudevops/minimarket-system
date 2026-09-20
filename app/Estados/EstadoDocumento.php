<?php

namespace App\Estados;

/**
 * Estado comercial de los documentos con ciclo de vida simple: notas de
 * credito, notas de debito, notas de venta, compras y guias de remision.
 *
 * Todos comparten el mismo par (existe / se invalido), asi que comparten
 * clase en vez de tener cinco enums identicos. Su estado SUNAT, cuando les
 * corresponde, vive aparte en EstadoSunat.
 */
class EstadoDocumento
{
    /** Emitido correctamente dentro del ERP. */
    public const REGISTRADA = 'REGISTRADA';

    /** Invalidado mediante un flujo permitido. */
    public const ANULADA = 'ANULADA';

    public const TODOS = [self::REGISTRADA, self::ANULADA];

    public const TRANSICIONES = [
        self::REGISTRADA => [self::ANULADA],
        self::ANULADA => [],
    ];

    public static function puedeTransicionar(?string $desde, string $hasta): bool
    {
        return in_array($hasta, self::TRANSICIONES[$desde] ?? [], true);
    }

    public static function badge(?string $estado): string
    {
        $badges = [
            self::REGISTRADA => ['success', 'Registrada'],
            self::ANULADA    => ['danger', 'Anulada'],
        ];

        [$color, $texto] = $badges[$estado] ?? ['secondary', (string) $estado];

        return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
    }
}
