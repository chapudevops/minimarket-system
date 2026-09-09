<?php

namespace Tests\Feature;

use App\Catalogo\Imagenes\EstadoFoto;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El codigo de barras despues de sacar los 33 EAN ficticios.
 *
 * La regla es una sola: un EAN inventado es peor que ninguno. Ninguno hace que
 * el cajero busque por descripcion; uno inventado hace que el lector de al
 * cliente un producto por otro, y el dia que entre el articulo real con su EAN
 * verdadero quedan dos filas para la misma cosa.
 */
class CodigoBarrasTest extends TestCase
{
    use CreaEscenarioDeVenta, DatabaseTransactions;

    #[Test]
    public function un_producto_puede_no_tener_codigo_de_barras(): void
    {
        $this->montarEscenario();

        $producto = $this->crearProducto(10);

        $this->assertNull($producto->fresh()->codigo_barras);
    }

    #[Test]
    public function varios_productos_pueden_quedar_sin_codigo_de_barras(): void
    {
        $this->montarEscenario();

        // El indice unico sobre codigo_barras no debe estorbar aca: en MySQL
        // un UNIQUE admite tantos NULL como haga falta, y la mayoria del
        // catalogo real no tiene EAN verificable.
        $this->crearProducto(5);
        $this->crearProducto(5);
        $this->crearProducto(5);

        $this->assertGreaterThanOrEqual(3, Producto::whereNull('codigo_barras')->count());
    }

    #[Test]
    public function dos_productos_no_pueden_compartir_un_codigo_de_barras_real(): void
    {
        $this->montarEscenario();

        $ean = '900' . random_int(1000000000, 9999999999);

        $primero = $this->crearProducto(5);
        $primero->update(['codigo_barras' => $ean]);

        $segundo = $this->crearProducto(5);

        // Si esto no fallara, el lector cobraria el producto equivocado.
        $this->expectException(QueryException::class);
        $segundo->update(['codigo_barras' => $ean]);
    }

    #[Test]
    public function el_codigo_interno_no_se_guarda_como_codigo_de_barras(): void
    {
        $this->montarEscenario();

        // Los codigos internos se generan solos (BEB-GAS-000001) y eso esta
        // bien; lo que no puede pasar es que uno termine en la columna del EAN
        // haciendose pasar por un codigo comercial.
        $conCodigoInternoEnBarras = Producto::whereNotNull('codigo_barras')
            ->whereColumn('codigo_barras', 'codigo_interno')
            ->count();

        $this->assertSame(0, $conCodigoInternoEnBarras);

        $formatoInterno = Producto::whereNotNull('codigo_barras')
            ->where('codigo_barras', 'REGEXP', '^[A-Z]{3}-[A-Z]{3}-[0-9]{6}$')
            ->count();

        $this->assertSame(0, $formatoInterno);
    }

    #[Test]
    public function no_queda_ningun_ean_sintetico_del_seeder_anterior(): void
    {
        // Los 33 que sembraba CatalogoSeeder. Se listan por prefijo: eran EAN-13
        // con prefijo de pais peruano, que es lo que los hacia creibles.
        $sinteticos = Producto::whereIn('codigo_barras', [
            '7751271001234', '7750670001120', '7751271009876', '7750243012345',
            '7750243098765', '7751500012340', '7751271004567', '7750182001234',
            '7750182005678', '7751271112233', '7750670004455', '7750670007788',
            '7751271556677', '7750243334455', '7751500778899', '7751500223344',
            '7750182998877', '7750182556644', '7751271443322', '7751271667788',
            '7750243112200', '7751500991122', '7751271334466', '7750670553311',
            '7750670884422', '7751271220099', '7751500445566', '7751500667700',
            '7750243776655', '7751271889900', '7751271010101', '7750670020202',
            '7751500030303',
        ])->count();

        $this->assertSame(0, $sinteticos, 'volvieron los codigos de barras inventados');
    }

    #[Test]
    public function la_cadena_vacia_no_se_guarda_como_codigo_de_barras(): void
    {
        $this->montarEscenario();

        // '' no es "sin codigo de barras": es un codigo vacio, y dos productos
        // asi chocan contra el indice unico. El controller lo normaliza a NULL.
        $this->assertSame(0, Producto::where('codigo_barras', '')->count());
    }

    /* --- El POS con productos sin EAN ---------------------------------- */

    #[Test]
    public function el_pos_encuentra_un_producto_sin_codigo_de_barras(): void
    {
        $producto = $this->montarEscenario();

        $this->assertNull($producto->codigo_barras);

        // Por codigo interno, que es como se busca cuando no hay etiqueta.
        $porCodigo = $this->actingAs($this->vendedor)
            ->getJson('/terminal/productos?search=' . $producto->codigo_interno);

        $porCodigo->assertOk();
        $this->assertSame(
            [$producto->id],
            array_column($porCodigo->json('data'), 'id')
        );

        // Y por descripcion.
        $porTexto = $this->actingAs($this->vendedor)
            ->getJson('/terminal/productos?search=Producto de prueba');

        $porTexto->assertOk();
        $this->assertContains($producto->id, array_column($porTexto->json('data'), 'id'));
    }

