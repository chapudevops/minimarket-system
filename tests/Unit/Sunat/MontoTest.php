<?php

namespace Tests\Unit\Sunat;

use App\Sunat\Monto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SUNAT valida que los totales cuadren al centimo. Estos tests fijan esa
 * propiedad: las partes siempre suman el total, sin importar el redondeo.
 */
class MontoTest extends TestCase
{
    #[Test]
    public function la_tasa_de_igv_sale_de_la_configuracion(): void
    {
        config(['sunat.igv' => 0.18]);
        $this->assertSame(0.18, Monto::tasaIgv());

        // Si el dia de mañana cambia la tasa, no hay que tocar codigo.
        config(['sunat.igv' => 0.10]);
        $this->assertSame(0.10, Monto::tasaIgv());
    }

    #[Test]
    #[DataProvider('totalesConIgvIncluido')]
    public function desagregar_devuelve_partes_que_suman_el_total(float $total): void
    {
        $r = Monto::desagregarIgv($total);

        $this->assertSame(
            round($total, 2),
            round($r['gravado'] + $r['igv'], 2),
            "gravado + igv debe dar exactamente {$total}"
        );
        $this->assertSame(round($total, 2), $r['total']);
    }

    public static function totalesConIgvIncluido(): array
    {
        return [
            'centimos justos'   => [9.00],
            'con decimales'     => [7.50],
            'primo feo'         => [0.07],
            'redondeo al alza'  => [10.01],
            'monto grande'      => [12345.67],
            'un centimo'        => [0.01],
            'cero'              => [0.00],
        ];
    }

    #[Test]
    public function desagregar_calcula_el_igv_peruano(): void
    {
        $r = Monto::desagregarIgv(118.00);

        $this->assertSame(100.00, $r['gravado']);
        $this->assertSame(18.00, $r['igv']);
    }

    #[Test]
    public function agregar_igv_sobre_una_base_sin_impuesto(): void
    {
        $r = Monto::agregarIgv(100.00);

        $this->assertSame(100.00, $r['gravado']);
        $this->assertSame(18.00, $r['igv']);
        $this->assertSame(118.00, $r['total']);
    }

    #[Test]
    public function una_operacion_exonerada_no_paga_igv(): void
    {
        $desagregado = Monto::desagregarIgv(50.00, grava: false);
        $this->assertSame(50.00, $desagregado['gravado']);
        $this->assertSame(0.0, $desagregado['igv']);

        $agregado = Monto::agregarIgv(50.00, grava: false);
        $this->assertSame(0.0, $agregado['igv']);
        $this->assertSame(50.00, $agregado['total']);
    }

    #[Test]
    public function redondea_siempre_a_dos_decimales(): void
    {
        $this->assertSame(1.24, Monto::redondear(1.235));
        $this->assertSame(1.23, Monto::redondear(1.234));
        $this->assertSame(0.0, Monto::redondear(0.001));
    }
}
