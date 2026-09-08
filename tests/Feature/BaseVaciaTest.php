<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use App\Sistema\ResetDemo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Una instalacion recien reseteada tiene que abrir entera.
 *
 * Es el escenario que ningun modulo ve durante el desarrollo, porque la base
 * local siempre tuvo datos: cero productos, cero ventas, cero stock. Ahi es
 * donde aparecen la division por cero del dashboard, el array vacio que se
 * indexa igual y el grafico que se dibuja sin series.
 *
 * Cada test arma su propio escenario minimo. No dependen de que la base local
 * tenga nada cargado — que es justamente lo que acabamos de borrar.
 */
class BaseVaciaTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Estado post-reset: solo configuracion, cero data operativa.
        (new ResetDemo)->ejecutar();

        Empresa::firstOrCreate(
            ['ruc' => '20512345678'],
            [
                'razon_social' => 'MINIMARKET DE PRUEBA S.A.C.',
                'direccion'    => 'Av. Prueba 123',
                'pais'         => 'Perú',
                'departamento' => 'Lima',
                'provincia'    => 'Lima',
                'distrito'     => 'Lima',
                'estado'       => 1,
            ]
        );

        $caja = Caja::create(['descripcion' => 'Caja principal '.uniqid()]);
        $almacen = Almacen::create([
            'descripcion'     => 'Almacen principal '.uniqid(),
            'establecimiento' => 'Oficina Principal',
        ]);

        $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total']);

        $this->admin = User::create([
            'name'       => 'Admin de prueba',
            'email'      => 'admin.'.uniqid().'@prueba.test',
            'password'   => Hash::make('secret'),
            'caja_id'    => $caja->id,
            'almacen_id' => $almacen->id,
            'estado'     => 1,
        ]);

        DB::table('user_roles')->insert(['user_id' => $this->admin->id, 'role_id' => $rol->id]);
    }

    public function test_la_base_quedo_vacia_de_data_operativa(): void
    {
        foreach (['productos', 'ventas', 'venta_detalles', 'compras', 'producto_almacen'] as $tabla) {
            $this->assertSame(0, DB::table($tabla)->count(), "{$tabla} no está vacía");
        }

        $this->assertSame(0, (int) DB::table('producto_almacen')->sum('stock'));
    }

    /**
     * El dashboard es el que mas riesgo tiene: promedia, compara contra el mes
     * anterior y dibuja graficos. Sin ventas, todo eso divide por cero.
     */
    public function test_el_dashboard_abre_sin_una_sola_venta(): void
    {
        $this->actingAs($this->admin)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Division by zero')
            ->assertDontSee('Undefined');
    }

    /** @dataProvider pantallas */
    public function test_cada_pantalla_abre_sobre_base_vacia(string $ruta): void
    {
        $this->actingAs($this->admin)
            ->get($ruta)
            ->assertOk()
            ->assertDontSee('Division by zero')
            ->assertDontSee('Undefined variable')
            ->assertDontSee('Undefined index')
            ->assertDontSee('Attempt to read property');
    }

    public static function pantallas(): array
    {
        return [
            'productos'         => ['productos'],
            'crear producto'    => ['productos/create'],
            'compras'           => ['compras'],
            'crear compra'      => ['compras/create'],
            'ventas'            => ['ventas'],
            'cotizaciones'      => ['cotizaciones'],
            'notas de venta'    => ['notas-venta'],
            'notas de credito'  => ['notas-credito'],
            'notas de debito'   => ['notas-debito'],
            'guias de remision' => ['guias-remision'],
            'traslados'         => ['traslados'],
            'combos'            => ['combos'],
            'almacenes'         => ['almacenes'],
            'cajas'             => ['cajas'],
            'apertura de caja'  => ['apertura-caja'],
            'gastos'            => ['gastos'],
            'clientes'          => ['clientes'],
            'proveedores'       => ['proveedores'],
            'usuarios'          => ['usuarios'],
            'series'            => ['series'],
            'empresa'           => ['empresa'],
            'auditoria'         => ['auditoria'],
        ];
    }

    /** @dataProvider endpointsDeDatos */
    public function test_las_tablas_devuelven_una_lista_vacia_sin_romperse(string $ruta): void
    {
        $respuesta = $this->actingAs($this->admin)->getJson($ruta);

        $respuesta->assertOk();

        $cuerpo = $respuesta->json();

        // DataTables server-side: la pantalla tiene que decir "no hay datos",
        // no tirar un 500 porque el resultado vino vacio.
        $this->assertIsArray($cuerpo);
        $this->assertArrayHasKey('data', $cuerpo);
        $this->assertSame(0, (int) ($cuerpo['recordsTotal'] ?? count($cuerpo['data'])));
    }

    public static function endpointsDeDatos(): array
    {
        return [
            'productos/data'      => ['productos/data'],
            'ventas/data'         => ['ventas/data'],
            'proveedores/data'    => ['proveedores/data'],
            'gastos/data'         => ['gastos/data'],
            'cotizaciones/data'   => ['cotizaciones/data'],
            'notas-credito/data'  => ['notas-credito/data'],
            'notas-debito/data'   => ['notas-debito/data'],
            'guias-remision/data' => ['guias-remision/data'],
            'apertura-caja/data'  => ['apertura-caja/data'],
        ];
    }

    public function test_el_pos_busca_sin_productos_y_no_se_rompe(): void
    {
        $this->actingAs($this->admin)
            ->getJson('terminal/search?q=coca')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Sin caja abierta el POS no abre, y esta bien que sea asi: despues del
     * reset no queda ninguna apertura, y vender sin caja abierta dejaria la
     * venta sin arqueo al que imputarse.
     */
    public function test_el_pos_manda_a_abrir_caja_en_vez_de_romperse(): void
    {
        $this->assertSame(0, DB::table('apertura_cajas')->where('estado', 'ABIERTA')->count());

        $this->actingAs($this->admin)
            ->get('terminal')
            ->assertRedirect(route('apertura-caja.index'))
            ->assertSessionHas('error');
    }

    /** Con la caja abierta, el POS abre aunque no haya un solo producto. */
    public function test_el_pos_abre_con_caja_abierta_y_cero_productos(): void
    {
        DB::table('apertura_cajas')->insert([
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'caja_id'        => $this->admin->caja_id,
            'responsable_id' => $this->admin->id,
            'monto_inicial'  => 100,
            'estado'         => 'ABIERTA',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->actingAs($this->admin)
            ->get('terminal')
            ->assertOk()
            ->assertDontSee('Division by zero')
            ->assertDontSee('Undefined variable');
    }

    /** Las dos tablas que legitimamente no vuelven vacias despues del reset. */
    public function test_las_dos_tablas_que_no_quedan_en_cero_son_las_correctas(): void
    {
        // El cliente generico: cotizaciones y notas de venta lo necesitan.
        $this->actingAs($this->admin)
            ->getJson('clientes/data')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Y la bitacora, que no queda en cero porque el reset la deja andando:
        // el propio setUp creo empresa, caja, almacen y usuario, y los registro.
        $this->assertGreaterThan(0, DB::table('auditorias')->count());
    }

    /** El reset no puede dejar la auditoria rota: tiene que seguir grabando. */
    public function test_la_auditoria_sigue_funcionando_despues_del_reset(): void
    {
        DB::table('auditorias')->delete();

        Almacen::create([
            'descripcion'     => 'Almacen post-reset '.uniqid(),
            'establecimiento' => 'Oficina Principal',
        ]);

        $this->assertDatabaseHas('auditorias', ['accion' => 'CREO', 'entidad' => 'Almacen']);
    }

    public function test_el_login_sigue_funcionando(): void
    {
        $this->get('login')->assertOk();

        $this->post('login', [
            'email'    => $this->admin->email,
            'password' => 'secret',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_el_cliente_generico_sobrevivio_al_reset(): void
    {
        // Sin el, cotizaciones y notas de venta no pueden guardarse:
        // su columna cliente_id es NOT NULL.
        $this->assertDatabaseHas('clientes', [
            'numero_documento' => ResetDemo::CLIENTE_GENERICO_DOC,
        ]);
    }
}
