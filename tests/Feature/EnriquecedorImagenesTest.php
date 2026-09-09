<?php

namespace Tests\Feature;

use App\Catalogo\Imagenes\DescargadorImagen;
use App\Catalogo\Imagenes\EnriquecedorImagenes;
use App\Catalogo\Imagenes\EstadoFoto;
use App\Catalogo\Imagenes\FuenteImagenNoAutorizada;
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
 * El enriquecimiento de imagenes de punta a punta, sin salir a internet.
 *
 * Lo que se protege: que sea incremental (no repetir trabajo ni golpear a la
 * fuente), reanudable (el estado vive en la fila), aislado (un error no tumba
 * el lote) y sobre todo que una foto propia no se pise jamas.
 */
class EnriquecedorImagenesTest extends TestCase
{
    use CreaEscenarioDeVenta, DatabaseTransactions;

    private function registro(
        string $estado = 'DISPONIBLE',
        string $almacenar = 'SI',
        string $acceso = 'SI',
        string $licenciaImagen = 'CC BY-SA 3.0',
    ): RegistroFuentesImagen {
        return new RegistroFuentesImagen([[
            'fuente' => 'FUENTE_PRUEBA', 'url_base' => 'https://ejemplo.test',
            'consulta_por' => 'EAN',
            'licencia_datos' => 'ODbL 1.0', 'licencia_imagen' => $licenciaImagen,
            'atribucion' => 'Fuente de Prueba', 'url_atribucion' => 'https://ejemplo.test',
            'permite_almacenar' => $almacenar, 'permite_enlazar' => 'SI',
            'robots_permite' => 'SI', 'acceso_permitido' => $acceso, 'estado' => $estado,
            'verificado_el' => '2026-09-09', 'observacion' => 'fuente de prueba',
        ]]);
    }

    private function enriquecedor(
        FuenteImagenFalsa $fuente,
        ?RegistroFuentesImagen $registro = null,
        bool $simular = false,
        ?DescargadorImagen $descargador = null,
    ): EnriquecedorImagenes {
        return new EnriquecedorImagenes(
            $fuente,
            new VerificadorCorrespondencia(
                new NormalizadorMarca(['cocacola' => 'Coca-Cola']),
                new NormalizadorPresentacion(),
            ),
            $registro ?? $this->registro(),
            simular: $simular,
            // Sin pausas: el test no necesita esperar por producto.
            pausar: false,
            descargador: $descargador,
        );
    }

