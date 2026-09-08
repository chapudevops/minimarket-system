<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\Fuentes\ExtractorVtex;
use App\Catalogo\Fuentes\MapeoFuentes;
use App\Catalogo\Fuentes\Robots;
use PHPUnit\Framework\TestCase;

/**
 * El extractor, contra respuestas guardadas.
 *
 * Ningun test de este archivo sale a internet: la forma de la respuesta VTEX
 * viaja como fixture. Un test que depende de que Plaza Vea este arriba no
 * prueba nuestro codigo, prueba la conexion.
 */
class ExtractorVtexTest extends TestCase
{
    private function mapeo(): MapeoFuentes
    {
        return new MapeoFuentes([
            ['fuente' => 'PLAZAVEA', 'ruta_fuente' => '/Bebidas/Gaseosas/', 'categoria' => 'BEBIDAS', 'subcategoria' => 'GASEOSAS', 'producto_tipo' => ''],
            ['fuente' => 'PLAZAVEA', 'ruta_fuente' => '/Abarrotes/Galletas y Golosinas/Galletas Dulces/', 'categoria' => 'GALLETAS Y DULCES', 'subcategoria' => 'GALLETAS', 'producto_tipo' => ''],
            ['fuente' => 'PLAZAVEA', 'ruta_fuente' => '/Abarrotes/Galletas y Golosinas/Caramelos y Chupetes/', 'categoria' => 'GALLETAS Y DULCES', 'subcategoria' => 'GOLOSINAS', 'producto_tipo' => ''],
        ]);
    }

    /** Una respuesta VTEX como la devuelve la API, recortada a lo que se usa. */
    private function producto(array $cambios = []): array
    {
        return array_replace_recursive([
            'productName' => 'Gaseosa COCA COLA Sin Azúcar Botella 1.5L',
            'brand'       => 'COCA COLA',
            'linkText'    => 'gaseosa-coca-cola-sin-azucar-botella-1-5l-123456',
            'categories'  => ['/Bebidas/Gaseosas/Gaseosas Familiares/', '/Bebidas/Gaseosas/', '/Bebidas/'],
            'items'       => [[
                'itemId'       => '12345',
                'nameComplete' => 'Gaseosa COCA COLA Sin Azúcar Botella 1.5L',
                'ean'          => '7801610350355',
                'sellers'      => [['commertialOffer' => ['Price' => 6.5, 'ListPrice' => 7.2]]],
            ]],
        ], $cambios);
    }

    public function test_convierte_un_producto_en_una_fila_raw(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $filas = $extractor->filasDe($this->producto(), 'www.plazavea.com.pe', '2026-09-08');

        $this->assertCount(1, $filas);
        $fila = $filas[0];

        $this->assertSame('PLAZAVEA', $fila['fuente']);
        $this->assertSame('BEBIDAS', $fila['categoria']);
        $this->assertSame('GASEOSAS', $fila['subcategoria']);
        $this->assertSame('COCA COLA', $fila['marca']);
        $this->assertSame('7801610350355', $fila['codigo_barras']);
        $this->assertSame('6.50', $fila['precio_referencia']);
        $this->assertSame('2026-09-08', $fila['fecha_consulta']);
        $this->assertStringStartsWith('https://www.plazavea.com.pe/', $fila['url_fuente']);
    }

    public function test_usa_la_ruta_mas_especifica_que_tenga_regla(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $galleta = $extractor->filasDe($this->producto([
            'productName' => 'Galleta Dulce FIELD Vainilla 6un',
            'categories'  => ['/Abarrotes/Galletas y Golosinas/Galletas Dulces/', '/Abarrotes/Galletas y Golosinas/', '/Abarrotes/'],
            'items'       => [['nameComplete' => 'Galleta Dulce FIELD Vainilla 6un']],
        ]), 'www.plazavea.com.pe', '2026-09-08');

        $caramelo = $extractor->filasDe($this->producto([
            'productName' => 'Caramelo AMBROSOLI Surtido 100g',
            'categories'  => ['/Abarrotes/Galletas y Golosinas/Caramelos y Chupetes/', '/Abarrotes/Galletas y Golosinas/', '/Abarrotes/'],
            'items'       => [['nameComplete' => 'Caramelo AMBROSOLI Surtido 100g']],
        ]), 'www.plazavea.com.pe', '2026-09-08');

        // La misma categoria de origen cae en dos subcategorias distintas.
        $this->assertSame('GALLETAS', $galleta[0]['subcategoria']);
        $this->assertSame('GOLOSINAS', $caramelo[0]['subcategoria']);
    }

