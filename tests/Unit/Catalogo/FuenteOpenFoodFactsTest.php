<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\Imagenes\Fuentes\ClienteOpenFoodFacts;
use App\Catalogo\Imagenes\Fuentes\FuenteOpenFoodFacts;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El cliente de Open Food Facts, contra respuestas guardadas.
 *
 * Ningun test sale a internet: las respuestas viven en
 * tests/Fixtures/openfoodfacts/. Un test que depende de que OFF este arriba no
 * prueba nuestro codigo, prueba la conexion — y ademas gastaria el limite de
 * 15 peticiones por minuto que ellos publican.
 */
class FuenteOpenFoodFactsTest extends TestCase
{
    private function fixture(string $nombre): string
    {
        return file_get_contents(base_path("tests/Fixtures/openfoodfacts/{$nombre}.json"));
    }

    /** @param array<string,array{0:int,1:?string}> $porUrl */
    private function fuente(callable $descargar): FuenteOpenFoodFacts
    {
        return new FuenteOpenFoodFacts(new ClienteOpenFoodFacts($descargar(...)));
    }

    #[Test]
    public function mapea_la_respuesta_de_off_a_una_candidata(): void
    {
        $fuente = $this->fuente(fn (string $url) => [200, $this->fixture('coca-cola-500')]);

        $candidata = $fuente->paraCategoria('BEBIDAS')->buscarPorEan('7750182001234');

        $this->assertNotNull($candidata);
        $this->assertSame('OPENFOODFACTS', $candidata->fuente);
        $this->assertStringContainsString('front_es', $candidata->url);
        $this->assertSame('7750182001234', $candidata->ean);
        // Los tres campos que hacen posible verificar la correspondencia.
        $this->assertSame('Coca-Cola', $candidata->marca);
        $this->assertSame('Coca-Cola Sabor Original', $candidata->nombre);
        $this->assertSame('500 ml', $candidata->presentacion);
    }

    #[Test]
    public function toma_solo_la_primera_marca_de_la_lista(): void
    {
        // OFF guarda "Coca-Cola, The Coca-Cola Company". Para comparar contra
        // nuestro catalogo alcanza y sobra con la primera.
        $fuente = $this->fuente(fn (string $url) => [200, $this->fixture('coca-cola-500')]);

        $this->assertSame('Coca-Cola', $fuente->buscarPorEan('7750182001234')->marca);
    }

    #[Test]
    public function un_status_cero_significa_que_no_lo_conoce(): void
    {
        // OFF responde HTTP 200 con status 0 cuando no tiene el codigo. Tratarlo
        // como respuesta valida daria una candidata vacia.
        $fuente = $this->fuente(fn (string $url) => [200, $this->fixture('no-encontrado')]);

        $this->assertNull($fuente->buscarPorEan('0000000000000'));
    }

    #[Test]
    public function un_404_no_es_un_error_reintentable(): void
    {
        $intentos = 0;
        $fuente = $this->fuente(function (string $url) use (&$intentos) {
            $intentos++;

            return [404, null];
        });

        $this->assertNull($fuente->buscarPorEan('7750182009999'));
        $this->assertSame(1, $intentos, 'reintentó un código que la fuente simplemente no tiene');
    }

    #[Test]
    public function un_producto_sin_foto_frontal_no_devuelve_candidata(): void
    {
        // Existe en OFF pero nadie le cargó la imagen. No es un error.
        $fuente = $this->fuente(fn (string $url) => [200, $this->fixture('sin-imagen')]);

        $this->assertNull($fuente->paraCategoria('LACTEOS')->buscarPorEan('7751271000011'));
    }

    #[Test]
    public function un_json_invalido_se_reintenta_y_no_revienta(): void
    {
        $intentos = 0;
        $fuente = $this->fuente(function (string $url) use (&$intentos) {
            $intentos++;

            return [200, 'esto no es json'];
        });

        $this->assertNull($fuente->buscarPorEan('7750182001234'));
        $this->assertSame(3, $intentos, 'debía agotar los tres intentos');
    }

    #[Test]
    public function enruta_al_proyecto_hermano_segun_la_categoria(): void
    {
        $hosts = [];
        $fuente = $this->fuente(function (string $url) use (&$hosts) {
            $hosts[] = parse_url($url, PHP_URL_HOST);

            return [404, null];
        });

        foreach (['BEBIDAS' => 'world.openfoodfacts.org',
            'HIGIENE PERSONAL' => 'world.openbeautyfacts.org',
            'LIMPIEZA' => 'world.openproductsfacts.org',
            'MASCOTAS' => 'world.openpetfoodfacts.org'] as $categoria => $esperado) {
            $fuente->paraCategoria($categoria)->buscarPorEan('7750182001234');
            $this->assertSame($esperado, array_pop($hosts), "falló con {$categoria}");
        }
    }

    #[Test]
    public function una_categoria_desconocida_va_a_alimentos(): void
    {
        $fuente = $this->fuente(fn (string $url) => [404, null]);

        $this->assertSame('world.openfoodfacts.org', $fuente->paraCategoria('LO QUE SEA')->host());
    }

    #[Test]
    public function el_agente_tiene_el_formato_que_off_exige(): void
    {
        // OFF pide "AppName/Version (ContactEmail)" y con eso identifica a quien
        // consulta. Un agente generico o falseado es exactamente lo que no
        // corresponde hacer.
        $this->assertMatchesRegularExpression(
            '#^[\w-]+/[\d.]+ \([^)]+@[^)]+\)$#',
            ClienteOpenFoodFacts::AGENTE
        );
    }

    #[Test]
    public function la_pausa_respeta_el_limite_publicado_de_off(): void
    {
        // 15 peticiones por minuto = 4 s como minimo. Superarlo puede costar el
        // acceso por IP, asi que el margen es deliberado.
        $porMinuto = 60 / (ClienteOpenFoodFacts::PAUSA / 1_000_000);

        $this->assertLessThanOrEqual(15, $porMinuto, 'la pausa permite superar el límite de OFF');
    }
}
