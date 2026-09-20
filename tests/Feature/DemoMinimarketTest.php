<?php

namespace Tests\Feature;

use App\Demo\PerfilComercial;
use App\Demo\SimuladorMinimarket;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\Serie;
use App\Models\User;
use App\Sunat\Monto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El escenario DEMO tiene que contar una historia coherente: se compra, entra
 * stock, se vende, se cobra, se gasta y queda utilidad. Si alguna de esas
 * piezas miente, la demo no sirve para enseñar nada.
 *
 * Se simula un periodo corto para que los tests corran rapido; las reglas son
 * las mismas que en el escenario completo.
 */
class DemoMinimarketTest extends TestCase
{
    use DatabaseTransactions;

    private const SEMILLA = 20260920;

    protected function setUp(): void
    {
        parent::setUp();
        $this->montarConfiguracion();
    }

    /** Lo minimo que el simulador necesita encontrar montado. */
    private function montarConfiguracion(): void
    {
        Empresa::firstOrCreate(['ruc' => '20512345678'], [
            'razon_social' => 'MINIMARKET DE PRUEBA S.A.C.',
            'direccion' => 'Av. Prueba 123', 'pais' => 'Perú',
            'departamento' => 'Lima', 'provincia' => 'Lima', 'distrito' => 'Lima', 'estado' => 1,
        ]);

        $caja = Caja::first() ?? Caja::create(['descripcion' => 'Caja demo']);
        $almacen = Almacen::first() ?? Almacen::create([
            'descripcion' => 'Almacen demo', 'establecimiento' => 'Principal',
        ]);

        if (! User::first()) {
            $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total']);
            $u = User::create([
                'name' => 'Admin demo', 'email' => 'demo.'.uniqid().'@prueba.test',
                'password' => Hash::make('secret'), 'caja_id' => $caja->id,
                'almacen_id' => $almacen->id, 'estado' => 1,
            ]);
            DB::table('user_roles')->insert(['user_id' => $u->id, 'role_id' => $rol->id]);
        }

        foreach (['BOLETA' => 'B001', 'FACTURA' => 'F001', 'NOTA_CREDITO' => 'FC01'] as $tipo => $serie) {
            Serie::firstOrCreate(
                ['tipo_comprobante' => $tipo, 'caja_id' => $caja->id],
                ['serie' => $serie, 'correlativo' => 0]
            );
        }

        $this->montarCatalogo($almacen);
    }

    /**
     * Catalogo minimo propio del test.
     *
     * No se depende del catalogo de desarrollo: los tests corren contra una
     * base que solo tiene esquema y datos de referencia, asi que sin esto el
     * simulador no encontraba ningun producto y generaba un escenario vacio
     * (cero compras, cero ventas y solo gastos, o sea perdidas).
     */
    private function montarCatalogo(Almacen $almacen): void
    {
        // La comprobacion mira productos CON categoria enlazada, no cualquier
        // producto: el simulador une productos-subcategorias-categorias, asi
        // que una fila suelta sin subcategoria no le sirve de nada.
        $yaHay = DB::table('productos as p')
            ->join('subcategorias as s', 's.id', '=', 'p.subcategoria_id')
            ->where('p.precio_venta', '>', 0)
            ->exists();

        if ($yaHay) {
            return;
        }

        // [categoria, subcategoria, cuantos, precio de gondola con IGV]
        //
        // El tamaño importa: los gastos fijos (alquiler, servicios) no
        // dependen del surtido, asi que una tienda con 50 referencias no los
        // cubre y el escenario daria perdidas por construccion. Se monta un
        // catalogo del orden del real (~150) para que el test mida lo que
        // dice medir y no el tamaño del catalogo de prueba.
        $familias = [
            ['ABARROTES', 'ACEITES', 16, 12.90],
            ['ABARROTES', 'FIDEOS', 16, 4.50],
            ['BEBIDAS', 'GASEOSAS', 18, 7.50],
            ['BEBIDAS', 'AGUA', 14, 2.50],
            ['LACTEOS', 'YOGURT', 14, 6.90],
            ['GALLETAS Y DULCES', 'CHOCOLATES', 18, 2.50],
            ['SNACKS', 'SNACKS SALADOS', 14, 3.50],
            ['LIMPIEZA', 'DETERGENTES', 14, 14.90],
            ['HIGIENE PERSONAL', 'JABONES', 14, 4.90],
            ['LICORES', 'CERVEZA', 12, 9.50],
        ];

        foreach ($familias as [$categoria, $subcategoria, $cuantos, $precio]) {
            $catId = DB::table('categorias')->where('nombre', $categoria)->value('id')
                ?? DB::table('categorias')->insertGetId([
                    'nombre' => $categoria, 'codigo' => substr(md5($categoria), 0, 6), 'estado' => 1,
                ]);

            $subId = DB::table('subcategorias')
                ->where('nombre', $subcategoria)->where('categoria_id', $catId)->value('id')
                ?? DB::table('subcategorias')->insertGetId([
                    'nombre' => $subcategoria, 'codigo' => substr(md5($subcategoria), 0, 6),
                    'categoria_id' => $catId, 'estado' => 1,
                ]);

            for ($i = 1; $i <= $cuantos; $i++) {
                $productoId = DB::table('productos')->insertGetId([
                    'codigo_interno' => 'DEMO-'.substr(md5($subcategoria.$i), 0, 8),
                    'descripcion' => "{$subcategoria} de prueba {$i}",
                    'unidad' => 'UNIDAD',
                    'subcategoria_id' => $subId,
                    'operacion' => 'GRAVADO',
                    'tipo_producto' => 'PRODUCTO',
                    // El costo lo fija el simulador; aqui solo el de gondola.
                    'precio_compra' => 0,
                    'precio_venta' => round($precio * (0.8 + $i / 10), 2),
                    'stock_minimo' => 10,
                    'detraccion' => 0,
                    'estado' => 1,
                ]);

                DB::table('producto_almacen')->insert([
                    'producto_id' => $productoId, 'almacen_id' => $almacen->id, 'stock' => 0,
                ]);
            }
        }
    }

