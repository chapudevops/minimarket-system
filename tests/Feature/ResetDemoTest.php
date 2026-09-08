<?php

namespace Tests\Feature;

use App\Sistema\ResetDemo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El comando que borra toda la data operativa.
 *
 * Todo corre dentro de una transaccion que se revierte al terminar, asi que
 * los tests pueden ejecutar el reset de verdad sobre la base de test sin
 * dejarla vacia para el resto de la suite.
 */
class ResetDemoTest extends TestCase
{
    use CreaEscenarioDeVenta;
    use DatabaseTransactions;

    public function test_no_corre_en_produccion_ni_con_confirmar(): void
    {
        $this->app['env'] = 'production';

        $productos = DB::table('productos')->count();

        $this->artisan('sistema:reset-demo --confirmar')
            ->expectsOutputToContain('PROHIBIDO EN PRODUCCIÓN')
            ->assertExitCode(1);

        $this->assertSame($productos, DB::table('productos')->count());
    }

    public function test_por_defecto_solo_simula(): void
    {
        $antes = [
            'productos' => DB::table('productos')->count(),
            'ventas'    => DB::table('ventas')->count(),
            'clientes'  => DB::table('clientes')->count(),
        ];

        $this->artisan('sistema:reset-demo')
            ->expectsOutputToContain('MODO SIMULACIÓN')
            ->assertExitCode(0);

        foreach ($antes as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->count(), "{$tabla} cambió durante la simulación");
        }
    }

    public function test_una_respuesta_equivocada_cancela_todo(): void
    {
        $productos = DB::table('productos')->count();

        $this->artisan('sistema:reset-demo --confirmar')
            ->expectsQuestion('  Escribe LIMPIAR para continuar', 'si')
            ->expectsOutputToContain('Cancelado')
            ->assertExitCode(1);

        $this->assertSame($productos, DB::table('productos')->count());
    }

    public function test_no_acepta_un_si_generico(): void
    {
        $this->artisan('sistema:reset-demo --confirmar')
            ->expectsQuestion('  Escribe LIMPIAR para continuar', 'y')
            ->assertExitCode(1);

        $this->artisan('sistema:reset-demo --confirmar')
            ->expectsQuestion('  Escribe LIMPIAR para continuar', 'limpiar')
            ->assertExitCode(1);
    }

    public function test_el_reset_vacia_las_tablas_operativas(): void
    {
        $this->sembrarProductoConMovimiento();

        (new ResetDemo)->ejecutar();

        foreach (['productos', 'ventas', 'venta_detalles', 'compras', 'compra_detalles',
            'producto_almacen', 'notas_credito', 'notas_debito', 'cotizaciones',
            'notas_venta', 'guias_remision', 'combos', 'gastos', 'apertura_cajas',
            'proveedores', 'auditorias'] as $tabla) {
            $this->assertSame(0, DB::table($tabla)->count(), "{$tabla} quedó con filas");
        }
    }

    public function test_conserva_la_configuracion_del_sistema(): void
    {
        $antes = [];
        foreach (ResetDemo::INTOCABLES as $tabla) {
            $antes[$tabla] = DB::table($tabla)->count();
        }

        (new ResetDemo)->ejecutar();

        foreach ($antes as $tabla => $filas) {
            $this->assertSame($filas, DB::table($tabla)->count(), "{$tabla} perdió filas");
        }
    }

    public function test_sobrevive_el_cliente_generico(): void
    {
        DB::table('clientes')->insert([
            'tipo_documento'      => 'DNI',
            'numero_documento'    => ResetDemo::CLIENTE_GENERICO_DOC,
            'nombre_razon_social' => ResetDemo::CLIENTE_GENERICO_NOMBRE,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        (new ResetDemo)->ejecutar();

        $this->assertSame(1, DB::table('clientes')->count());
        $this->assertDatabaseHas('clientes', [
            'numero_documento' => ResetDemo::CLIENTE_GENERICO_DOC,
        ]);
    }

    public function test_recrea_el_cliente_generico_si_no_existia(): void
    {
        DB::table('clientes')->where('numero_documento', ResetDemo::CLIENTE_GENERICO_DOC)->delete();

        (new ResetDemo)->ejecutar();

        // cotizaciones.cliente_id y notas_venta.cliente_id son NOT NULL: sin
        // cliente generico esos modulos quedarian inutilizables.
        $this->assertDatabaseHas('clientes', [
            'numero_documento' => ResetDemo::CLIENTE_GENERICO_DOC,
        ]);
    }

    public function test_no_toca_los_correlativos_sin_pedirlo(): void
    {
        $this->montarEscenario();
        $serie = $this->serieBoleta->id;
        DB::table('series')->where('id', $serie)->update(['correlativo' => 42]);

        (new ResetDemo)->ejecutar();

        $this->assertSame(42, (int) DB::table('series')->where('id', $serie)->value('correlativo'));
    }

    public function test_reinicia_los_correlativos_a_cero_no_a_uno(): void
    {
        $this->montarEscenario();
        $serie = $this->serieBoleta->id;
        DB::table('series')->where('id', $serie)->update(['correlativo' => 42]);

        (new ResetDemo(reiniciarCorrelativos: true))->ejecutar();

        // correlativo guarda el ULTIMO emitido y el siguiente sale de +1. Con
        // 1 la primera boleta seria la 2 y el numero 1 quedaria sin usar.
        $this->assertSame(0, (int) DB::table('series')->where('id', $serie)->value('correlativo'));
    }

    public function test_conserva_la_flota_salvo_que_se_pida(): void
    {
        DB::table('conductores')->insert([
            'nombre' => 'Conductor de prueba', 'licencia' => 'Q'.random_int(10000000, 99999999),
            'documento' => (string) random_int(10000000, 99999999),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $conductores = DB::table('conductores')->count();

        (new ResetDemo)->ejecutar();
        $this->assertSame($conductores, DB::table('conductores')->count());

        (new ResetDemo(limpiarFlota: true))->ejecutar();
        $this->assertSame(0, DB::table('conductores')->count());
    }

    public function test_deja_una_sola_entrada_en_la_bitacora(): void
    {
        $reset = new ResetDemo;
        $eliminados = $reset->ejecutar();

        $this->assertSame(0, DB::table('auditorias')->count());

        $reset->registrarEnBitacora($eliminados, ['eliminados' => 0, 'fallos' => []]);

        // Una importacion masiva que deja 3.000 lineas de bitacora es una
        // bitacora que nadie va a leer.
        $this->assertSame(1, DB::table('auditorias')->count());
        $this->assertDatabaseHas('auditorias', ['accion' => 'RESET', 'entidad' => 'Sistema']);
    }

    public function test_el_orden_de_borrado_respeta_las_claves_foraneas(): void
    {
        $this->sembrarProductoConMovimiento();

        // Sin FOREIGN_KEY_CHECKS=0: si el orden estuviera mal, MySQL lanzaria
        // una QueryException por violacion de integridad.
        (new ResetDemo)->ejecutar();

        $this->assertSame(0, DB::table('productos')->count());
    }

    public function test_no_borra_carpetas_protegidas(): void
    {
        $this->assertContains('app/empresa/certificados', ResetDemo::CARPETAS_PROTEGIDAS);

        foreach (ResetDemo::CARPETAS_COMPROBANTES as $carpeta) {
            foreach (ResetDemo::CARPETAS_PROTEGIDAS as $protegida) {
                $this->assertFalse(
                    str_starts_with($carpeta, $protegida),
                    "{$carpeta} se solapa con la carpeta protegida {$protegida}"
                );
            }
        }
    }

    public function test_el_inventario_cuenta_sin_modificar(): void
    {
        $productos = DB::table('productos')->count();

        $inventario = (new ResetDemo)->inventario();

        $this->assertSame($productos, $inventario['Catalogo']['productos']);
        $this->assertSame($productos, DB::table('productos')->count());
    }

    /** Un producto con stock y una venta, para probar el orden de borrado. */
    private function sembrarProductoConMovimiento(): void
    {
        // El escenario se arma entero aca: los tests no dependen de que la
        // base local tenga productos, almacenes ni cajas cargados.
        $producto = $this->montarEscenario(stockInicial: 10);

        $venta = DB::table('ventas')->insertGetId([
            'tipo_comprobante' => 'BOLETA',
            'serie'            => $this->serieBoleta->serie,
            'numero'           => '00000001',
            'fecha_emision'    => now(),
            'caja_id'          => $this->caja->id,
            'cliente_id'       => $this->cliente->id,
            'usuario_id'       => $this->vendedor->id,
            'subtotal'         => 1.69,
            'igv'              => 0.31,
            'total'            => 2,
            'estado'           => 'COMPLETADA',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('venta_detalles')->insert([
            'venta_id'    => $venta,
            'producto_id' => $producto->id,
            'almacen_id'  => $this->almacen->id,
            'cantidad'    => 1,
            'precio_unitario' => 2,
            'total'       => 2,
        ]);
    }
}
