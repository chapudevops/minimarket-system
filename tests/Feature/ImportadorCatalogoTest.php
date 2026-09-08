<?php

namespace Tests\Feature;

use App\Catalogo\EsquemaMaestro;
use App\Catalogo\ImportadorCatalogo;
use App\Models\Auditoria;
use App\Models\Producto;
use App\Models\ProductoAlmacen;
use App\Sunat\Tributos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El importador masivo: catalogo_maestro.csv -> tabla productos.
 *
 * Lo que mas se protege aca no es que importe, sino que NO destruya. El
 * escenario real es una tienda que ya opera: importa, ajusta precios durante
 * semanas, escanea codigos de barras, y despues vuelve a importar un catalogo
 * actualizado. Nada de ese trabajo puede perderse.
 */
class ImportadorCatalogoTest extends TestCase
{
    use CreaEscenarioDeVenta, DatabaseTransactions;

    private string $prefijo;

    protected function setUp(): void
    {
        parent::setUp();

        // Codigos propios de cada test: la base de test conserva el catalogo
        // maestro sembrado y no queremos chocar con el.
        $this->prefijo = 'IMP'.strtoupper(substr(uniqid(), -6));
    }

    /** Una fila del maestro, con todas las columnas del esquema. */
    private function fila(array $campos = []): array
    {
        $base = array_fill_keys(EsquemaMaestro::COLUMNAS, '');

        return array_merge($base, [
            'codigo_interno' => $this->prefijo.'-GAS-000001',
            'descripcion'    => 'Gaseosa de prueba 500 ml',
            'categoria'      => 'BEBIDAS',
            'subcategoria'   => 'GASEOSAS',
            'unidad'         => 'UNIDAD',
            'marca'          => 'Marca Prueba',
            'presentacion'   => '500 ml',
            'operacion'      => 'GRAVADO',
            'afecto_isc'     => '0',
            'afecto_ivap'    => '0',
            'precio_compra'  => '2.00',
            'precio_venta'   => '3.00',
            'tipo_producto'  => 'PRODUCTO',
            'detraccion'     => '0',
            'stock_minimo'   => '10',
            'fuente'         => 'TOTTUS',
            'url_fuente'     => 'https://www.tottus.com.pe/ejemplo',
        ], $campos);
    }

    private function importar(array $filas, bool $simular = false, bool $precios = false)
    {
        return (new ImportadorCatalogo($simular, $precios))->importar($filas);
    }

    /* --- Carga --------------------------------------------------------- */

    #[Test]
    public function importa_una_fila_del_maestro(): void
    {
        $resultado = $this->importar([$this->fila()]);

        $this->assertSame(1, $resultado->creados);
        $this->assertSame(0, $resultado->totalRechazadas());

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        $this->assertNotNull($producto);
        $this->assertSame('Gaseosa de prueba 500 ml', $producto->descripcion);
        $this->assertSame('GRAVADO', $producto->operacion);
        $this->assertSame('UNIDAD', $producto->unidad);
        $this->assertSame('2.00', $producto->precio_compra);
        $this->assertSame('3.00', $producto->precio_venta);
        $this->assertTrue($producto->estado);
    }

