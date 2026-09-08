<?php

namespace Tests\Feature;

use App\Catalogo\Fuentes\MapeoFuentes;
use App\Catalogo\Taxonomia;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Subcategoria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * Taxonomia, SKU, categorias y el candado de precio.
 *
 * Es la fase que trajo el catalogo real: 738 productos importados sin stock y
 * sin precio. Estos tests fijan las tres decisiones que la sostienen —el SKU
 * es codigo_interno, la categoria se deriva de la subcategoria, y un producto
 * sin precio no se puede cobrar— y que ninguna dependa de internet.
 */
class CatalogoRealTest extends TestCase
{
    use CreaEscenarioDeVenta;
    use DatabaseTransactions;

    // ---------------------------------------------------------------- taxonomía

    public function test_la_taxonomia_de_la_base_coincide_con_el_diccionario(): void
    {
        $archivo = Taxonomia::desdeArchivo();

        foreach ($archivo->todas() as $entrada) {
            $categoria = Categoria::where('codigo', $entrada['codigo_categoria'])->first();

            $this->assertNotNull($categoria, "Falta la categoría {$entrada['categoria']} en la base");

            $this->assertDatabaseHas('subcategorias', [
                'categoria_id' => $categoria->id,
                'codigo'       => $entrada['codigo_subcategoria'],
            ]);
        }
    }

    public function test_el_mapeo_de_fuentes_solo_apunta_a_subcategorias_que_existen(): void
    {
        $mapeo = MapeoFuentes::desdeArchivo();

        $this->assertGreaterThan(0, $mapeo->total(), 'El diccionario de mapeo está vacío');

        foreach (['PLAZAVEA', 'METRO'] as $fuente) {
            $this->assertNotEmpty($mapeo->rutasDe($fuente), "{$fuente} no tiene categorías mapeadas");
        }
    }

    public function test_una_subcategoria_con_productos_no_se_puede_borrar(): void
    {
        $producto = $this->productoClasificado();

        // ON DELETE RESTRICT: borrar la subcategoría dejaría al producto
        // apuntando al vacío.
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('subcategorias')->where('id', $producto->subcategoria_id)->delete();
    }

    // --------------------------------------------------------------------- SKU

    public function test_el_sku_es_codigo_interno_y_no_una_columna_nueva(): void
    {
        $producto = $this->productoClasificado();

        $this->assertSame($producto->codigo_interno, $producto->sku);
        $this->assertFalse(
            DB::getSchemaBuilder()->hasColumn('productos', 'sku'),
            'No debe existir una columna sku: duplicaría codigo_interno'
        );
    }

    public function test_el_sku_es_obligatorio_y_unico(): void
    {
        $producto = $this->productoClasificado();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Producto::create([
            'codigo_interno' => $producto->codigo_interno,
            'descripcion'    => 'Otro producto con el mismo SKU',
            'unidad'         => 'UNIDAD',
            'operacion'      => 'GRAVADO',
            'estado'         => 1,
        ]);
    }

    public function test_el_sku_viaja_al_comprobante_electronico(): void
    {
        // codigo_interno sale como cac:SellersItemIdentification en el UBL, que
        // es la definicion de SUNAT para "codigo interno del vendedor". Por eso
        // renombrar la columna habria tocado comprobantes ya aceptados.
        $constructor = file_get_contents(app_path('Sunat/ConstructorComprobante.php'));

        $this->assertStringContainsString('codigo_interno', $constructor);
        $this->assertStringContainsString('setCodProducto', $constructor);
    }

    // ------------------------------------------------------------- categorías

    public function test_la_categoria_se_deriva_de_la_subcategoria(): void
    {
        $producto = $this->productoClasificado();

        $this->assertNotNull($producto->subcategoria);
        $this->assertNotNull($producto->categoria());
        $this->assertSame(
            $producto->subcategoria->categoria->id,
            $producto->categoria()->id,
        );
    }