    private function generar(int $dias = 60, int $semilla = self::SEMILLA): array
    {
        return (new SimuladorMinimarket($semilla, $dias, escribir: true))->ejecutar();
    }

    /* ---------- Seguridad ---------- */

    #[Test]
    public function el_comando_no_corre_en_produccion(): void
    {
        $this->app['env'] = 'production';

        $salida = Artisan::call('demo:minimarket', ['--confirmar' => true]);

        $this->assertSame(1, $salida, 'El comando debe rechazar production');
        $this->assertStringContainsString('PROHIBIDO EN PRODUCCIÓN', Artisan::output());
    }

    #[Test]
    public function por_defecto_solo_simula_y_no_escribe(): void
    {
        $ventasAntes = DB::table('ventas')->count();

        Artisan::call('demo:minimarket', ['--simular' => true]);

        $this->assertStringContainsString('no se escribió nada', Artisan::output());
        $this->assertSame($ventasAntes, DB::table('ventas')->count());
    }

    #[Test]
    public function rechaza_periodos_fuera_del_rango(): void
    {
        $this->assertSame(1, Artisan::call('demo:minimarket', ['--dias' => 10]));
        $this->assertSame(1, Artisan::call('demo:minimarket', ['--dias' => 400]));
    }

    /* ---------- Reproducibilidad ---------- */

    #[Test]
    public function la_misma_semilla_produce_el_mismo_escenario(): void
    {
        $a = $this->generar();
        $huellaA = $this->huella();

        $b = $this->generar();
        $huellaB = $this->huella();

        $this->assertSame($a['ventas'], $b['ventas']);
        $this->assertSame($huellaA, $huellaB, 'Dos corridas con la misma semilla deben coincidir al céntimo');
    }

    /** @return array<string,mixed> cifras que resumen el escenario */
    private function huella(): array
    {
        return [
            'ventas' => DB::table('ventas')->count(),
            'ingresos' => (string) DB::table('ventas')->sum('total'),
            'unidades' => (string) DB::table('venta_detalles')->sum('cantidad'),
            'compras' => DB::table('compras')->count(),
            'stock' => (string) DB::table('producto_almacen')->sum('stock'),
        ];
    }

    /* ---------- Circuito compra -> stock -> venta ---------- */

    #[Test]
    public function las_compras_generan_el_stock(): void
    {
        $this->generar();

        $comprado = (int) DB::table('compra_detalles')->sum('cantidad');
        $vendido = (int) DB::table('venta_detalles')->sum('cantidad');
        $enAlmacen = (int) DB::table('producto_almacen')->sum('stock');
        $devuelto = (int) DB::table('nota_credito_detalles')->sum('cantidad');

        $this->assertGreaterThan(0, $comprado);
        // Todo lo comprado o esta vendido o esta en el almacen.
        $this->assertSame($comprado - $vendido + $devuelto, $enAlmacen);
    }

    #[Test]
    public function el_stock_nunca_queda_negativo(): void
    {
        $this->generar();

        $this->assertSame(0, DB::table('producto_almacen')->where('stock', '<', 0)->count());
    }

    #[Test]
    public function no_se_vende_nada_antes_de_haberlo_comprado(): void
    {
        $this->generar();

        $primeraCompra = DB::table('compras')->min('fecha_emision');
        $ventasAnteriores = DB::table('ventas')->whereDate('fecha_emision', '<', $primeraCompra)->count();

        $this->assertSame(0, $ventasAnteriores);
    }

