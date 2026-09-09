<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\Imagenes\DescargadorImagen;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Descarga, redimensionado y conversion a WebP.
 *
 * No sale a internet: la descarga se inyecta. Lo que se protege es que un POS
 * no termine con archivos de varios MB ni con un HTML de error guardado como
 * .webp, que es lo que pasa cuando se confia en el codigo HTTP y nada mas.
 */
class DescargadorImagenTest extends TestCase
{
    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directorio = sys_get_temp_dir().'/descargador-'.uniqid();
        mkdir($this->directorio, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directorio.'/productos/*') ?: [] as $archivo) {
            unlink($archivo);
        }

        @rmdir($this->directorio.'/productos');
        @rmdir($this->directorio);

        parent::tearDown();
    }

    private function png(int $ancho, int $alto): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 200, 30, 30));

        ob_start();
        imagepng($imagen);
        $binario = ob_get_clean();
        imagedestroy($imagen);

        return $binario;
    }

    private function descargador(callable $respuesta): DescargadorImagen
    {
        return new DescargadorImagen($this->directorio, $respuesta(...));
    }

    #[Test]
    public function guarda_la_imagen_como_webp_con_nombre_interno(): void
    {
        $png = $this->png(400, 400);
        $descargador = $this->descargador(fn (string $url) => [200, $png, 'image/png']);

        $ruta = $descargador->guardar('https://ejemplo.test/x.png', 42);

        // El nombre es nuestro, nunca el del tercero.
        $this->assertSame('productos/42.webp', $ruta);

        $archivo = $this->directorio.'/'.$ruta;
        $this->assertFileExists($archivo);
        $this->assertSame('image/webp', image_type_to_mime_type(getimagesize($archivo)[2]));
    }

    #[Test]
    public function achica_al_lado_maximo_conservando_la_proporcion(): void
    {
        $png = $this->png(1600, 900);
        $descargador = $this->descargador(fn (string $url) => [200, $png, 'image/png']);

        [$ancho, $alto] = getimagesize($this->directorio.'/'.$descargador->guardar('https://ejemplo.test/x.png', 7));

        $this->assertSame(DescargadorImagen::LADO_MAXIMO, $ancho);
        // 1600x900 -> 600x338: la proporcion 16:9 se mantiene.
        $this->assertSame(338, $alto);
    }

    #[Test]
    public function una_imagen_chica_no_se_agranda(): void
    {
        $png = $this->png(120, 80);
        $descargador = $this->descargador(fn (string $url) => [200, $png, 'image/png']);

        [$ancho, $alto] = getimagesize($this->directorio.'/'.$descargador->guardar('https://ejemplo.test/x.png', 9));

        $this->assertSame(120, $ancho);
        $this->assertSame(80, $alto);
    }

    #[Test]
    public function rechaza_lo_que_no_es_una_imagen(): void
    {
        // Una pagina de error con HTTP 200 es el caso real: guardarla como
        // .webp deja un archivo que el POS no puede pintar.
        $descargador = $this->descargador(fn (string $url) => [200, '<html>error</html>', 'text/html']);

        $this->expectException(RuntimeException::class);
        $descargador->guardar('https://ejemplo.test/x.png', 1);
    }

    #[Test]
    public function rechaza_un_cuerpo_corrupto_aunque_diga_ser_imagen(): void
    {
        // Content-Type correcto pero contenido basura: el juez final es GD.
        $descargador = $this->descargador(fn (string $url) => [200, 'no soy un png', 'image/png']);

        $this->expectException(RuntimeException::class);
        $descargador->guardar('https://ejemplo.test/x.png', 1);
    }

    #[Test]
    public function un_error_http_no_deja_archivo(): void
    {
        $descargador = $this->descargador(fn (string $url) => [500, null, null]);

        try {
            $descargador->guardar('https://ejemplo.test/x.png', 5);
            $this->fail('debía lanzar');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }

        $this->assertFileDoesNotExist($this->directorio.'/productos/5.webp');
    }

    #[Test]
    public function sin_content_type_decide_gd(): void
    {
        // Algunos CDN no mandan Content-Type. Si GD puede abrirlo, es imagen.
        $png = $this->png(200, 200);
        $descargador = $this->descargador(fn (string $url) => [200, $png, null]);

        $this->assertSame('productos/3.webp', $descargador->guardar('https://ejemplo.test/x', 3));
    }

    #[Test]
    public function el_peso_del_webp_es_razonable_para_un_pos(): void
    {
        $png = $this->png(1600, 1600);
        $descargador = $this->descargador(fn (string $url) => [200, $png, 'image/png']);

        $archivo = $this->directorio.'/'.$descargador->guardar('https://ejemplo.test/x.png', 11);

        // No necesitamos 4K en un mostrador.
        $this->assertLessThan(200 * 1024, filesize($archivo));
    }
}