    #[Test]
    public function una_celda_vacia_entra_como_null_y_no_como_cadena_vacia(): void
    {
        $this->importar([$this->fila(['marca' => '', 'codigo_barras' => '', 'fecha_vencimiento' => ''])]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        // '' en codigo_barras chocaria contra el indice unico apenas hubiera
        // un segundo producto sin EAN.
        $this->assertNull($producto->codigo_barras);
        $this->assertNull($producto->marca);
        $this->assertNull($producto->fecha_vencimiento);
    }

    #[Test]
    public function los_tributos_llegan_a_la_base_separados(): void
    {
        $this->importar([
            $this->fila(['codigo_interno' => $this->prefijo.'-CER-000001', 'operacion' => 'GRAVADO', 'afecto_isc' => '1']),
            $this->fila(['codigo_interno' => $this->prefijo.'-HUE-000001', 'operacion' => 'EXONERADO']),
        ]);

        $cerveza = Producto::where('codigo_interno', $this->prefijo.'-CER-000001')->first();
        $huevo = Producto::where('codigo_interno', $this->prefijo.'-HUE-000001')->first();

        $this->assertSame('GRAVADO', $cerveza->operacion);
        $this->assertTrue($cerveza->afecto_isc);
        $this->assertFalse($cerveza->afecto_ivap);

        $this->assertSame('EXONERADO', $huevo->operacion);
        $this->assertFalse($huevo->afecto_isc);
    }

    #[Test]
    public function el_importador_no_crea_stock(): void
    {
        $this->importar([$this->fila()]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        // Un producto importado nace sin stock y entra al inventario por una
        // compra. Sembrarlo seria inventarlo.
        $this->assertSame(0, ProductoAlmacen::where('producto_id', $producto->id)->count());
    }

    /* --- PENDIENTE no entra -------------------------------------------- */

    #[Test]
    public function una_fila_pendiente_no_se_importa(): void
    {
        $resultado = $this->importar([$this->fila(['operacion' => Tributos::PENDIENTE])]);

        $this->assertSame(0, $resultado->creados);
        $this->assertSame(1, $resultado->totalRechazadas());
        $this->assertStringContainsString('IGV', $resultado->rechazos[0]['motivo']);

        $this->assertNull(Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first());
    }

    /**
     * Cambio de regla respecto de la version anterior de este test.
     *
     * Antes una fila sin precios se rechazaba. Junto con la regla de que los
     * precios no se inventan —precio_compra sale de la lista del proveedor,
     * precio_venta lo decide el comercio— eso dejaba el catalogo imposible de
     * cargar: las dos condiciones no se podian cumplir a la vez.
     *
     * Ahora entra sin precio, igual que entra sin stock, y lo que impide
     * cobrarlo es estaListoParaVender(). El producto existe, se busca y se le
     * puede comprar al proveedor; simplemente todavia no esta a la venta.
     */
    #[Test]
    public function una_fila_sin_precios_se_importa_pero_no_queda_vendible(): void
    {
        $resultado = $this->importar([$this->fila(['precio_compra' => '', 'precio_venta' => ''])]);

        $this->assertSame(0, $resultado->totalRechazadas());
        $this->assertSame(1, $resultado->creados);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        $this->assertNotNull($producto);
        $this->assertSame('0.00', $producto->precio_venta);
        $this->assertFalse($producto->tienePrecio());
        $this->assertFalse($producto->estaListoParaVender());
    }

    #[Test]
    public function una_fila_mala_no_arrastra_a_las_buenas(): void
    {
        $resultado = $this->importar([
            $this->fila(['codigo_interno' => $this->prefijo.'-GAS-000001']),
            $this->fila(['codigo_interno' => $this->prefijo.'-ARR-000001', 'operacion' => Tributos::PENDIENTE]),
            $this->fila(['codigo_interno' => $this->prefijo.'-GAS-000002']),
        ]);

        // Rechazar es por fila, no por archivo: un catalogo de 5.000
        // referencias con 200 pendientes tiene que poder cargar las 4.800.
        $this->assertSame(2, $resultado->creados);
        $this->assertSame(1, $resultado->totalRechazadas());
    }

    /* --- Idempotencia y proteccion del trabajo de la tienda ------------ */

    #[Test]
    public function volver_a_importar_no_duplica(): void
    {
        $filas = [$this->fila()];

        $this->importar($filas);
        $antes = Producto::count();

        $segunda = $this->importar($filas);

        $this->assertSame($antes, Producto::count());
        $this->assertSame(0, $segunda->creados);
        $this->assertSame(1, $segunda->sinCambios);
    }

    #[Test]
    public function una_reimportacion_no_pisa_los_precios_de_la_tienda(): void
    {
        $this->importar([$this->fila()]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();
        $producto->update(['precio_venta' => 4.50, 'precio_compra' => 2.80]);

        // El catalogo vuelve con los precios viejos.
        $this->importar([$this->fila(['precio_compra' => '2.00', 'precio_venta' => '3.00'])]);

        $producto->refresh();

        // Tres semanas de ajustes de precio no se pierden por reimportar.
        $this->assertSame('4.50', $producto->precio_venta);
        $this->assertSame('2.80', $producto->precio_compra);
    }

    #[Test]
    public function con_actualizar_precios_si_los_pisa(): void
    {
        $this->importar([$this->fila()]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();
        $producto->update(['precio_venta' => 4.50]);

        $this->importar([$this->fila(['precio_venta' => '3.00'])], simular: false, precios: true);

        $this->assertSame('3.00', $producto->refresh()->precio_venta);
    }

    #[Test]
    public function una_reimportacion_no_toca_stock_minimo_ni_vencimiento(): void
    {
        $this->importar([$this->fila()]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();
        $producto->update(['stock_minimo' => 42, 'fecha_vencimiento' => '2027-01-31', 'detraccion' => true]);

        $this->importar([$this->fila(['stock_minimo' => '10', 'fecha_vencimiento' => '', 'detraccion' => '0'])]);

        $producto->refresh();

        // Dependen de la rotacion real, del lote recibido y de decisiones de
        // la tienda: el catalogo no opina.
        $this->assertSame(42, $producto->stock_minimo);
        $this->assertSame('2027-01-31', $producto->fecha_vencimiento->format('Y-m-d'));
        $this->assertTrue($producto->detraccion);
    }

    #[Test]
    public function una_reimportacion_no_borra_un_codigo_de_barras_escaneado(): void
    {
        $this->importar([$this->fila()]);

        $ean = '900'.random_int(1000000000, 9999999999);
        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();
        $producto->update(['codigo_barras' => $ean]);

        // El CSV sigue trayendo la celda vacia, como siempre.
        $this->importar([$this->fila(['codigo_barras' => ''])]);

        // El EAN que alguien escaneo del producto fisico vale mas que una
        // celda vacia.
        $this->assertSame($ean, $producto->refresh()->codigo_barras);
    }

    #[Test]
    public function el_catalogo_si_corrige_la_afectacion_de_igv(): void
    {
        $this->importar([$this->fila(['operacion' => 'GRAVADO'])]);

        // Si el contador resolvio que iba EXONERADO, esa correccion tiene que
        // llegar: los campos del catalogo si se refrescan.
        $resultado = $this->importar([$this->fila(['operacion' => 'EXONERADO', 'afecto_isc' => '1'])]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        $this->assertSame(1, $resultado->actualizados);
        $this->assertSame('EXONERADO', $producto->operacion);
        $this->assertTrue($producto->afecto_isc);
    }

    #[Test]
    public function un_codigo_de_barras_nuevo_se_asigna_a_un_producto_que_no_tenia(): void
    {
        $this->importar([$this->fila()]);

        $ean = '900'.random_int(1000000000, 9999999999);
        $resultado = $this->importar([$this->fila(['codigo_barras' => $ean])]);

        $this->assertSame(1, $resultado->codigosBarrasAsignados);
        $this->assertSame(
            $ean,
            Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->value('codigo_barras')
        );
    }

    /* --- Integridad de codigos ----------------------------------------- */

    #[Test]
    public function dos_filas_con_el_mismo_ean_no_rompen_la_importacion(): void
    {
        $ean = '900'.random_int(1000000000, 9999999999);

        $resultado = $this->importar([
            $this->fila(['codigo_interno' => $this->prefijo.'-GAS-000001', 'codigo_barras' => $ean]),
            $this->fila(['codigo_interno' => $this->prefijo.'-GAS-000002', 'codigo_barras' => $ean]),
        ]);

        // Sin este control, el indice unico cortaria la importacion a la mitad
        // con una excepcion de base de datos.
        $this->assertSame(1, $resultado->creados);
        $this->assertSame(1, $resultado->totalRechazadas());
        $this->assertStringContainsString('repetido en el archivo', $resultado->rechazos[0]['motivo']);
    }

    #[Test]
    public function un_ean_que_ya_es_de_otro_producto_se_rechaza(): void
    {
        $this->montarEscenario();

        $ean = '900'.random_int(1000000000, 9999999999);
        $existente = $this->crearProducto(5);
        $existente->update(['codigo_barras' => $ean]);

        $resultado = $this->importar([$this->fila(['codigo_barras' => $ean])]);

        $this->assertSame(0, $resultado->creados);
        $this->assertStringContainsString('ya registrado', $resultado->rechazos[0]['motivo']);
    }

    #[Test]
    public function un_codigo_interno_repetido_en_el_archivo_se_rechaza(): void
    {
        $resultado = $this->importar([
            $this->fila(['descripcion' => 'Primera']),
            $this->fila(['descripcion' => 'Segunda']),
        ]);

        // La segunda pisaria a la primera sin que nadie se entere.
        $this->assertSame(1, $resultado->creados);
        $this->assertSame(1, $resultado->totalRechazadas());
        $this->assertSame(
            'Primera',
            Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->value('descripcion')
        );
    }

    /* --- Simulacion ----------------------------------------------------- */

    #[Test]
    public function la_simulacion_no_escribe_nada(): void
    {
        $antes = Producto::count();

        $resultado = $this->importar([$this->fila()], simular: true);

        $this->assertSame($antes, Producto::count());
        // Pero si informa lo que habria pasado.
        $this->assertSame(1, $resultado->creados);
    }

    /* --- Volumen -------------------------------------------------------- */

    #[Test]
    public function importa_mas_filas_que_el_tamano_del_lote(): void
    {
        // El importador escribe de a 500 filas por transaccion. Con menos de
        // un lote, el codigo de corte de lote nunca se ejercita.
        $filas = [];

        for ($i = 1; $i <= 620; $i++) {
            $filas[] = $this->fila([
                'codigo_interno' => sprintf('%s-VOL-%06d', $this->prefijo, $i),
                'descripcion' => "Producto de volumen {$i}",
            ]);
        }

        $resultado = $this->importar($filas);

        $this->assertSame(620, $resultado->creados);
        $this->assertSame(
            620,
            Producto::where('codigo_interno', 'LIKE', $this->prefijo.'-VOL-%')->count()
        );
    }

    #[Test]
    public function no_consulta_la_base_una_vez_por_fila(): void
    {
        $filas = [];

        for ($i = 1; $i <= 200; $i++) {
            $filas[] = $this->fila(['codigo_interno' => sprintf('%s-CON-%06d', $this->prefijo, $i)]);
        }

        DB::enableQueryLog();
        $this->importar($filas);
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Buscar los existentes de a uno era el cuello de botella: 5.000 filas
        // tardaban 25 segundos y con la busqueda por lote bajaron a 8. Lo que
        // se protege es el orden de magnitud —un INSERT por fila y poco mas—,
        // no un numero exacto.
        $this->assertLessThan(
            250,
            $consultas,
            "200 filas dispararon {$consultas} consultas: volvio la busqueda de a una"
        );
    }

    #[Test]
    public function no_deja_una_entrada_de_auditoria_por_producto(): void
    {
        $antes = Auditoria::where('entidad', 'Producto')->count();

        $filas = [];

        for ($i = 1; $i <= 30; $i++) {
            $filas[] = $this->fila(['codigo_interno' => sprintf('%s-AUD-%06d', $this->prefijo, $i)]);
        }

        $this->importar($filas);

        // Una importacion es UNA accion humana. Treinta lineas de "Creó
        // producto X" —o 4.800 en un catalogo real— dejarian la bitacora
        // inservible para ver lo que si hizo una persona a mano.
        $this->assertSame($antes, Auditoria::where('entidad', 'Producto')->count());
    }

    /* --- Lo importado se puede vender ----------------------------------- */

    #[Test]
    public function un_producto_importado_se_puede_vender(): void
    {
        $this->montarEscenario();

        $this->importar([$this->fila()]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        // Entra al inventario por una compra, no por el importador.
        ProductoAlmacen::create([
            'producto_id' => $producto->id,
            'almacen_id'  => $this->almacen->id,
            'stock'       => 10,
        ]);

        $respuesta = $this->vender(
            [['id' => $producto->id, 'cantidad' => 2, 'precio' => 3.00]],
            6.00
        );

        $respuesta->assertOk()->assertJson(['success' => true]);
        $this->assertSame(8, $this->stockDe($producto));
    }

    #[Test]
    public function el_pos_encuentra_un_producto_recien_importado(): void
    {
        $this->montarEscenario();
        $this->importar([$this->fila()]);

        $producto = Producto::where('codigo_interno', $this->prefijo.'-GAS-000001')->first();

        $busqueda = $this->actingAs($this->vendedor)
            ->getJson('/terminal/productos?search='.$producto->codigo_interno)
            ->assertOk();

        $this->assertContains($producto->id, array_column($busqueda->json('data'), 'id'));
    }
}