    #[Test]
    public function los_agotados_llegaron_a_cero_vendiendo(): void
    {
        $this->generar();

        // Un agotado que nunca se vendio seria una alerta inventada.
        $artificiales = DB::table('producto_almacen as pa')
            ->whereIn('pa.producto_id', fn ($q) => $q->select('producto_id')->from('compra_detalles'))
            ->whereNotIn('pa.producto_id', fn ($q) => $q->select('producto_id')->from('venta_detalles'))
            ->where('pa.stock', 0)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('compra_detalles as cd')
                ->whereColumn('cd.producto_id', 'pa.producto_id'))
            ->count();

        $this->assertSame(0, $artificiales);
    }

    /* ---------- Costos y precios ---------- */

    #[Test]
    public function ningun_producto_vendido_cuesta_mas_de_lo_que_se_cobra(): void
    {
        $this->generar();

        // Se compara contra la venta NETA: el precio de gondola trae IGV y el
        // costo no, asi que compararlos directamente daria un margen falso.
        $enPerdida = DB::table('productos as p')
            ->whereIn('p.id', fn ($q) => $q->select('producto_id')->from('venta_detalles'))
            ->whereRaw('p.precio_compra >= p.precio_venta / 1.18')
            ->count();

        $this->assertSame(0, $enPerdida);
    }

    #[Test]
    public function el_margen_varia_entre_familias(): void
    {
        $golosina = PerfilComercial::margen(1, 'GALLETAS Y DULCES', 'CHOCOLATES');
        $aceite = PerfilComercial::margen(1, 'ABARROTES', 'ACEITES');

        $this->assertGreaterThan($aceite, $golosina, 'Una golosina deja más margen que el aceite');
        $this->assertGreaterThan(0.10, $aceite);
        $this->assertLessThan(0.40, $golosina);
    }

    #[Test]
    public function el_margen_de_un_producto_es_estable(): void
    {
        $primero = PerfilComercial::margen(4242, 'BEBIDAS', 'GASEOSAS');
        $segundo = PerfilComercial::margen(4242, 'BEBIDAS', 'GASEOSAS');

        $this->assertSame($primero, $segundo);
    }

    /* ---------- Resultado ---------- */

    #[Test]
    public function el_escenario_termina_siendo_rentable(): void
    {
        $this->generar();

        $ingresoNeto = (float) DB::table('ventas')->where('estado', EstadoVenta::APROBADA)->sum('subtotal');
        $costo = (float) DB::table('venta_detalles as d')
            ->join('ventas as v', 'v.id', '=', 'd.venta_id')
            ->join('productos as p', 'p.id', '=', 'd.producto_id')
            ->where('v.estado', EstadoVenta::APROBADA)
            ->sum(DB::raw('d.cantidad * p.precio_compra'));
        $gastos = (float) DB::table('gastos')->sum('monto');

        $utilidadBruta = $ingresoNeto - $costo;
        $resultado = $utilidadBruta - $gastos;
        $margen = $utilidadBruta / max(0.01, $ingresoNeto);

        $this->assertGreaterThan(0, $resultado, 'El escenario debe cerrar en positivo');
        // Ni regalado ni absurdo: un minimarket no deja 60% de margen.
        $this->assertGreaterThan(0.15, $margen);
        $this->assertLessThan(0.40, $margen);
    }

    #[Test]
    public function los_gastos_reducen_el_resultado(): void
    {
        $this->generar();

        $gastos = (float) DB::table('gastos')->sum('monto');

        $this->assertGreaterThan(0, $gastos, 'Sin gastos el resultado operativo no significa nada');
        $this->assertSame(0, DB::table('gastos')->where('monto', '<=', 0)->count());
    }

    #[Test]
    public function el_comando_de_validacion_confirma_el_escenario(): void
    {
        $this->generar();

        $this->assertSame(0, Artisan::call('demo:validar-rentabilidad'));
        $this->assertStringContainsString('comprobaciones pasan', Artisan::output());
    }

    /* ---------- Caja ---------- */

    #[Test]
    public function cada_dia_con_ventas_tiene_su_caja_abierta(): void
    {
        $this->generar();

        $sinCaja = DB::table('ventas as v')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('apertura_cajas as a')
                ->whereRaw('DATE(a.fecha_apertura) = DATE(v.fecha_emision)'))
            ->count();

        $this->assertSame(0, $sinCaja);
    }

    #[Test]
    public function los_cierres_de_caja_cuadran(): void
    {
        $this->generar();

        // Declarado = fondo inicial + efectivo cobrado - gastos del dia.
        $descuadres = 0;
        $cerradas = DB::table('apertura_cajas')->where('estado', 'CERRADA')->get();

        foreach ($cerradas as $caja) {
            $efectivo = (float) DB::table('ventas')
                ->whereDate('fecha_emision', $caja->fecha_apertura)
                ->where('forma_pago', 'EFECTIVO')
                ->where('estado', EstadoVenta::APROBADA)
                ->sum('total');
            $gastos = (float) DB::table('gastos')->whereDate('fecha_emision', $caja->fecha_apertura)->sum('monto');

            if (abs(((float) $caja->monto_inicial + $efectivo - $gastos) - (float) $caja->monto_cierre) > 0.05) {
                $descuadres++;
            }
        }

        $this->assertGreaterThan(0, $cerradas->count());
        $this->assertSame(0, $descuadres);
    }

    /* ---------- Devoluciones ---------- */

    #[Test]
    public function las_devoluciones_devuelven_stock_y_no_exceden_lo_vendido(): void
    {
        $this->generar();

        $this->assertGreaterThan(0, DB::table('notas_credito')->count());

        $excesos = DB::table('nota_credito_detalles as nd')
            ->join('notas_credito as n', 'n.id', '=', 'nd.nota_credito_id')
            ->joinSub(
                DB::table('venta_detalles')->select('venta_id', 'producto_id', DB::raw('SUM(cantidad) vendido'))
                    ->groupBy('venta_id', 'producto_id'),
                'ven',
                fn ($j) => $j->on('ven.venta_id', '=', 'n.venta_id')->on('ven.producto_id', '=', 'nd.producto_id')
            )
            ->whereColumn('nd.cantidad', '>', 'ven.vendido')
            ->count();

        $this->assertSame(0, $excesos);
    }

    #[Test]
    public function ninguna_nota_de_credito_es_anterior_a_su_venta(): void
    {
        $this->generar();

        $this->assertSame(0, DB::table('notas_credito as n')
            ->join('ventas as v', 'v.id', '=', 'n.venta_id')
            ->whereColumn('n.fecha_emision', '<', 'v.fecha_emision')
            ->count());
    }

    /* ---------- SUNAT y catalogo ---------- */

    #[Test]
    public function ningun_documento_demo_se_envia_a_sunat(): void
    {
        $this->generar();

        $this->assertSame(0, DB::table('ventas')->where('estado_sunat', '!=', EstadoSunat::NO_ENVIADO)->count());
        $this->assertSame(0, DB::table('ventas')->whereNotNull('enviado_sunat_at')->count());
        $this->assertSame(0, DB::table('ventas')->whereNotNull('ruta_xml')->count());
        $this->assertSame(0, DB::table('notas_credito')->where('estado_sunat', '!=', EstadoSunat::NO_ENVIADO)->count());
    }

    #[Test]
    public function solo_participan_productos_con_tributacion_resuelta(): void
    {
        $this->generar();

        $sinClasificar = DB::table('productos as p')
            ->whereIn('p.id', fn ($q) => $q->select('producto_id')->from('venta_detalles'))
            ->whereNotIn('p.operacion', \App\Sunat\Tributos::AFECTACIONES)
            ->count();

        $this->assertSame(0, $sinClasificar);
    }

    #[Test]
    public function el_catalogo_maestro_no_se_toca(): void
    {
        $antes = DB::table('productos')
            ->orderBy('id')
            ->get(['id', 'codigo_interno', 'codigo_barras', 'descripcion', 'marca', 'presentacion', 'operacion', 'precio_venta', 'foto']);

        $this->generar();

        $despues = DB::table('productos')
            ->orderBy('id')
            ->get(['id', 'codigo_interno', 'codigo_barras', 'descripcion', 'marca', 'presentacion', 'operacion', 'precio_venta', 'foto']);

        // La simulacion solo puede tocar precio_compra; todo lo demas es
        // catalogo maestro y debe salir igual que entro.
        $this->assertEquals($antes->toArray(), $despues->toArray());
    }

    #[Test]
    public function las_ventas_cuadran_con_su_detalle(): void
    {
        $this->generar();

        $descuadradas = DB::table('ventas as v')
            ->joinSub(
                DB::table('venta_detalles')->select('venta_id', DB::raw('SUM(total) suma'))->groupBy('venta_id'),
                'd',
                fn ($j) => $j->on('d.venta_id', '=', 'v.id')
            )
            ->whereRaw('ABS(v.total - d.suma) > 0.05')
            ->count();

        $this->assertSame(0, $descuadradas);
    }

    #[Test]
    public function el_igv_de_cada_venta_se_desagrega_del_precio_de_gondola(): void
    {
        $this->generar();

        $venta = DB::table('ventas')->orderByDesc('id')->first();
        $esperado = Monto::desagregarIgv((float) $venta->total);

        $this->assertEqualsWithDelta($esperado['gravado'], (float) $venta->subtotal, 0.02);
        $this->assertEqualsWithDelta($esperado['igv'], (float) $venta->igv, 0.02);
    }
}
