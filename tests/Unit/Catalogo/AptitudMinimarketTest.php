<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\AptitudMinimarket;
use App\Catalogo\Taxonomia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Que entra al surtido de un minimarket y que no.
 *
 * El RAW sale de supermercados que tambien venden secadoras de pelo y laptops.
 * Lo que se protege aca es que el filtro no clasifique por palabras sueltas:
 * "Cono Sur Bicicleta" es un vino, no una bicicleta.
 */
class AptitudMinimarketTest extends TestCase
{
    private function fila(string $categoria, string $subcategoria, string $descripcion, string $presentacion = ''): array
    {
        return compact('categoria', 'subcategoria', 'descripcion', 'presentacion');
    }

    #[Test]
    public function un_producto_normal_de_gondola_es_apto(): void
    {
        $filtro = new AptitudMinimarket();

        foreach ([
            ['BEBIDAS', 'GASEOSAS', 'Gaseosa Coca Cola Original 500 ml'],
            ['LIMPIEZA', 'DETERGENTES', 'Detergente Bolivar Floral 780 g'],
            ['HIGIENE PERSONAL', 'SHAMPOO Y ACONDICIONADOR', 'Shampoo Head & Shoulders 375 ml'],
        ] as [$c, $s, $d]) {
            $this->assertSame(AptitudMinimarket::APTO, $filtro->evaluar($this->fila($c, $s, $d)), $d);
        }
    }

    #[Test]
    public function un_electrodomestico_en_gondola_de_no_alimentos_queda_fuera(): void
    {
        $filtro = new AptitudMinimarket();

        $this->assertSame(
            AptitudMinimarket::FUERA,
            $filtro->evaluar($this->fila('HIGIENE PERSONAL', 'SHAMPOO Y ACONDICIONADOR', 'Secadora Gama Eolic Mini 1600 Watts'))
        );
        $this->assertStringContainsString('secadora', $filtro->motivo());
    }

    #[Test]
    public function una_palabra_de_electrodomestico_en_alimentos_es_una_marca(): void
    {
        $filtro = new AptitudMinimarket();

        // El falso positivo que motivo la regla: es un vino chileno, no una
        // bicicleta. Clasificar por palabras sueltas lo habria descartado.
        $this->assertSame(
            AptitudMinimarket::APTO,
            $filtro->evaluar($this->fila('LICORES', 'VINO', 'Vino Tinto Carmenere Cono Sur Bicicleta Reserva 750 ml'))
        );
    }

    #[Test]
    public function un_producto_para_el_electrodomestico_va_a_revisar_y_no_se_descarta(): void
    {
        $filtro = new AptitudMinimarket();

        // "Limpia Lavadoras" es un producto de limpieza perfectamente normal.
        // No es la lavadora. Lo decide una persona, no el filtro.
        $this->assertSame(
            AptitudMinimarket::REVISAR,
            $filtro->evaluar($this->fila('LIMPIEZA', 'LEJIAS Y DESINFECTANTES', 'Limpia Lavadoras Dr. Beckmann 250 ml'))
        );
    }

    #[Test]
    public function el_plural_no_arrastra_palabras_que_solo_empiezan_igual(): void
    {
        $filtro = new AptitudMinimarket();

        // "Tabletas" contiene "tablet". Sin limite de palabra, un medicamento
        // quedaba clasificado como electronica y con un motivo falso.
        $this->assertNotSame(
            AptitudMinimarket::FUERA,
            $filtro->evaluar($this->fila('HIGIENE PERSONAL', 'SHAMPOO Y ACONDICIONADOR', 'Minoxidil 2.5Mg 60 Tabletas'))
        );

        // Pero el plural real si tiene que cazar.
        $this->assertSame(
            AptitudMinimarket::FUERA,
            $filtro->evaluar($this->fila('LIMPIEZA', 'UTILES DE LIMPIEZA', 'Aspiradoras portatiles para auto'))
        );
    }

    #[Test]
    public function una_subcategoria_fuera_de_la_taxonomia_no_entra(): void
    {
        $filtro = new AptitudMinimarket();
        $taxonomia = Taxonomia::desdeArchivo();

        $this->assertSame(
            AptitudMinimarket::FUERA,
            $filtro->evaluar($this->fila('ELECTRO', 'TELEVISORES', 'Televisor LG 55 pulgadas'), $taxonomia)
        );
        $this->assertStringContainsString('taxonomía', $filtro->motivo());
    }

    #[Test]
    public function el_formato_de_mayoreo_va_a_revisar(): void
    {
        $filtro = new AptitudMinimarket();

        $this->assertSame(
            AptitudMinimarket::REVISAR,
            $filtro->evaluar($this->fila('ABARROTES', 'ARROZ', 'Arroz Costeño Extra saco x 50 kg', 'saco x 50 kg'))
        );
    }

    #[Test]
    public function un_producto_apto_no_lleva_motivo(): void
    {
        $filtro = new AptitudMinimarket();
        $filtro->evaluar($this->fila('BEBIDAS', 'GASEOSAS', 'Inca Kola 1.5 L'));

        $this->assertSame('', $filtro->motivo());
    }
}
