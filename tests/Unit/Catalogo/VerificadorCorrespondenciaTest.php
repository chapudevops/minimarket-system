<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\Imagenes\CandidataImagen;
use App\Catalogo\Imagenes\EstadoFoto;
use App\Catalogo\Imagenes\VerificadorCorrespondencia;
use App\Catalogo\NormalizadorMarca;
use App\Catalogo\NormalizadorPresentacion;
use App\Models\Producto;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Que la foto sea de ESTE producto.
 *
 * Es el error caro del enriquecimiento: si "Coca-Cola Original 500 ml" recibe
 * la foto de la Zero o la de 1.5 L, el cajero cobra lo que no es. Ante
 * cualquier contradiccion la respuesta tiene que ser REVISAR, nunca VERIFICADA.
 */
class VerificadorCorrespondenciaTest extends TestCase
{
    private function verificador(): VerificadorCorrespondencia
    {
        return new VerificadorCorrespondencia(
            new NormalizadorMarca(['cocacola' => 'Coca-Cola', 'gloria' => 'Gloria']),
            new NormalizadorPresentacion(),
        );
    }

    private function producto(array $campos = []): Producto
    {
        $producto = new Producto();

        foreach (array_merge([
            'codigo_interno' => 'BEB-GAS-000001',
            'codigo_barras' => '7750182001234',
            'descripcion' => 'Coca Cola Original 500 ml',
            'marca' => 'Coca-Cola',
            'presentacion' => '500 ml',
        ], $campos) as $k => $v) {
            $producto->{$k} = $v;
        }

        return $producto;
    }

    private function candidata(array $campos = []): CandidataImagen
    {
        return CandidataImagen::desdeArray('FUENTE_PRUEBA', array_merge([
            'url' => 'https://ejemplo.test/coca-500.jpg',
            'marca' => 'Coca-Cola',
            'nombre' => 'Coca Cola Original',
            'presentacion' => '500 ml',
            'ean' => '7750182001234',
        ], $campos));
    }

    #[Test]
    public function todo_coincide_y_la_imagen_queda_verificada(): void
    {
        $veredicto = $this->verificador()->verificar($this->producto(), $this->candidata());

        $this->assertSame(EstadoFoto::VERIFICADA, $veredicto['estado']);
        $this->assertSame([], $veredicto['motivos']);
    }

    #[Test]
    public function un_ean_distinto_no_se_da_por_bueno(): void
    {
        // Que la fuente devuelva algo para un numero no alcanza.
        $veredicto = $this->verificador()->verificar(
            $this->producto(),
            $this->candidata(['ean' => '7750182009999'])
        );

        $this->assertSame(EstadoFoto::REVISAR, $veredicto['estado']);
        $this->assertStringContainsString('EAN', $veredicto['motivos'][0]);
    }

    #[Test]
    public function una_marca_distinta_manda_a_revisar(): void
    {
        $veredicto = $this->verificador()->verificar(
            $this->producto(),
            $this->candidata(['marca' => 'Pepsi', 'nombre' => 'Pepsi Original'])
        );

        $this->assertSame(EstadoFoto::REVISAR, $veredicto['estado']);
    }

    #[Test]
    public function la_presentacion_distinta_manda_a_revisar(): void
    {
        // El caso del enunciado: la de 1.5 L no puede quedarse con la foto de
        // la de 500 ml.
        $veredicto = $this->verificador()->verificar(
            $this->producto(),
            $this->candidata(['presentacion' => '1.5 L', 'nombre' => 'Coca Cola Original'])
        );

        $this->assertSame(EstadoFoto::REVISAR, $veredicto['estado']);
        $this->assertStringContainsString('presentación', $veredicto['motivos'][0]);
    }

    #[Test]
    public function la_variante_distinta_manda_a_revisar(): void
    {
        // Misma marca, mismo tamano, mismo EAN devuelto: solo cambia que una
        // es Zero. Es el falso positivo mas dificil de ver.
        $veredicto = $this->verificador()->verificar(
            $this->producto(),
            $this->candidata(['nombre' => 'Coca Cola Zero'])
        );

        $this->assertSame(EstadoFoto::REVISAR, $veredicto['estado']);
        $this->assertStringContainsString('variante', $veredicto['motivos'][0]);
    }

    #[Test]
    public function distingue_las_variantes_de_la_leche(): void
    {
        $entera = $this->producto([
            'descripcion' => 'Leche Gloria Entera 400 g', 'marca' => 'Gloria',
            'presentacion' => '400 g', 'codigo_barras' => '7751271000011',
        ]);

        $veredicto = $this->verificador()->verificar($entera, $this->candidata([
            'marca' => 'Gloria', 'nombre' => 'Leche Gloria Sin Lactosa',
            'presentacion' => '400 g', 'ean' => '7751271000011',
        ]));

        $this->assertSame(EstadoFoto::REVISAR, $veredicto['estado']);
    }

    #[Test]
    public function sin_url_no_hay_imagen(): void
    {
        $veredicto = $this->verificador()->verificar($this->producto(), $this->candidata(['url' => '']));

        $this->assertSame(EstadoFoto::SIN_IMAGEN, $veredicto['estado']);
    }

    #[Test]
    public function la_grafia_de_la_marca_no_genera_falsos_negativos(): void
    {
        // "COCA COLA" y "Coca-Cola" son la misma marca: no puede mandarse a
        // revisar un caso correcto solo por como esta escrito.
        $veredicto = $this->verificador()->verificar(
            $this->producto(),
            $this->candidata(['marca' => 'COCA COLA', 'presentacion' => '500ML'])
        );

        $this->assertSame(EstadoFoto::VERIFICADA, $veredicto['estado']);
    }
}