    /** Descargador con una imagen real en memoria, sin salir a internet. */
    private function descargador(?string $directorio = null, bool $falla = false): DescargadorImagen
    {
        $imagen = imagecreatetruecolor(300, 300);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 10, 90, 200));
        ob_start();
        imagepng($imagen);
        $png = ob_get_clean();
        imagedestroy($imagen);

        return new DescargadorImagen(
            $directorio ?? $this->directorioTemporal(),
            fn (string $url) => $falla ? [500, null, null] : [200, $png, 'image/png'],
        );
    }

    private function directorioTemporal(): string
    {
        $ruta = sys_get_temp_dir().'/enriquecedor-'.uniqid();
        mkdir($ruta, 0775, true);
        $this->temporales[] = $ruta;

        return $ruta;
    }

    /** @var array<int,string> */
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

    private function producto(array $campos = []): Producto
    {
        $this->montarEscenario();
        $producto = $this->crearProducto(5);

        $producto->forceFill(array_merge([
            'codigo_barras' => '7750182001234',
            'descripcion' => 'Coca Cola Original 500 ml',
            'marca' => 'Coca-Cola',
            'presentacion' => '500 ml',
            'foto_estado' => EstadoFoto::SIN_IMAGEN,
            'foto_fecha_consulta' => null,
        ], $campos))->save();

        return $producto->fresh();
    }

    private function datos(array $campos = []): array
    {
        return array_merge([
            'url' => 'https://ejemplo.test/coca-500.jpg',
            'marca' => 'Coca-Cola', 'nombre' => 'Coca Cola Original',
            'presentacion' => '500 ml', 'ean' => '7750182001234',
        ], $campos);
    }

    /* --- Camino feliz --------------------------------------------------- */

    #[Test]
    public function encuentra_la_imagen_por_ean_y_la_marca_verificada(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $resultado = $this->enriquecedor($fuente)->enriquecer([$producto]);

        $this->assertSame(1, $resultado->verificadas);

        $producto->refresh();
        $this->assertSame(EstadoFoto::VERIFICADA, $producto->foto_estado);
        $this->assertSame('FUENTE_PRUEBA', $producto->foto_fuente);
        $this->assertSame('https://ejemplo.test/coca-500.jpg', $producto->foto_url_origen);
        $this->assertNotNull($producto->foto_fecha_consulta);
    }

    #[Test]
    public function un_ean_que_la_fuente_no_conoce_queda_sin_imagen(): void
    {
        $producto = $this->producto();
        $resultado = $this->enriquecedor(new FuenteImagenFalsa())->enriquecer([$producto]);

        $this->assertSame(1, $resultado->sinImagen);
        $this->assertSame(EstadoFoto::SIN_IMAGEN, $producto->fresh()->foto_estado);
    }

    #[Test]
    public function una_imagen_que_no_corresponde_queda_para_revisar(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos([
            'nombre' => 'Coca Cola Zero',
        ]));

        $resultado = $this->enriquecedor($fuente)->enriquecer([$producto]);

        $this->assertSame(1, $resultado->aRevisar);
        $this->assertSame(EstadoFoto::REVISAR, $producto->fresh()->foto_estado);
        // Y no se descarga nada de lo que no se esta seguro.
        $this->assertNull($producto->fresh()->foto);
    }

    /* --- La foto propia es intocable ------------------------------------ */

    #[Test]
    public function una_foto_propia_no_se_reemplaza_nunca(): void
    {
        $producto = $this->producto([
            'foto' => 'productos/propia.webp',
            'foto_estado' => EstadoFoto::PROPIA,
            'foto_fuente' => 'PROPIA',
        ]);

        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());
        $resultado = $this->enriquecedor($fuente)->enriquecer([$producto]);

        // Ni siquiera se consulta: no hay motivo para pedirle nada a nadie.
        $this->assertSame([], $fuente->consultas);
        $this->assertSame(1, $resultado->omitidos);

        $producto->refresh();
        $this->assertSame('productos/propia.webp', $producto->foto);
        $this->assertSame(EstadoFoto::PROPIA, $producto->foto_estado);
    }

    /* --- Incremental y reanudable --------------------------------------- */

    #[Test]
    public function una_imagen_ya_verificada_no_se_vuelve_a_consultar(): void
    {
        $producto = $this->producto([
            'foto_estado' => EstadoFoto::VERIFICADA,
            'foto_url_origen' => 'https://ejemplo.test/ya-estaba.jpg',
        ]);

        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());
        $resultado = $this->enriquecedor($fuente)->enriquecer([$producto]);

        $this->assertSame([], $fuente->consultas, 'volvió a pedir una imagen ya resuelta');
        $this->assertSame(1, $resultado->omitidos);
    }

    #[Test]
    public function sin_imagen_se_reintenta_recien_pasado_el_tiempo(): void
    {
        $reciente = $this->producto([
            'foto_estado' => EstadoFoto::SIN_IMAGEN,
            'foto_fecha_consulta' => now()->subDays(3)->toDateString(),
        ]);

        $fuente = (new FuenteImagenFalsa());
        $this->enriquecedor($fuente)->enriquecer([$reciente]);
        $this->assertSame([], $fuente->consultas, 'reintentó demasiado pronto');

        // Pasado el plazo si vuelve a intentarlo: la fuente pudo incorporarlo.
        $viejo = $reciente;
        $viejo->forceFill(['foto_fecha_consulta' => now()->subDays(90)->toDateString()])->save();

        $fuente2 = (new FuenteImagenFalsa());
        $this->enriquecedor($fuente2)->enriquecer([$viejo->fresh()]);
        $this->assertCount(1, $fuente2->consultas);
    }

    #[Test]
    public function un_producto_sin_ean_no_se_consulta_y_sigue_siendo_valido(): void
    {
        $producto = $this->producto(['codigo_barras' => null]);

        $fuente = new FuenteImagenFalsa();
        $resultado = $this->enriquecedor($fuente)->enriquecer([$producto]);

        $this->assertSame([], $fuente->consultas);
        $this->assertSame(1, $resultado->omitidos);
        // El producto no pierde nada por no tener imagen.
        $this->assertTrue($producto->fresh()->estado);
    }

    /* --- Robustez -------------------------------------------------------- */

    #[Test]
    public function reintenta_ante_un_error_transitorio(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())
            ->responde('7750182001234', $this->datos())
            ->fallaVeces('7750182001234', 2);

        $resultado = $this->enriquecedor($fuente)->enriquecer([$producto]);

        $this->assertSame(1, $resultado->verificadas);
        $this->assertCount(3, $fuente->consultas, 'debía reintentar dos veces antes de lograrlo');
    }

    #[Test]
    public function un_error_en_una_imagen_no_detiene_el_lote(): void
    {
        $this->montarEscenario();

        $malo = $this->crearProducto(5);
        $malo->forceFill(['codigo_barras' => '7750182000001', 'foto_estado' => EstadoFoto::SIN_IMAGEN])->save();

        $bueno = $this->crearProducto(5);
        $bueno->forceFill([
            'codigo_barras' => '7750182001234', 'descripcion' => 'Coca Cola Original 500 ml',
            'marca' => 'Coca-Cola', 'presentacion' => '500 ml', 'foto_estado' => EstadoFoto::SIN_IMAGEN,
        ])->save();

        $fuente = (new FuenteImagenFalsa())
            ->responde('7750182001234', $this->datos())
            // Falla siempre: agota los reintentos y lanza.
            ->fallaVeces('7750182000001', 99);

        $resultado = $this->enriquecedor($fuente)->enriquecer([$malo->fresh(), $bueno->fresh()]);

        $this->assertSame(1, $resultado->errores);
        $this->assertSame(1, $resultado->verificadas, 'el producto bueno debía procesarse igual');
    }

    #[Test]
    public function una_fuente_no_autorizada_no_se_consulta(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        // Es el caso real de hoy: Open Food Facts tiene la licencia correcta
        // pero su robots.txt prohibe el acceso automatizado.
        $this->expectException(FuenteImagenNoAutorizada::class);

        $this->enriquecedor($fuente, $this->registro(estado: 'BLOQUEADA'))->enriquecer([$producto]);
    }

    #[Test]
    public function si_la_fuente_solo_permite_enlazar_no_se_guarda_copia(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $registro = $this->registro(estado: 'SOLO_REFERENCIA', almacenar: 'NO');
        $resultado = $this->enriquecedor($fuente, $registro)->enriquecer([$producto]);

        $this->assertSame(1, $resultado->verificadas);

        $producto->refresh();
        // Queda la URL de origen, pero ningun archivo nuestro.
        $this->assertNull($producto->foto);
        $this->assertSame('https://ejemplo.test/coca-500.jpg', $producto->foto_url_origen);
    }

    #[Test]
    public function la_simulacion_no_escribe(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $resultado = $this->enriquecedor($fuente, simular: true)->enriquecer([$producto]);

        $this->assertSame(1, $resultado->verificadas);
        $this->assertSame(EstadoFoto::SIN_IMAGEN, $producto->fresh()->foto_estado);
    }

    /* --- Descarga real ---------------------------------------------------- */

    #[Test]
    public function descarga_y_guarda_el_archivo_cuando_la_fuente_lo_permite(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $directorio = $this->directorioTemporal();
        $resultado = $this->enriquecedor($fuente, descargador: $this->descargador($directorio))
            ->enriquecer([$producto]);

        $this->assertSame(1, $resultado->verificadas);

        $producto->refresh();
        $this->assertSame("productos/{$producto->id}.webp", $producto->foto);
        $this->assertFileExists($directorio.'/'.$producto->foto);
    }

    #[Test]
    public function si_la_descarga_falla_la_imagen_queda_para_revisar(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $resultado = $this->enriquecedor($fuente, descargador: $this->descargador(falla: true))
            ->enriquecer([$producto]);

        // La ficha coincidia, pero el archivo no llego. Dar por buena una
        // imagen que no esta seria peor que admitir que hay que mirarla.
        $this->assertSame(0, $resultado->verificadas);
        $this->assertSame(1, $resultado->aRevisar);

        $producto->refresh();
        $this->assertSame(EstadoFoto::REVISAR, $producto->foto_estado);
        $this->assertNull($producto->foto);
    }

    #[Test]
    public function una_candidata_a_revisar_no_se_descarga(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos(['nombre' => 'Coca Cola Zero']));

        $directorio = $this->directorioTemporal();
        $this->enriquecedor($fuente, descargador: $this->descargador($directorio))->enriquecer([$producto]);

        // No se gasta ancho de banda ni disco en una imagen que no corresponde.
        $this->assertFileDoesNotExist($directorio."/productos/{$producto->id}.webp");
    }

    /* --- Placeholder ------------------------------------------------------ */

    #[Test]
    public function un_producto_sin_imagen_muestra_el_placeholder(): void
    {
        $producto = $this->producto();

        $this->assertFalse($producto->tieneFoto());
        $this->assertSame(Producto::placeholder(), $producto->foto_url);
        $this->assertStringContainsString('default-product', $producto->foto_url);
    }

    #[Test]
    public function una_imagen_solo_enlazada_se_muestra_desde_su_origen(): void
    {
        $producto = $this->producto([
            'foto' => null,
            'foto_estado' => EstadoFoto::VERIFICADA,
            'foto_url_origen' => 'https://ejemplo.test/coca-500.jpg',
        ]);

        $this->assertTrue($producto->tieneFoto());
        $this->assertSame('https://ejemplo.test/coca-500.jpg', $producto->foto_url);
    }

    #[Test]
    public function la_imagen_externa_guarda_su_licencia_y_su_credito(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $this->enriquecedor($fuente)->enriquecer([$producto]);

        $producto->refresh();

        // La licencia se guarda al obtener la foto, no se deduce despues: si la
        // fuente cambia sus condiciones, esta imagen sigue amparada por lo que
        // decia el dia que se bajo.
        $this->assertSame('CC BY-SA 3.0', $producto->foto_licencia);
        $this->assertSame('Imagen: Fuente de Prueba (CC BY-SA 3.0)', $producto->foto_atribucion);
        $this->assertSame('2026-09-09', $producto->foto_fecha_consulta?->toDateString() ?: now()->toDateString());

        $credito = $producto->creditoDeFoto();
        $this->assertNotNull($credito);
        $this->assertStringContainsString('CC BY-SA', $credito['texto']);
    }

    #[Test]
    public function sin_licencia_determinable_la_imagen_no_se_publica(): void
    {
        $producto = $this->producto();
        $fuente = (new FuenteImagenFalsa())->responde('7750182001234', $this->datos());

        $directorio = $this->directorioTemporal();
        $registro = $this->registro(licenciaImagen: RegistroFuentesImagen::NO_DETERMINABLE);

        $resultado = $this->enriquecedor($fuente, $registro, descargador: $this->descargador($directorio))
            ->enriquecer([$producto]);

        // Coincidian marca, presentacion y EAN, pero no hay con que justificar
        // el uso de la foto. Una licencia inventada es peor que no tener imagen.
        $this->assertSame(0, $resultado->verificadas);
        $this->assertSame(1, $resultado->aRevisar);

        $producto->refresh();
        $this->assertSame(EstadoFoto::REVISAR, $producto->foto_estado);
        $this->assertNull($producto->foto);
        $this->assertNull($producto->foto_licencia);
        $this->assertFileDoesNotExist($directorio."/productos/{$producto->id}.webp");
        // Y no se muestra: el POS sigue con el placeholder.
        $this->assertFalse($producto->tieneFoto());
    }

    #[Test]
    public function una_foto_propia_no_lleva_credito_de_terceros(): void
    {
        $producto = $this->producto([
            'foto' => 'productos/1.webp',
            'foto_estado' => EstadoFoto::PROPIA,
            'foto_fuente' => 'PROPIA',
            'foto_licencia' => null,
            'foto_atribucion' => null,
        ]);

        $this->assertTrue($producto->tieneFoto());
        $this->assertNull($producto->creditoDeFoto());
    }

    #[Test]
    public function la_url_de_la_foto_no_duplica_el_segmento_de_carpeta(): void
    {
        // El dashboard armaba la URL a mano con 'storage/productos/' . $foto,
        // y con las rutas nuevas producia storage/productos/productos/1.webp.
        // Todas las vistas tienen que pasar por foto_url.
        $conPrefijo = $this->producto(['foto' => 'productos/1.webp', 'foto_estado' => EstadoFoto::VERIFICADA]);
        $this->assertStringEndsWith('/storage/productos/1.webp', $conPrefijo->foto_url);
        $this->assertStringNotContainsString('productos/productos', $conPrefijo->foto_url);

        // Y el formato viejo, sin prefijo, sigue resolviendo igual.
        $conPrefijo->forceFill(['foto' => '1758000000_abc.jpg'])->save();
        $this->assertStringEndsWith('/storage/productos/1758000000_abc.jpg', $conPrefijo->fresh()->foto_url);
    }

    #[Test]
    public function una_candidata_a_revisar_no_se_muestra_como_si_fuera_buena(): void
    {
        $producto = $this->producto([
            'foto' => null,
            'foto_estado' => EstadoFoto::REVISAR,
            'foto_url_origen' => 'https://ejemplo.test/dudosa.jpg',
        ]);

        // Mientras nadie la confirme, el POS muestra el placeholder.
        $this->assertFalse($producto->tieneFoto());
        $this->assertSame(Producto::placeholder(), $producto->foto_url);
    }
}
