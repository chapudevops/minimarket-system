<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use DatabaseTransactions;
    use CreaEscenarioDeVenta;

    #[Test]
    public function registra_quien_cambio_un_precio_y_cual_era_antes(): void
    {
        $producto = $this->montarEscenario();
        $this->actingAs($this->vendedor);

        $producto->update(['precio_venta' => 25.90]);

        $registro = Auditoria::latest('id')->first();

        $this->assertSame('ACTUALIZO', $registro->accion);
        $this->assertSame('Producto', $registro->entidad);
        $this->assertSame($this->vendedor->id, $registro->usuario_id);
        $this->assertSame($this->vendedor->name, $registro->usuario_nombre);
        // Ambos lados se guardan con el mismo formato que el cast del modelo.
        $this->assertSame('10.00', $registro->cambios['precio_venta']['antes']);
        $this->assertSame('25.90', $registro->cambios['precio_venta']['despues']);
    }

    #[Test]
    public function registra_quien_anulo_una_venta(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        $this->actingAs($this->vendedor)
            ->postJson("/ventas/{$venta->id}/anular")
            ->assertOk();

        // La accion paso de 'ANULO' a 'VENTA_ANULADA': la bitacora ahora
        // distingue los eventos comerciales (VENTA_ANULADA, DEVOLUCION_*) de
        // los de SUNAT, y 'ANULO' no decia que se anulo.
        $registro = Auditoria::where('accion', 'VENTA_ANULADA')->latest('id')->first();

        $this->assertNotNull($registro, 'anular una venta debe dejar rastro');
        $this->assertSame('Venta', $registro->entidad);
        $this->assertSame($venta->id, (int) $registro->entidad_id);
        $this->assertStringContainsString($venta->documento, $registro->descripcion);
    }

    #[Test]
    public function nunca_guarda_las_credenciales_de_sunat(): void
    {
        $this->montarEscenario();
        $this->actingAs($this->vendedor);

        $empresa = Empresa::first();
        $empresa->update([
            'clave' => 'ClaveSolSecreta',
            'clave_certificado' => 'ClaveDelCertificado',
            'client_secret' => 'secreto-oauth',
            'email_contabilidad' => 'nuevo@prueba.test',
        ]);

        $registro = Auditoria::where('entidad', 'Empresa')->latest('id')->first();
        $serializado = json_encode($registro->cambios);

        // El cambio legible si queda; los secretos no.
        $this->assertArrayHasKey('email_contabilidad', $registro->cambios);
        $this->assertStringNotContainsString('ClaveSolSecreta', $serializado);
        $this->assertStringNotContainsString('ClaveDelCertificado', $serializado);
        $this->assertStringNotContainsString('secreto-oauth', $serializado);
    }

    #[Test]
    public function nunca_guarda_el_hash_de_la_contrasena(): void
    {
        $this->montarEscenario();
        $this->actingAs($this->vendedor);

        $antes = Auditoria::count();
        $this->vendedor->update(['password' => bcrypt('otra-clave')]);

        // Un cambio de contraseña solo no deja entrada: no hay nada legible que
        // mostrar y guardar el hash seria filtrarlo.
        $this->assertSame($antes, Auditoria::count());
    }

    #[Test]
    public function un_update_que_no_cambia_nada_no_ensucia_la_bitacora(): void
    {
        $producto = $this->montarEscenario();
        $this->actingAs($this->vendedor);

        $antes = Auditoria::count();
        $producto->update(['precio_venta' => $producto->precio_venta]);

        $this->assertSame($antes, Auditoria::count());
    }

    #[Test]
    public function el_nombre_del_usuario_sobrevive_a_su_eliminacion(): void
    {
        $producto = $this->montarEscenario();

        // Un usuario recien creado, sin cajas ni comprobantes: los que si
        // tienen no se pueden borrar, y eso esta bien.
        $efimero = \App\Models\User::create([
            'name' => 'Usuario efimero',
            'email' => 'efimero.' . uniqid() . '@prueba.test',
            'password' => bcrypt('secret'),
            'estado' => 1,
        ]);

        $this->actingAs($efimero);
        $producto->update(['precio_venta' => 99.00]);

        $registro = Auditoria::latest('id')->first();
        $this->assertSame($efimero->id, $registro->usuario_id);

        $efimero->delete();

        // El id queda en null, pero la bitacora sigue diciendo quien fue.
        $registro->refresh();
        $this->assertNull($registro->usuario_id);
        $this->assertSame('Usuario efimero', $registro->usuario_nombre);
    }

    #[Test]
    public function no_deja_borrar_un_usuario_con_historial_y_lo_explica(): void
    {
        $this->montarEscenario();
        $admin = $this->usuarioAdministrador();

        // El vendedor tiene una apertura de caja: la FK lo protege.
        $this->actingAs($admin)
            ->deleteJson("/usuarios/{$this->vendedor->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        // El mensaje no debe filtrar el error de la base.
        $mensaje = $this->actingAs($admin)
            ->deleteJson("/usuarios/{$this->vendedor->id}")
            ->json('message');

        $this->assertStringNotContainsString('SQLSTATE', $mensaje);
        $this->assertStringNotContainsString('FOREIGN KEY', $mensaje);
        $this->assertStringContainsString('Desactivalo', $mensaje);
    }

    #[Test]
    public function solo_el_administrador_ve_la_bitacora(): void
    {
        $this->montarEscenario();

        $this->actingAs($this->vendedor)->get('/auditoria')->assertForbidden();
        $this->actingAs($this->vendedor)->getJson('/auditoria/data')->assertForbidden();
    }

    #[Test]
    public function los_filtros_acotan_el_resultado(): void
    {
        $producto = $this->montarEscenario();
        $this->actingAs($this->vendedor);
        $producto->update(['precio_venta' => 33.00]);

        $admin = $this->usuarioAdministrador();

        $this->actingAs($admin)->getJson('/auditoria/data?entidad=Producto')
            ->assertOk()
            ->assertJsonPath('data.0.entidad', 'Producto');

        $this->actingAs($admin)->getJson('/auditoria/data?entidad=NoExiste')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function usuarioAdministrador(): \App\Models\User
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin de prueba',
            'email' => 'admin.' . uniqid() . '@prueba.test',
            'password' => bcrypt('secret'),
            'estado' => 1,
        ]);

        $rol = \App\Models\Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Total']);
        \Illuminate\Support\Facades\DB::table('user_roles')->insert(['user_id' => $admin->id, 'role_id' => $rol->id]);

        return $admin;
    }
}
