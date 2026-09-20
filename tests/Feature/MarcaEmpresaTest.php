<?php

namespace Tests\Feature;

use App\Marca;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El nombre y el logo de la tienda salen de la empresa configurada, en un solo
 * sitio, y cambiarlos ahi tiene que verse en el sistema entero.
 *
 * Dos fallos que motivan estos tests:
 *
 * 1. Marca::nombre() devolvia `nombre_comercial` antes que `razon_social`.
 *    Cambiar la razon social en Configuracion → Empresa no se notaba en
 *    ninguna pantalla, y encima la interfaz decia una cosa y el PDF otra,
 *    porque los comprobantes siempre imprimieron la razon social.
 *
 * 2. Las vistas de sesion apuntaban a un archivo inexistente
 *    (`1788986003_logo.png.png`, con la extension duplicada), asi que el logo
 *    salia roto en el login.
 */
class MarcaEmpresaTest extends TestCase
{
    use DatabaseTransactions;

    private function empresa(array $datos = []): Empresa
    {
        $empresa = Empresa::first() ?? Empresa::create(array_merge([
            'ruc' => '20512345678',
            'razon_social' => 'EMPRESA DE PRUEBA S.A.C.',
            'direccion' => 'Av. Prueba 123',
            'pais' => 'Perú',
            'departamento' => 'Lima',
            'provincia' => 'Lima',
            'distrito' => 'Lima',
            'estado' => 1,
        ], $datos));

        if ($datos !== []) {
            $empresa->update($datos);
        }

        Marca::olvidar();

        return $empresa->fresh();
    }

    private function usuario(): User
    {
        $rol = Role::firstOrCreate(['nombre' => 'Vendedor'], ['descripcion' => 'Ventas']);

        $usuario = User::create([
            'name' => 'Usuario marca',
            'email' => 'marca.'.uniqid().'@prueba.test',
            'password' => Hash::make('secret'),
            'estado' => 1,
        ]);
        DB::table('user_roles')->insert(['user_id' => $usuario->id, 'role_id' => $rol->id]);

        return $usuario;
    }

    #[Test]
    public function manda_la_razon_social_y_no_el_nombre_comercial(): void
    {
        $this->empresa([
            'razon_social' => 'BODEGA NUEVA S.A.C.',
            'nombre_comercial' => 'Nombre viejo sin tocar',
        ]);

        $this->assertSame('BODEGA NUEVA S.A.C.', Marca::nombre());
    }

    #[Test]
    public function sin_razon_social_cae_al_nombre_comercial(): void
    {
        $this->empresa(['razon_social' => '', 'nombre_comercial' => 'Solo comercial']);

        $this->assertSame('Solo comercial', Marca::nombre());
    }

    #[Test]
    public function sin_ningun_nombre_no_se_queda_en_blanco(): void
    {
        $this->empresa(['razon_social' => '', 'nombre_comercial' => '']);

        $this->assertNotSame('', Marca::nombre());
    }

    #[Test]
    public function cambiar_la_razon_social_se_ve_en_el_sidebar_y_el_titulo(): void
    {
        $this->empresa([
            'razon_social' => 'MINIMARKET CAMBIADO S.A.C.',
            'nombre_comercial' => 'Otro nombre distinto',
            'ruc' => '20999888777',
        ]);

        $html = $this->actingAs($this->usuario())->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('MINIMARKET CAMBIADO S.A.C.', $html);
        $this->assertStringContainsString('20999888777', $html);
        // El nombre comercial no debe colarse en el cromo de la aplicacion.
        $this->assertStringNotContainsString('Otro nombre distinto', $html);
    }

    #[Test]
    public function ninguna_pantalla_conserva_el_nombre_de_la_plantilla(): void
    {
        $this->empresa(['razon_social' => 'TIENDA REAL S.A.C.']);
        $usuario = $this->usuario();

        foreach (['/', '/ventas'] as $ruta) {
            $html = $this->actingAs($usuario)->get($ruta)->assertOk()->getContent();

            $this->assertStringNotContainsString('Minimarket-system', $html, "en {$ruta}");
            $this->assertStringNotContainsString('DISTRIBUIDORA BEJAR', $html, "en {$ruta}");
        }
    }

    #[Test]
    public function el_logo_apunta_a_un_archivo_que_existe(): void
    {
        // El bug original: la ruta terminaba en '.png.png' y daba 404.
        $logo = Marca::logo();

        $this->assertStringNotContainsString('.png.png', $logo);

        $relativa = parse_url($logo, PHP_URL_PATH);
        $this->assertFileExists(public_path(ltrim($relativa, '/')));
    }

    #[Test]
    public function el_login_muestra_el_logo_de_la_empresa(): void
    {
        $this->empresa();

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString(basename(parse_url(Marca::logo(), PHP_URL_PATH)), $html);
    }
}