    public function test_una_categoria_sin_regla_se_descarta_no_se_adivina(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $filas = $extractor->filasDe($this->producto([
            'categories' => ['/Electrohogar/Cocinas/', '/Electrohogar/'],
        ]), 'www.plazavea.com.pe', '2026-09-08');

        $this->assertSame([], $filas);
        $this->assertSame(1, $extractor->estadisticas()['descartados_sin_categoria']);
    }

    public function test_descarta_los_packs_armados_por_la_tienda(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        foreach (['Pack Gaseosa COCA COLA 2un', 'Combo Gaseosa + Galleta', 'Sixpack Cerveza'] as $nombre) {
            $filas = $extractor->filasDe($this->producto([
                'items' => [['nameComplete' => $nombre]],
            ]), 'www.plazavea.com.pe', '2026-09-08');

            $this->assertSame([], $filas, "{$nombre} no debería entrar al catálogo");
        }

        $this->assertSame(3, $extractor->estadisticas()['descartados_pack']);
    }

    public function test_un_ean_que_no_valida_queda_vacio_y_se_cuenta_aparte(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $filas = $extractor->filasDe($this->producto([
            'items' => [['ean' => '7801610350356']],
        ]), 'www.plazavea.com.pe', '2026-09-08');

        $this->assertSame('', $filas[0]['codigo_barras']);

        $stats = $extractor->estadisticas();
        $this->assertSame(1, $stats['ean_invalido']);
        $this->assertSame(0, $stats['con_ean']);
    }

    public function test_sin_ean_la_fila_entra_igual(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $filas = $extractor->filasDe($this->producto([
            'items' => [['ean' => '']],
        ]), 'www.plazavea.com.pe', '2026-09-08');

        // Un producto sin codigo de barras se vende igual: se busca por SKU o
        // por descripcion.
        $this->assertCount(1, $filas);
        $this->assertSame('', $filas[0]['codigo_barras']);
        $this->assertSame(1, $extractor->estadisticas()['sin_ean']);
    }

    public function test_nunca_deriva_precio_de_compra_del_precio_de_gondola(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $fila = $extractor->filasDe($this->producto(), 'www.plazavea.com.pe', '2026-09-08')[0];

        $this->assertSame('6.50', $fila['precio_referencia']);
        $this->assertArrayNotHasKey('precio_compra', $fila);
        $this->assertArrayNotHasKey('precio_venta', $fila);
    }

    public function test_extrae_la_presentacion_del_nombre_publicado(): void
    {
        $extractor = new ExtractorVtex('PLAZAVEA', $this->mapeo());

        $casos = [
            'Gaseosa COCA COLA Botella 1.5L'      => '1.5 L',
            'Aceite PRIMOR Botella 900ml'         => '900 ml',
            'Arroz COSTEÑO Extra Bolsa 5kg'       => '5 kg',
            'Producto sin medida reconocible'     => '',
        ];

        foreach ($casos as $nombre => $esperado) {
            $filas = $extractor->filasDe($this->producto([
                'items' => [['nameComplete' => $nombre]],
            ]), 'www.plazavea.com.pe', '2026-09-08');

            $this->assertSame($esperado, $filas[0]['presentacion'], $nombre);
        }
    }

    public function test_el_lector_de_robots_respeta_disallow(): void
    {
        $robots = Robots::de('ejemplo.test', fn () => "User-agent: *\nDisallow: /api/\nAllow: /api/sitemap/\n");

        $this->assertTrue($robots->accesible());
        $this->assertFalse($robots->permite('/api/catalog_system/pub/products/search'));
        $this->assertTrue($robots->permite('/api/sitemap/products.xml'));
        $this->assertTrue($robots->permite('/bebidas/gaseosas'));
    }

    public function test_sin_robots_legible_no_se_automatiza(): void
    {
        // Un 403 de Cloudflare delante del propio robots.txt ya dice bastante.
        $cloudflare = Robots::de('ejemplo.test', fn () => '<html><title>403</title></html>');
        $this->assertFalse($cloudflare->accesible());
        $this->assertFalse($cloudflare->permite('/'));

        $caido = Robots::de('ejemplo.test', fn () => null);
        $this->assertFalse($caido->accesible());
        $this->assertFalse($caido->permite('/'));
    }
}
