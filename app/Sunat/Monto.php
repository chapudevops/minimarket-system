<?php

namespace App\Sunat;

/**
 * Aritmetica de importes con redondeo explicito.
 *
 * Las columnas son decimal(10,2), pero los calculos se hacian en float sin
 * redondear: el valor llegaba a la base con mas decimales y quedaba a criterio
 * de MySQL. SUNAT valida que los totales cuadren al centimo, asi que conviene
 * decidirlo en PHP y no delegarlo.
 */
class Monto
{
    public static function tasaIgv(): float
    {
        return (float) config('sunat.igv', 0.18);
    }

    public static function redondear(float|int|string $valor): float
    {
        return round((float) $valor, 2);
    }

    /**
     * Importes a partir de una base que NO incluye IGV.
     *
     * @return array{gravado: float, igv: float, total: float}
     */
    public static function agregarIgv(float $base, bool $grava = true): array
    {
        $gravado = self::redondear($base);
        $igv = $grava ? self::redondear($gravado * self::tasaIgv()) : 0.0;

        return [
            'gravado' => $gravado,
            'igv'     => $igv,
            // El total se arma sumando los redondeados, no redondeando la suma:
            // asi lo que se guarda cuadra exactamente con sus partes.
            'total'   => self::redondear($gravado + $igv),
        ];
    }

    /**
     * Importes a partir de un total que YA incluye IGV, que es el caso del
     * terminal: el precio de venta en gondola es el precio final.
     *
     * Solo se redondea el gravado y el IGV sale por diferencia, para que
     * gravado + igv devuelva el total exacto que se le cobro al cliente.
     *
     * @return array{gravado: float, igv: float, total: float}
     */
    public static function desagregarIgv(float $total, bool $grava = true): array
    {
        $total = self::redondear($total);

        if (! $grava) {
            return ['gravado' => $total, 'igv' => 0.0, 'total' => $total];
        }

        $gravado = self::redondear($total / (1 + self::tasaIgv()));

        return [
            'gravado' => $gravado,
            'igv'     => self::redondear($total - $gravado),
            'total'   => $total,
        ];
    }
}
