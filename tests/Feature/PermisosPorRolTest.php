<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los roles existian en la base pero no se aplicaban en ningun lado: cualquier
 * usuario logueado podia hacer cualquier cosa. Estos tests fijan la matriz.
 */
class PermisosPorRolTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioCon(string $rol): User
    {
        $usuario = User::create([
            'name' => "Usuario {$rol}",
            'email' => strtolower($rol) . '.' . uniqid() . '@prueba.test',
            'password' => Hash::make('secret'),
            'estado' => 1,
        ]);

        $role = Role::firstOrCreate(['nombre' => $rol], ['descripcion' => $rol]);
        DB::table('user_roles')->insert(['user_id' => $usuario->id, 'role_id' => $role->id]);

        return $usuario;
    }

    #[Test]
    #[DataProvider('rutasDelVendedor')]
    public function el_vendedor_entra_a_lo_suyo_y_no_a_lo_demas(string $ruta, int $esperado): void
    {
        $this->actingAs($this->usuarioCon('Vendedor'))
            ->get($ruta)
            ->assertStatus($esperado);
    }

    public static function rutasDelVendedor(): array
    {
        return [
            'ventas'         => ['/ventas', 200],
            'clientes'       => ['/clientes', 200],
            'cotizaciones'   => ['/cotizaciones', 200],
            'gastos'         => ['/gastos', 200],
            'apertura caja'  => ['/apertura-caja', 200],
            'productos'      => ['/productos', 403],
            'almacenes'      => ['/almacenes', 403],
            'compras'        => ['/compras', 403],
            'usuarios'       => ['/usuarios', 403],
            'empresa'        => ['/empresa', 403],
            'series'         => ['/series', 403],
        ];
    }

    #[Test]
    #[DataProvider('rutasDelAlmacenero')]
    public function el_almacenero_entra_a_lo_suyo_y_no_a_lo_demas(string $ruta, int $esperado): void
    {
        $this->actingAs($this->usuarioCon('Almacenero'))
            ->get($ruta)
            ->assertStatus($esperado);
    }

    public static function rutasDelAlmacenero(): array
    {
        return [
            'productos'      => ['/productos', 200],
            'almacenes'      => ['/almacenes', 200],
            'compras'        => ['/compras', 200],
            'proveedores'    => ['/proveedores', 200],
            'guias remision' => ['/guias-remision', 200],
            'ventas'         => ['/ventas', 403],
            'usuarios'       => ['/usuarios', 403],
            'empresa'        => ['/empresa', 403],
        ];
    }

    #[Test]
    public function el_administrador_pasa_aunque_no_este_listado_en_la_ruta(): void
    {
        $admin = $this->usuarioCon('Administrador');

        // 'role:Vendedor' no menciona a Administrador y aun asi debe dejarlo
        // entrar: evita que una ruta nueva lo deje afuera por olvido.
        foreach (['/ventas', '/productos', '/usuarios', '/empresa', '/series'] as $ruta) {
            $this->actingAs($admin)->get($ruta)->assertOk();
        }
    }

    #[Test]
    public function un_usuario_sin_rol_no_entra_a_ninguna_seccion(): void
    {
        $huerfano = User::create([
            'name' => 'Sin rol',
            'email' => 'sinrol.' . uniqid() . '@prueba.test',
            'password' => Hash::make('secret'),
            'estado' => 1,
        ]);

        foreach (['/ventas', '/productos', '/usuarios'] as $ruta) {
            $this->actingAs($huerfano)->get($ruta)->assertForbidden();
        }

        // El dashboard es la unica pantalla comun.
        $this->actingAs($huerfano)->get('/')->assertOk();
    }

    #[Test]
    public function sin_sesion_redirige_al_login(): void
    {
        $this->get('/ventas')->assertRedirect('/login');
    }

    #[Test]
    public function el_403_de_una_peticion_ajax_vuelve_como_json(): void
    {
        $this->actingAs($this->usuarioCon('Vendedor'))
            ->getJson('/productos/data')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function la_ruta_comodin_ya_no_permite_saltear_los_permisos(): void
    {
        // Antes /usuario/index renderizaba la vista de administracion aunque
        // /usuarios devolviera 403.
        $this->actingAs($this->usuarioCon('Vendedor'))
            ->get('/usuario/index')
            ->assertNotFound();
    }
}
