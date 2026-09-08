<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\CodigoBarras;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * El validador de GTIN.
 *
 * La regla del proyecto es que un EAN inventado es peor que ninguno: si el
 * codigo esta mal, el escaner del mostrador trae otro producto y la venta sale
 * con el articulo equivocado. Estos tests fijan que "verificable" signifique
 * que el digito verificador cierra, no que el dato haya venido de una web.
 */
class CodigoBarrasTest extends TestCase
{
    /** GTIN reales tomados de catalogos publicos peruanos. */
    public static function gtinValidos(): array
    {
        return [
            'EAN-13 Perú (Coca Cola)'   => ['7801610350355'],
            'EAN-13 Perú (Schweppes)'   => ['7750182006699'],
            'EAN-13 Perú (Guaraná)'     => ['7753749001649'],
            'EAN-13 Uruguay (Pepsi)'    => ['7401005914638'],
            'EAN-13 Perú (Primor)'      => ['7750243067997'],
            'UPC-A 12 dígitos'          => ['036000291452'],
        ];
    }

    #[DataProvider('gtinValidos')]
    public function test_acepta_un_gtin_real(string $gtin): void
    {
        $this->assertSame($gtin, CodigoBarras::normalizar($gtin));
        $this->assertTrue(CodigoBarras::esValido($gtin));
    }

    public function test_rechaza_un_digito_verificador_que_no_cierra(): void
    {
        // 7750670022095 es real; cambiarle el ultimo digito lo invalida.
        $this->assertNotNull(CodigoBarras::normalizar('7750670022095'));
        $this->assertNull(CodigoBarras::normalizar('7750670022096'));
    }

    public static function basura(): array
    {
        return [
            'vacío'            => [''],
            'cero'             => ['0'],
            'ceros 12'         => ['000000000000'],
            'ceros 13'         => ['0000000000000'],
            'largo inválido'   => ['775067002209'],
            'demasiado corto'  => ['12345'],
            'solo letras'      => ['ABCDEFGHIJKLM'],
            'null'             => [null],
        ];
    }

    #[DataProvider('basura')]
    public function test_lo_que_no_es_un_gtin_devuelve_null(mixed $valor): void
    {
        $this->assertNull(CodigoBarras::normalizar($valor));
        $this->assertFalse(CodigoBarras::esValido($valor));
    }

    public function test_limpia_separadores_pero_no_inventa_digitos(): void
    {
        $this->assertSame('7750182006699', CodigoBarras::normalizar(' 7750182006699 '));
        $this->assertSame('7750182006699', CodigoBarras::normalizar('7750-182-006699'));

        // Limpiar no puede convertir algo invalido en valido.
        $this->assertNull(CodigoBarras::normalizar('7750-182-006698'));
    }

    public function test_conserva_los_ceros_iniciales(): void
    {
        // Un UPC-A con cero adelante es un codigo distinto del EAN-13 corto:
        // el escaner lee lo que esta impreso.
        $gtin = CodigoBarras::normalizar('036000291452');

        $this->assertSame('036000291452', $gtin);
        $this->assertSame(12, strlen((string) $gtin));
    }

    public function test_para_catalogo_descarta_los_gtin_8(): void
    {
        // 29798926 es un GTIN-8 valido, pero en estos catalogos ese largo es
        // un codigo interno del retailer (rostizados, combos de tienda). Como
        // codigo_barras haria que el escaner de otra tienda no lo encuentre.
        $this->assertTrue(CodigoBarras::esValido('29798926'));
        $this->assertNull(CodigoBarras::paraCatalogo('29798926'));

        $this->assertSame('7750182006699', CodigoBarras::paraCatalogo('7750182006699'));
    }

    public function test_no_existe_forma_de_generar_un_codigo(): void
    {
        $metodos = get_class_methods(CodigoBarras::class);

        foreach ($metodos as $metodo) {
            $this->assertDoesNotMatchRegularExpression(
                '/generar|crear|siguiente|nuevo/i',
                $metodo,
                "CodigoBarras::{$metodo}() suena a que fabrica códigos, y eso está prohibido"
            );
        }
    }

    public function test_para_catalogo_descarta_los_codigos_internos_de_tienda(): void
    {
        // GS1 reserva 02 y 20-29 para etiquetas impresas en el local. Cierran
        // el digito verificador pero solo significan algo dentro de ese
        // supermercado: aparecieron en un arroz y en una crema de avellanas de
        // Plaza Vea.
        foreach (['2200202353593', '2050044003671', '2200204274575'] as $interno) {
            $this->assertTrue(CodigoBarras::esValido($interno), "{$interno} debería cerrar el dígito");
            $this->assertNull(CodigoBarras::paraCatalogo($interno), "{$interno} no debería entrar al catálogo");
        }

        // Un EAN de fabricante con el mismo largo sí entra.
        $this->assertSame('7750670022095', CodigoBarras::paraCatalogo('7750670022095'));
    }

    public function test_el_origen_es_informativo(): void
    {
        $this->assertSame('Perú', CodigoBarras::origen('7750182006699'));
        $this->assertSame('Chile', CodigoBarras::origen('7801610350355'));
        $this->assertNull(CodigoBarras::origen(null));
    }
}
