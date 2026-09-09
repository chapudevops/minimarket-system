<?php

namespace Tests\Feature;

use App\Catalogo\Imagenes\EnriquecedorImagenes;
use App\Catalogo\Imagenes\EstadoFoto;
use App\Catalogo\Imagenes\RegistroFuentesImagen;
use App\Catalogo\Imagenes\VerificadorCorrespondencia;
use App\Catalogo\NormalizadorMarca;
use App\Catalogo\NormalizadorPresentacion;
use App\Models\Producto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\Support\FuenteImagenFalsa;
use Tests\TestCase;

/**
 * El sondeo tiene que ser inocuo.
 *
 * Es lo que se corre ANTES de decidir si vale la pena enriquecer todo el
 * catalogo, y por eso no puede dejar rastro: si el diagnostico ya modifico
 * productos, la decision se toma sobre una base que ya cambio.
 */
class SondeoImagenesTest extends TestCase
{
    use CreaEscenarioDeVenta, DatabaseTransactions;

    private array $temporales = [];

    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) {
            foreach (glob($ruta.'/productos/*') ?: [] as $archivo) {
                unlink($archivo);
            }
            @rmdir($ruta.'/productos');
            @rmdir($ruta);
        }

        parent::tearDown();
    }

    private function directorioTemporal(): string
    {
        $ruta = sys_get_temp_dir().'/sondeo-'.uniqid();
        mkdir($ruta, 0775, true);
        $this->temporales[] = $ruta;

        return $ruta;
    }

    private function registro(): RegistroFuentesImagen
    {
        return new RegistroFuentesImagen([[
            'fuente' => 'FUENTE_PRUEBA', 'url_base' => 'https://ejemplo.test',
            'consulta_por' => 'EAN', 'licencia_datos' => 'ODbL 1.0',
            'licencia_imagen' => 'CC BY-SA 3.0', 'atribucion' => 'Fuente de Prueba',
            'url_atribucion' => 'https://ejemplo.test',
            'permite_almacenar' => 'SI', 'permite_enlazar' => 'SI',
            'robots_permite' => 'SI', 'acceso_permitido' => 'SI', 'estado' => 'DISPONIBLE',
            'verificado_el' => '2026-09-09', 'observacion' => 'fuente de prueba',
        ]]);
    }

    private function producto(): Producto
    {
        $this->montarEscenario();
        $producto = $this->crearProducto(5);

        $producto->forceFill([
            'codigo_barras' => '7750182001234',
            'descripcion' => 'Coca Cola Original 500 ml',
            'marca' => 'Coca-Cola',
            'presentacion' => '500 ml',
            'foto_estado' => EstadoFoto::SIN_IMAGEN,
        ])->save();

        return $producto->fresh();
    }

    #[Test]
    public function el_sondeo_no_modifica_el_producto(): void
    {
        $producto = $this->producto();
        $antes = $producto->only([
            'foto', 'foto_estado', 'foto_fuente', 'foto_url_origen',
            'foto_licencia', 'foto_atribucion', 'foto_fecha_consulta',
        ]);

        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', [
            'url' => 'https://ejemplo.test/coca.jpg', 'marca' => 'Coca-Cola',
            'nombre' => 'Coca Cola Original', 'presentacion' => '500 ml',
            'ean' => '7750182001234',
        ]);

        $resultado = $this->sondeo($fuente)->enriquecer([$producto]);

        // Informa lo que habria pasado...
        $this->assertSame(1, $resultado->verificadas);

        // ...pero el producto queda exactamente igual.
        $this->assertSame($antes, $producto->fresh()->only(array_keys($antes)));
    }

    #[Test]
    public function el_sondeo_no_deja_archivos(): void
    {
        $producto = $this->producto();
        $directorio = $this->directorioTemporal();

        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', [
            'url' => 'https://ejemplo.test/coca.jpg', 'marca' => 'Coca-Cola',
            'nombre' => 'Coca Cola Original', 'presentacion' => '500 ml',
            'ean' => '7750182001234',
        ]);

        $this->sondeo($fuente)->enriquecer([$producto]);

        $this->assertFileDoesNotExist($directorio."/productos/{$producto->id}.webp");
        $this->assertSame([], glob($directorio.'/productos/*') ?: []);
    }

    #[Test]
    public function el_sondeo_no_toca_una_foto_propia(): void
    {
        $producto = $this->producto();
        $producto->forceFill([
            'foto' => 'productos/propia.webp',
            'foto_estado' => EstadoFoto::PROPIA,
            'foto_fuente' => 'PROPIA',
        ])->save();

        $fuente = new FuenteImagenFalsa();
        $resultado = $this->sondeo($fuente)->enriquecer([$producto->fresh()]);

        // Ni siquiera la consulta.
        $this->assertSame([], $fuente->consultas);
        $this->assertSame(1, $resultado->omitidos);
        $this->assertSame('productos/propia.webp', $producto->fresh()->foto);
    }

    private function sondeo(FuenteImagenFalsa $fuente): EnriquecedorImagenes
    {
        return new EnriquecedorImagenes(
            $fuente,
            new VerificadorCorrespondencia(
                new NormalizadorMarca(['cocacola' => 'Coca-Cola']),
                new NormalizadorPresentacion(),
            ),
            $this->registro(),
            // Las dos cosas que hacen que el sondeo sea inocuo.
            simular: true,
            pausar: false,
            descargador: null,
        );
    }
}
