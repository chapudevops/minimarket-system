<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\NormalizadorPresentacion;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cada supermercado escribe el mismo envase distinto. Estos casos son los que
 * aparecen de verdad en las fichas de Tottus, Metro y Plaza Vea.
 */
class NormalizadorPresentacionTest extends TestCase
{
    private NormalizadorPresentacion $normalizador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizador = new NormalizadorPresentacion();
    }

    #[Test]
    public function mililitros_se_escriben_siempre_igual(): void
    {
        foreach (['500ML', '500 ml', '500 Ml', '500ml', '500 MILILITROS', '500 cc'] as $variante) {
            $this->assertSame('500 ml', $this->normalizador->normalizar($variante), "fallo con: {$variante}");
        }
    }

    #[Test]
    public function el_litro_va_en_mayuscula_y_con_punto_decimal(): void
    {
        // "L" en mayuscula porque en minuscula se confunde con el digito 1, y
        // punto decimal porque la coma rompe cualquier CSV.
        foreach (['1,5L', '1.5 LT', '1.50 litros', '1.5lts', '1.5 L'] as $variante) {
            $this->assertSame('1.5 L', $this->normalizador->normalizar($variante), "fallo con: {$variante}");
        }
    }

    #[Test]
    public function gramos_y_kilos_se_abrevian(): void
    {
        $this->assertSame('400 g', $this->normalizador->normalizar('400 GR'));
        $this->assertSame('400 g', $this->normalizador->normalizar('400gramos'));
        $this->assertSame('1 kg', $this->normalizador->normalizar('1 KG.'));
        $this->assertSame('5 kg', $this->normalizador->normalizar('5 kilos'));
    }

    #[Test]
    public function el_envase_se_conserva_junto_al_contenido(): void
    {
        $this->assertSame('lata 355 ml', $this->normalizador->normalizar('LATA 355ML'));
        $this->assertSame('botella 1.5 L', $this->normalizador->normalizar('Botella 1,5 Lt'));
        $this->assertSame('bolsa 780 g', $this->normalizador->normalizar('BOLSA 780 GR'));
    }

    #[Test]
    public function el_multiplicador_siempre_queda_delante(): void
    {
        foreach (['PACK X 6', '6 PACK', 'pack de 6', 'Pack x6'] as $variante) {
            $this->assertSame('pack x6', $this->normalizador->normalizar($variante), "fallo con: {$variante}");
        }

        $this->assertSame('x6 355 ml', $this->normalizador->normalizar('6x355ml'));
        $this->assertSame('x15 un', $this->normalizador->normalizar('X 15 UNIDADES'));
    }

    #[Test]
    public function un_decimal_no_se_confunde_con_un_multiplicador(): void
    {
        // "1.5 x 2" no debe partirse en "1.x5 2".
        $this->assertSame('1.5 L x2', $this->normalizador->normalizar('1.5 L x 2'));
    }

    #[Test]
    public function lo_que_no_se_reconoce_se_deja_intacto(): void
    {
        // Preferimos una presentacion sin tocar antes que adivinar una unidad.
        $this->assertSame('surtido especial', $this->normalizador->normalizar('SURTIDO  ESPECIAL'));

        // "formato" y "aprox" son ruido de ficha, no parte del envase.
        $this->assertSame('500 g', $this->normalizador->normalizar('Contenido neto 500 g aprox'));
    }

    #[Test]
    public function sin_presentacion_devuelve_null(): void
    {
        $this->assertNull($this->normalizador->normalizar(null));
        $this->assertNull($this->normalizador->normalizar('   '));
    }

    #[Test]
    public function la_clave_iguala_variantes_de_la_misma_presentacion(): void
    {
        $this->assertSame(
            $this->normalizador->clave('500ML'),
            $this->normalizador->clave('500 Ml')
        );

        // 1.5 L y 15 L no son lo mismo: el punto decimal sobrevive a la clave.
        $this->assertNotSame(
            $this->normalizador->clave('1.5 L'),
            $this->normalizador->clave('15 L')
        );
    }
}
