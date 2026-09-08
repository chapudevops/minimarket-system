<?php

namespace Tests\Unit\Sunat;

use App\Sunat\Letras;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El monto en letras es la leyenda 1000, obligatoria en todo comprobante.
 * Los casos borde de acá son los que rompen las implementaciones caseras.
 */
class LetrasTest extends TestCase
{
    #[Test]
    #[DataProvider('montos')]
    public function convierte_el_monto_a_letras(float $monto, string $esperado): void
    {
        $this->assertSame($esperado, Letras::deMonto($monto));
    }

    public static function montos(): array
    {
        return [
            'cero'                  => [0.00, 'CERO CON 00/100 SOLES'],
            'solo centimos'         => [0.90, 'CERO CON 90/100 SOLES'],
            'un centimo'            => [0.01, 'CERO CON 01/100 SOLES'],
            'decena simple'         => [7.50, 'SIETE CON 50/100 SOLES'],
            'veintiuno pegado'      => [21.00, 'VEINTIUNO CON 00/100 SOLES'],
            'veintiseis pegado'     => [26.00, 'VEINTISEIS CON 00/100 SOLES'],
            'decena con y'          => [31.00, 'TREINTA Y UNO CON 00/100 SOLES'],
            'cien exacto'           => [100.00, 'CIEN CON 00/100 SOLES'],
            'ciento y pico'         => [115.40, 'CIENTO QUINCE CON 40/100 SOLES'],
            // El caso que fallaba: el resto en cero agregaba un "CERO" sobrante.
            'mil redondo'           => [1000.00, 'MIL CON 00/100 SOLES'],
            'dos mil redondo'       => [2000.00, 'DOS MIL CON 00/100 SOLES'],
            'mil con centimos'      => [1000.50, 'MIL CON 50/100 SOLES'],
            'mil y pico'            => [1234.56, 'MIL DOSCIENTOS TREINTA Y CUATRO CON 56/100 SOLES'],
            'un millon redondo'     => [1000000.00, 'UN MILLON CON 00/100 SOLES'],
            'varios millones'       => [5000000.00, 'CINCO MILLONES CON 00/100 SOLES'],
        ];
    }

    #[Test]
    public function el_redondeo_de_centimos_no_desborda(): void
    {
        // 9.999 redondea a 10.00: los centimos no pueden quedar en 100.
        $this->assertSame('DIEZ CON 00/100 SOLES', Letras::deMonto(9.999));
    }

    #[Test]
    public function acepta_otra_moneda(): void
    {
        $this->assertSame('CINCO CON 00/100 DOLARES', Letras::deMonto(5.00, 'DOLARES'));
    }
}