    public function test_productos_no_guarda_categoria_id(): void
    {
        // Guardar las dos permitiria escribir "Bebidas / Arroz". Con una sola
        // columna esa incoherencia no se puede ni representar.
        $this->assertFalse(
            DB::getSchemaBuilder()->hasColumn('productos', 'categoria_id'),
            'productos.categoria_id sería redundante con subcategoria_id'
        );
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('productos', 'subcategoria_id'));
    }

    public function test_un_producto_puede_quedar_sin_clasificar(): void
    {
        $producto = $this->productoClasificado();
        $producto->update(['subcategoria_id' => null]);

        // NULL es "todavía nadie decidió dónde va", no un error.
        $this->assertNull($producto->fresh()->subcategoria_id);
        $this->assertNull($producto->fresh()->categoria());
    }

    public function test_se_puede_filtrar_el_catalogo_por_categoria(): void
    {
        $producto = $this->productoClasificado();
        $categoria = $producto->categoria();

        $encontrados = Producto::whereHas(
            'subcategoria.categoria',
            fn ($q) => $q->where('id', $categoria->id)
        )->pluck('id');

        $this->assertContains($producto->id, $encontrados->all());
    }

    // --------------------------------------------------- el candado de precio

    public function test_un_producto_importado_nace_sin_precio_y_sin_stock(): void
    {
        $producto = $this->productoClasificado(['precio_venta' => 0, 'precio_compra' => 0]);

        $this->assertFalse($producto->tienePrecio());
        $this->assertFalse($producto->estaListoParaVender());
        $this->assertSame(0, DB::table('producto_almacen')->where('producto_id', $producto->id)->count());
    }

    public function test_el_pos_encuentra_un_producto_sin_precio_pero_no_lo_vende(): void
    {
        $this->montarEscenario();

        $producto = $this->productoClasificado(['precio_venta' => 0]);
        DB::table('producto_almacen')->insert([
            'producto_id' => $producto->id,
            'almacen_id'  => $this->almacen->id,
            'stock'       => 50,
        ]);

        // Se encuentra: está en el catálogo y tiene stock.
        $this->actingAs($this->vendedor)
            ->getJson('terminal/search?q='.urlencode($producto->codigo_interno))
            ->assertOk()
            ->assertJsonPath('success', true);

        // Pero no se cobra: vender a 0.00 sería regalar mercadería por un dato
        // que falta.
        $respuesta = $this->vender(
            [['id' => $producto->id, 'cantidad' => 1, 'precio' => 0, 'total' => 0]],
            0.0,
        );

        $respuesta->assertStatus(422);
        $this->assertStringContainsString('precio', strtolower($respuesta->json('message') ?? ''));
        $this->assertSame(0, DB::table('ventas')->where('serie', $this->serieBoleta->serie)->count());
    }

    public function test_con_precio_puesto_el_mismo_producto_si_se_vende(): void
    {
        $this->montarEscenario();

        $producto = $this->productoClasificado(['precio_venta' => 0]);
        DB::table('producto_almacen')->insert([
            'producto_id' => $producto->id,
            'almacen_id'  => $this->almacen->id,
            'stock'       => 50,
        ]);

        // El comercio le pone su precio: recién ahí queda vendible.
        $producto->update(['precio_venta' => 5.00]);

        $this->assertTrue($producto->fresh()->tienePrecio());
        $this->assertTrue($producto->fresh()->estaListoParaVender());
        $this->assertSame(1, Producto::conPrecio()->where('id', $producto->id)->count());
    }

    public function test_el_scope_con_precio_deja_fuera_lo_que_no_se_puede_cobrar(): void
    {
        $sinPrecio = $this->productoClasificado(['precio_venta' => 0]);
        $conPrecio = $this->productoClasificado(['precio_venta' => 3.50]);

        $vendibles = Producto::conPrecio()->pluck('id');

        $this->assertContains($conPrecio->id, $vendibles->all());
        $this->assertNotContains($sinPrecio->id, $vendibles->all());
    }

    // ------------------------------------------------------------------ EAN

    public function test_el_catalogo_no_tiene_ean_de_distribucion_restringida(): void
    {
        // GS1 reserva 02 y 20-29 para etiquetas impresas dentro de un local.
        $internos = Producto::whereNotNull('codigo_barras')
            ->where(function ($q) {
                foreach (['02', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29'] as $prefijo) {
                    $q->orWhere('codigo_barras', 'LIKE', $prefijo.'%');
                }
            })
            ->pluck('codigo_barras');

        $this->assertSame([], $internos->all(), 'Hay códigos internos de tienda guardados como EAN');
    }

    /** Un producto clasificado, con su subcategoría real de la taxonomía. */
    private function productoClasificado(array $extra = []): Producto
    {
        $subcategoria = Subcategoria::query()->firstOrFail();

        return Producto::create(array_merge([
            'codigo_interno'  => 'TEST-'.strtoupper(substr(md5(uniqid('', true)), 0, 10)),
            'descripcion'     => 'Producto de prueba del catálogo real',
            'subcategoria_id' => $subcategoria->id,
            'unidad'          => 'UNIDAD',
            'operacion'       => 'GRAVADO',
            'precio_compra'   => 0,
            'precio_venta'    => 0,
            'estado'          => 1,
        ], $extra));
    }
}