    #[Test]
    public function el_escaner_no_confunde_el_codigo_interno_con_el_de_barras(): void
    {
        $this->montarEscenario();

        $ean = '900' . random_int(1000000000, 9999999999);

        $conEan = $this->crearProducto(5);
        $conEan->update(['codigo_barras' => $ean]);

        $sinEan = $this->crearProducto(5);

        // Escanear el EAN devuelve SOLO el producto que lo tiene.
        $escaneo = $this->actingAs($this->vendedor)->getJson('/terminal/productos?search=' . $ean);

        $escaneo->assertOk();
        $ids = array_column($escaneo->json('data'), 'id');

        $this->assertContains($conEan->id, $ids);
        $this->assertNotContains($sinEan->id, $ids);
    }

    #[Test]
    public function un_producto_sin_ean_se_vende_igual(): void
    {
        $producto = $this->montarEscenario(20);

        $this->assertNull($producto->codigo_barras);

        $respuesta = $this->vender(
            [['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]],
            20.00
        );

        $respuesta->assertOk()->assertJson(['success' => true]);
        $this->assertSame(18, $this->stockDe($producto));
    }

    /** El modulo de productos es del administrador, no del vendedor. */
    private function administrador(): User
    {
        $usuario = User::create([
            'name' => 'Admin de prueba',
            'email' => 'admin.'.uniqid().'@prueba.test',
            'password' => Hash::make('secret'),
            'caja_id' => $this->caja->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 1,
        ]);

        $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Administrador']);
        DB::table('user_roles')->insert(['user_id' => $usuario->id, 'role_id' => $rol->id]);

        return $usuario;
    }

    #[Test]
    public function editar_un_producto_sin_ean_no_le_inventa_uno(): void
    {
        $this->montarEscenario();

        $producto = $this->crearProducto(5);
        $this->assertNull($producto->codigo_barras);

        // El endpoint de detalle devuelve '-' en codigo_barras para mostrarlo
        // en la ficha. El formulario NO puede llenarse con eso: guardaria '-'
        // como si fuera un EAN, y el segundo producto asi chocaria contra el
        // indice unico. Por eso los valores crudos viajan aparte, en 'form'.
        $detalle = $this->actingAs($this->administrador())
            ->getJson('/productos/'.$producto->id)
            ->assertOk();

        $this->assertSame('-', $detalle->json('data.codigo_barras'));
        $this->assertNull($detalle->json('data.form.codigo_barras'));
    }

    #[Test]
    public function el_formulario_recibe_los_tratamientos_tributarios_crudos(): void
    {
        $this->montarEscenario();

        $producto = $this->crearProducto(5, 'GRAVADO');
        $producto->update(['detraccion' => true, 'afecto_isc' => true]);

        $form = $this->actingAs($this->administrador())
            ->getJson('/productos/'.$producto->id)
            ->assertOk()
            ->json('data.form');

        // Sin esto, editar cualquier producto le apagaba las casillas.
        $this->assertTrue($form['detraccion']);
        $this->assertTrue($form['afecto_isc']);
        $this->assertFalse($form['afecto_ivap']);
        $this->assertSame('GRAVADO', $form['operacion']);
    }

    #[Test]
    public function una_foto_subida_a_mano_queda_marcada_como_propia(): void
    {
        $this->montarEscenario();
        $producto = $this->crearProducto(5);

        \Illuminate\Support\Facades\Storage::fake('local');

        $this->actingAs($this->administrador())
            ->put('/productos/'.$producto->id, [
                'codigo_interno' => $producto->codigo_interno,
                'descripcion' => $producto->descripcion,
                'unidad' => 'UNIDAD',
                'operacion' => 'GRAVADO',
                'tipo_producto' => 'PRODUCTO',
                'precio_compra' => 5,
                'precio_venta' => 9,
                'subcategoria_id' => $producto->subcategoria_id,
                'foto' => UploadedFile::fake()->image('mi-foto.jpg', 200, 200),
            ]);

        $producto->refresh();

        // Sin esta marca la foto quedaba en SIN_IMAGEN y el primer barrido de
        // enriquecimiento la reemplazaba por una de internet.
        $this->assertNotNull($producto->foto);
        $this->assertSame(EstadoFoto::PROPIA, $producto->foto_estado);
        $this->assertSame('PROPIA', $producto->foto_fuente);
        $this->assertFalse(EstadoFoto::sePuedeReemplazar($producto->foto_estado));
    }

    #[Test]
    public function un_ean_real_se_puede_cargar_despues(): void
    {
        $this->montarEscenario();

        $producto = $this->crearProducto(5);
        $this->assertNull($producto->codigo_barras);

        // El flujo previsto: el producto nace sin EAN y lo recibe cuando
        // alguien escanea el articulo fisico o llega la lista del proveedor.
        $ean = '900' . random_int(1000000000, 9999999999);
        $producto->update(['codigo_barras' => $ean]);

        $this->assertSame($ean, $producto->fresh()->codigo_barras);

        $busqueda = $this->actingAs($this->vendedor)->getJson('/terminal/productos?search=' . $ean);
        $this->assertContains($producto->id, array_column($busqueda->json('data'), 'id'));
    }
}
