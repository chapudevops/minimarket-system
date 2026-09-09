<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\AperturaCaja;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\OrdenTraslado;
use App\Models\Producto;
use App\Models\ProductoAlmacen;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use App\Sistema\ResetDemo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El panel de alertas emitia una alerta por producto bajo minimo. Con el
 * catalogo real (cientos de articulos) eso llenaba el dashboard de tarjetas y
 * empujaba metricas y graficos fuera de la pantalla, ademas de mandar cientos
 * de registros en el JSON que la campana consulta cada minuto.
 *
 * Estos tests fijan el contrato nuevo: alertas de catalogo agrupadas, lista
 * acotada, total real y orden por prioridad.
 */
class AlertasDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private DashboardService $servicio;
    private User $admin;
    private Almacen $almacen;
    private Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        // Se parte de una instalacion limpia para que las alertas que se
        // cuenten sean solo las que arma cada test.
        (new ResetDemo)->ejecutar();

        $this->servicio = app(DashboardService::class);

        $this->caja = Caja::create(['descripcion' => 'Caja alertas '.uniqid()]);
        $this->almacen = Almacen::create([
            'descripcion' => 'Almacen alertas '.uniqid(),
            'establecimiento' => 'Oficina Principal',
        ]);

        $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total']);

        $this->admin = User::create([
            'name' => 'Admin alertas',
            'email' => 'admin.alertas.'.uniqid().'@prueba.test',
            'password' => Hash::make('secret'),
            'caja_id' => $this->caja->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 1,
        ]);
        DB::table('user_roles')->insert(['user_id' => $this->admin->id, 'role_id' => $rol->id]);

        $this->actingAs($this->admin);
    }

    /** Deja al admin con caja abierta para que no aparezca la alerta de caja. */
    private function abrirCaja(): void
    {
        AperturaCaja::create([
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura' => now(),
            'caja_id' => $this->caja->id,
            'responsable_id' => $this->admin->id,
            'monto_inicial' => 100,
            'estado' => 'ABIERTA',
        ]);
    }

    private function producto(int $stock, int $stockMinimo = 5, float $precioVenta = 10.00): Producto
    {
        $producto = Producto::create([
            'codigo_interno' => 'ALE'.uniqid(),
            'descripcion' => 'Producto alerta '.uniqid(),
            'unidad' => 'UNIDAD',
            'operacion' => 'GRAVADO',
            'tipo_producto' => 'PRODUCTO',
            'precio_compra' => 5.00,
            'precio_venta' => $precioVenta,
            'stock_minimo' => $stockMinimo,
            'detraccion' => 0,
            'estado' => 1,
        ]);

        ProductoAlmacen::create([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'stock' => $stock,
        ]);

        return $producto;
    }

    /** @return list<string> */
    private function tipos(array $alertas): array
    {
        return array_column($alertas['items'], 'tipo');
    }

    #[Test]
    public function sin_problemas_no_hay_ninguna_alerta(): void
    {
        $this->abrirCaja();
        $this->producto(stock: 50);

        $alertas = $this->servicio->getAlertas();

        $this->assertSame([], $alertas['items']);
        $this->assertSame(0, $alertas['total']);
    }

    #[Test]
    public function el_dashboard_muestra_el_estado_vacio_cuando_no_hay_alertas(): void
    {
        $this->abrirCaja();
        $this->producto(stock: 50);

        $this->get('/')
            ->assertOk()
            ->assertSee('No hay alertas pendientes.')
            // Sin alertas no debe quedar el contenedor con scroll ocupando sitio.
            // Se busca el div, no la clase suelta: el nombre tambien aparece en
            // el bloque <style> de la pagina.
            ->assertDontSee('flex-column gap-2 alertas-scroll');
    }

    #[Test]
    public function una_sola_alerta_se_muestra_con_el_nombre_del_producto(): void
    {
        $this->abrirCaja();
        $producto = $this->producto(stock: 2, stockMinimo: 5);

        $alertas = $this->servicio->getAlertas();

        $this->assertSame(1, $alertas['total']);
        $this->assertCount(1, $alertas['items']);
        $this->assertSame('stock', $alertas['items'][0]['tipo']);
        // Con un unico producto sigue valiendo la pena nombrarlo.
        $this->assertStringContainsString($producto->descripcion, $alertas['items'][0]['mensaje']);
        $this->assertStringContainsString('(2/5)', $alertas['items'][0]['mensaje']);
    }

    #[Test]
    public function cientos_de_productos_bajo_minimo_producen_una_sola_alerta(): void
    {
        $this->abrirCaja();

        for ($i = 0; $i < 150; $i++) {
            $this->producto(stock: 1, stockMinimo: 10);
        }

        $alertas = $this->servicio->getAlertas();

        // Lo importante: 150 productos, UNA alerta.
        $this->assertSame(1, $alertas['total']);
        $this->assertCount(1, $alertas['items']);
        $this->assertSame('stock', $alertas['items'][0]['tipo']);
        $this->assertSame(150, $alertas['items'][0]['cantidad']);
        $this->assertStringContainsString('150 productos', $alertas['items'][0]['mensaje']);
    }

    #[Test]
    public function cientos_de_productos_sin_precio_producen_una_sola_alerta(): void
    {
        $this->abrirCaja();

        for ($i = 0; $i < 200; $i++) {
            // stock_minimo 0 = catalogo sin inventario: no debe alertar por stock,
            // solo por no tener precio.
            $this->producto(stock: 0, stockMinimo: 0, precioVenta: 0);
        }

        $alertas = $this->servicio->getAlertas();

        $this->assertSame(1, $alertas['total']);
        $this->assertSame('sin_precio', $alertas['items'][0]['tipo']);
        $this->assertSame(200, $alertas['items'][0]['cantidad']);
        $this->assertStringContainsString('200 productos', $alertas['items'][0]['mensaje']);
    }

    #[Test]
    public function el_dashboard_no_pinta_una_tarjeta_por_producto(): void
    {
        $this->abrirCaja();

        for ($i = 0; $i < 120; $i++) {
            $this->producto(stock: 0, stockMinimo: 8);
        }

        $html = $this->get('/')->assertOk()->getContent();

        // Una tarjeta por producto serian 120 divs .alert dentro del panel.
        $panel = $this->panelDeAlertas($html);
        $this->assertLessThanOrEqual(
            DashboardService::ALERTAS_POR_DEFECTO,
            substr_count($panel, 'role="alert"'),
            'El panel de alertas pinto mas tarjetas que el limite configurado.'
        );
        $this->assertStringContainsString('120 productos', $panel);
    }

    #[Test]
    public function el_catalogo_sin_inventario_inicial_no_alerta_por_stock(): void
    {
        $this->abrirCaja();

        // Producto recien importado: sin stock y sin minimo definido. Nunca
        // recibio mercaderia, asi que no es un quiebre de stock.
        for ($i = 0; $i < 50; $i++) {
            $this->producto(stock: 0, stockMinimo: 0);
        }

        $alertas = $this->servicio->getAlertas();

        $this->assertNotContains('stock', $this->tipos($alertas));
        $this->assertNotContains('stock_agotado', $this->tipos($alertas));
    }

    #[Test]
    public function un_producto_operativo_en_cero_alerta_como_agotado_y_no_como_stock_bajo(): void
    {
        $this->abrirCaja();
        $this->producto(stock: 0, stockMinimo: 10);
        $this->producto(stock: 3, stockMinimo: 10);

        $alertas = $this->servicio->getAlertas();
        $porTipo = array_column($alertas['items'], 'cantidad', 'tipo');

        $this->assertSame(1, $porTipo['stock_agotado']);
        $this->assertSame(1, $porTipo['stock']);
    }

    #[Test]
    public function las_alertas_salen_ordenadas_por_prioridad(): void
    {
        // Sin abrir caja: la alerta de caja debe encabezar.
        $this->producto(stock: 0, stockMinimo: 5);   // agotado
        $this->producto(stock: 2, stockMinimo: 5);   // stock bajo
        $this->producto(stock: 0, stockMinimo: 0, precioVenta: 0); // sin precio
        $this->crearCotizacionPendiente();
        $this->crearTrasladoPendiente();

        $alertas = $this->servicio->getAlertas();

        $this->assertSame(
            ['caja', 'stock_agotado', 'sin_precio', 'stock', 'cotizacion', 'traslado'],
            $this->tipos($alertas)
        );

        // La prioridad tiene que ser monotona, no solo el orden casual.
        $prioridades = array_column($alertas['items'], 'prioridad');
        $ordenadas = $prioridades;
        sort($ordenadas);
        $this->assertSame($ordenadas, $prioridades);
    }

    #[Test]
    public function la_lista_se_acota_al_limite_pero_el_total_sigue_siendo_el_real(): void
    {
        $this->producto(stock: 0, stockMinimo: 5);
        $this->producto(stock: 2, stockMinimo: 5);
        $this->producto(stock: 0, stockMinimo: 0, precioVenta: 0);
        $this->crearCotizacionPendiente();
        $this->crearTrasladoPendiente();

        $completo = $this->servicio->getAlertas();
        $this->assertSame(6, $completo['total']);

        $acotado = $this->servicio->getAlertas(limite: 2);

        $this->assertCount(2, $acotado['items']);
        $this->assertSame(6, $acotado['total'], 'El total debe contar todas, no solo las mostradas.');
        $this->assertSame(2, $acotado['limite']);
        // Y las que sobreviven al corte son las mas prioritarias.
        $this->assertSame(['caja', 'stock_agotado'], $this->tipos($acotado));
    }

    #[Test]
    public function el_panel_avisa_cuando_muestra_menos_alertas_que_el_total(): void
    {
        $this->producto(stock: 0, stockMinimo: 5);
        $this->producto(stock: 2, stockMinimo: 5);
        $this->crearCotizacionPendiente();

        // Con el limite por defecto (20) caben todas y no hay que avisar nada.
        $this->get('/')->assertOk()->assertDontSee('Mostrando');
    }

    #[Test]
    public function el_limite_por_defecto_es_de_veinte_alertas(): void
    {
        $this->assertSame(20, DashboardService::ALERTAS_POR_DEFECTO);
        $this->assertSame(20, $this->servicio->getAlertas()['limite']);
    }

    #[Test]
    public function el_json_de_la_campana_manda_el_total_y_la_lista_acotada(): void
    {
        for ($i = 0; $i < 80; $i++) {
            $this->producto(stock: 0, stockMinimo: 5);
        }
        $this->crearCotizacionPendiente();

        $respuesta = $this->getJson('/?_notificaciones=1')->assertOk();

        $respuesta->assertJsonStructure(['alertas', 'total']);
        // 80 productos agotados = 1 alerta agrupada, mas caja y cotizacion.
        $this->assertSame(3, $respuesta->json('total'));
        $this->assertLessThanOrEqual(
            DashboardService::ALERTAS_POR_DEFECTO,
            count($respuesta->json('alertas'))
        );
    }

    #[Test]
    public function la_base_vacia_no_rompe_el_dashboard(): void
    {
        // Sin productos, sin ventas, sin caja: solo debe quedar la de caja.
        $alertas = $this->servicio->getAlertas();

        $this->assertSame(1, $alertas['total']);
        $this->assertSame('caja', $alertas['items'][0]['tipo']);

        $this->get('/')->assertOk();
    }

    #[Test]
    public function las_alertas_no_escalan_en_consultas_con_el_tamano_del_catalogo(): void
    {
        $this->abrirCaja();

        for ($i = 0; $i < 100; $i++) {
            $this->producto(stock: 0, stockMinimo: 5);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->servicio->getAlertas();
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        // caja + stock + sin precio + cotizaciones + traslados. Si alguien
        // vuelve a consultar por producto, esto se dispara.
        $this->assertLessThanOrEqual(
            8,
            $consultas,
            "getAlertas() hizo {$consultas} consultas con 100 productos en problemas."
        );
    }

    #[Test]
    public function la_tarjeta_de_stock_bajo_ignora_el_catalogo_sin_minimo(): void
    {
        $this->abrirCaja();

        // Catalogo recien importado: stock 0 y minimo 0.
        for ($i = 0; $i < 40; $i++) {
            $this->producto(stock: 0, stockMinimo: 0);
        }
        // Un unico producto operativo de verdad bajo su minimo.
        $this->producto(stock: 1, stockMinimo: 10);

        $metricas = $this->servicio->getMetrics(date('Y-m-01'), date('Y-m-d'));

        // Antes la tarjeta contaba tambien los 40 del catalogo, porque 0 <= 0,
        // y contradecia al panel de alertas de la misma pantalla.
        $this->assertSame(1, $metricas['productosBajoStock']);
    }

    private function crearCotizacionPendiente(): void
    {
        $cliente = Cliente::create([
            'tipo_documento' => 'DNI',
            'numero_documento' => (string) random_int(10000000, 99999999),
            'nombre_razon_social' => 'Cliente alerta',
            'estado' => 1,
        ]);

        Cotizacion::create([
            'serie' => 'C'.random_int(100, 999),
            'numero' => random_int(1, 99999),
            'fecha_emision' => now(),
            'fecha_validez' => now()->addDays(15)->toDateString(),
            'cliente_id' => $cliente->id,
            'tipo_moneda' => 'PEN',
            'tipo_cambio' => 1,
            'subtotal' => 100,
            'igv' => 18,
            'total' => 118,
            'descuento' => 0,
            'estado' => 'PENDIENTE',
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->admin->id,
        ]);
    }

    private function crearTrasladoPendiente(): void
    {
        $destino = Almacen::create([
            'descripcion' => 'Almacen destino '.uniqid(),
            'establecimiento' => 'Sucursal',
        ]);

        OrdenTraslado::create([
            'serie' => 'OT01',
            'numero' => random_int(1, 99999),
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'almacen_origen_id' => $this->almacen->id,
            'almacen_destino_id' => $destino->id,
            'estado' => 'PENDIENTE',
            'creado_por' => $this->admin->id,
        ]);
    }

    /** Recorta el HTML al panel de alertas, para no contar alerts de otras secciones. */
    private function panelDeAlertas(string $html): string
    {
        $inicio = strpos($html, 'Alertas del Sistema');
        $this->assertNotFalse($inicio, 'No se encontro el panel de alertas en el dashboard.');

        $fin = strpos($html, 'Tarjetas de métricas', $inicio);

        return $fin === false ? substr($html, $inicio) : substr($html, $inicio, $fin - $inicio);
    }
}
