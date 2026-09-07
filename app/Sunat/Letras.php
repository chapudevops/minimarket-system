<?php

namespace App\Sunat;

/**
 * Monto en letras para la leyenda 1000, que SUNAT exige en todo comprobante.
 * Formato peruano: "SIETE CON 50/100 SOLES".
 */
class Letras
{
    private const UNIDADES = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS',
        'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
    ];

    private const DECENAS = [
        2 => 'VEINTI', 3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA',
        6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA',
    ];

    private const CENTENAS = [
        1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS',
        5 => 'QUINIENTOS', 6 => 'SEISCIENTOS', 7 => 'SETECIENTOS',
        8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS',
    ];

    public static function deMonto(float $monto, string $moneda = 'SOLES'): string
    {
        $entera = (int) floor(abs($monto));
        $centimos = (int) round((abs($monto) - $entera) * 100);

        // El redondeo de los centimos puede arrastrar una unidad.
        if ($centimos === 100) {
            $entera++;
            $centimos = 0;
        }

        return sprintf('%s CON %02d/100 %s', self::deEntero($entera), $centimos, $moneda);
    }

    public static function deEntero(int $n): string
    {
        if ($n === 0) {
            return 'CERO';
        }

        if ($n >= 1000000) {
            $millones = intdiv($n, 1000000);
            $resto = $n % 1000000;
            $texto = ($millones === 1 ? 'UN MILLON' : self::deEntero($millones) . ' MILLONES');

            // Sin este corte, un resto de cero agregaria un "CERO" sobrante:
            // 1000000 quedaba como "UN MILLON CERO".
            return $resto === 0 ? $texto : $texto . ' ' . self::deEntero($resto);
        }

        if ($n >= 1000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $texto = ($miles === 1 ? 'MIL' : self::deEntero($miles) . ' MIL');

            return $resto === 0 ? $texto : $texto . ' ' . self::deEntero($resto);
        }

        return self::hastaNovecientos($n);
    }

    private static function hastaNovecientos(int $n): string
    {
        if ($n === 100) {
            return 'CIEN';
        }

        $partes = [];

        $centena = intdiv($n, 100);
        if ($centena > 0) {
            $partes[] = self::CENTENAS[$centena];
        }

        $resto = $n % 100;

        if ($resto <= 20) {
            if ($resto > 0) {
                $partes[] = self::UNIDADES[$resto];
            }

            return implode(' ', $partes);
        }

        $decena = intdiv($resto, 10);
        $unidad = $resto % 10;

        if ($decena === 2) {
            // VEINTIUNO, VEINTIDOS: se escriben pegados.
            $partes[] = self::DECENAS[2] . strtolower(self::UNIDADES[$unidad]);
            return strtoupper(implode(' ', $partes));
        }

        $partes[] = self::DECENAS[$decena] . ($unidad > 0 ? ' Y ' . self::UNIDADES[$unidad] : '');

        return implode(' ', $partes);
    }
}
