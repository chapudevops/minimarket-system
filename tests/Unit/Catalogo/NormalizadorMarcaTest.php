<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\NormalizadorMarca;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Si dos grafias de la misma marca no colapsan, el detector de duplicados deja
 * pasar el producto dos veces y el catalogo nace roto. Por eso las variantes
 * quedan fijadas aca.
 */
class NormalizadorMarcaTest extends TestCase
{
    private function normalizador(): NormalizadorMarca
    {
        return new NormalizadorMarca([
            'cocacola'         => 'Coca-Cola',
            'incakola'         => 'Inca Kola',
            'inkakola'         => 'Inca Kola',
            'headshoulders'    => 'Head & Shoulders',
            'hs'               => 'Head & Shoulders',
            'nestle'           => 'Nestlé',
        ]);
    }

    #[Test]
    public function mayusculas_guiones_y_espacios_son_la_misma_marca(): void
    {
        $marcas = $this->normalizador();

        $variantes = ['Coca Cola', 'Coca-Cola', 'COCA COLA', 'coca  cola', ' Coca-Cola '];

        foreach ($variantes as $variante) {
            $this->assertSame('cocacola', $marcas->clave($variante), "fallo con: {$variante}");
            $this->assertSame('Coca-Cola', $marcas->normalizar($variante), "fallo con: {$variante}");
        }
    }

    #[Test]
    public function las_tildes_no_separan_marcas(): void
    {
        $marcas = $this->normalizador();

        $this->assertSame($marcas->clave('Nestle'), $marcas->clave('Nestlé'));
        // La grafia guardada es la oficial, con tilde.
        $this->assertSame('Nestlé', $marcas->normalizar('NESTLE'));
    }

    #[Test]
    public function un_alias_colapsa_con_su_marca_oficial(): void
    {
        $marcas = $this->normalizador();

        // Sin el diccionario "H&S" y "Head & Shoulders" serian dos productos:
        // no hay forma de deducir la equivalencia solo normalizando texto.
        $this->assertSame($marcas->clave('Head & Shoulders'), $marcas->clave('H&S'));
        $this->assertSame('Head & Shoulders', $marcas->normalizar('h&s'));

        $this->assertSame($marcas->clave('Inca Kola'), $marcas->clave('Inka Kola'));
    }

    #[Test]
    public function una_marca_desconocida_se_conserva_y_se_reporta(): void
    {
        $marcas = $this->normalizador();

        // No se descarta ni se fuerza contra el diccionario: se capitaliza y
        // se anota para que alguien la revise.
        $this->assertSame('Marca Que No Existe', $marcas->normalizar('MARCA QUE NO EXISTE'));
        $this->assertFalse($marcas->conoce('MARCA QUE NO EXISTE'));
        $this->assertContains('marcaquenoexiste', $marcas->desconocidas());
    }

    #[Test]
    public function una_marca_vacia_devuelve_null(): void
    {
        $marcas = $this->normalizador();

        $this->assertNull($marcas->normalizar(null));
        $this->assertNull($marcas->normalizar('   '));
        $this->assertSame('', $marcas->clave(null));
    }

    #[Test]
    public function el_diccionario_del_proyecto_carga_y_unifica(): void
    {
        $marcas = NormalizadorMarca::desdeArchivo();

        $this->assertSame('Coca-Cola', $marcas->normalizar('COCA COLA'));
        $this->assertSame('Inca Kola', $marcas->normalizar('inca kola'));
        $this->assertSame('D\'Onofrio', $marcas->normalizar('DONOFRIO'));
    }
}
