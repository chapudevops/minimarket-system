<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El boton "Guardar Cambios" de la configuracion de empresa era
 * `type="button"`, pero el unico manejador registrado escucha el evento
 * `submit` del formulario (resources/js/empresa/config.js). Un boton que no
 * es submit no dispara ese evento, asi que el click no hacia absolutamente
 * nada: ni peticion, ni error en consola, ni aviso en pantalla.
 *
 * El fallo era mudo, que es lo que lo hacia dificil de ver. Estos tests fijan
 * las dos mitades: que el boton siga siendo submit, y que el endpoint al que
 * envia guarde de verdad.
 */
class GuardarEmpresaTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total']);

        $this->admin = User::create([
            'name' => 'Admin empresa',
            'email' => 'admin.empresa.'.uniqid().'@prueba.test',
            'password' => Hash::make('secret'),
            'estado' => 1,
        ]);
        DB::table('user_roles')->insert(['user_id' => $this->admin->id, 'role_id' => $rol->id]);

        $this->actingAs($this->admin);
    }

    private function empresa(): Empresa
    {
        return Empresa::first() ?? Empresa::create([
            'ruc' => '20512345678',
            'razon_social' => 'MINIMARKET DE PRUEBA S.A.C.',
            'direccion' => 'Av. Prueba 123',
            'pais' => 'Perú',
            'departamento' => 'Lima',
            'provincia' => 'Lima',
            'distrito' => 'Lima',
            'estado' => 1,
        ]);
    }

    /** @return array<string,string> Datos minimos que exige la validacion. */
    private function camposObligatorios(Empresa $empresa): array
    {
        return [
            'ruc' => $empresa->ruc,
            'razon_social' => $empresa->razon_social,
            'direccion' => $empresa->direccion,
            'pais' => $empresa->pais,
            'departamento' => $empresa->departamento,
            'provincia' => $empresa->provincia,
            'distrito' => $empresa->distrito,
        ];
    }

    #[Test]
    public function el_boton_de_guardar_es_submit(): void
    {
        // Con type="button" el click no dispara el submit del formulario y el
        // guardado no se ejecuta nunca.
        $html = $this->get('/empresa')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="btnGuardar"[^>]*>/',
            $html,
            'No se encontro el boton de guardar en la configuracion de empresa.'
        );

        preg_match('/<button[^>]*id="btnGuardar"[^>]*>/', $html, $m);

        $this->assertStringContainsString(
            'type="submit"',
            $m[0],
            'El boton de guardar debe ser type="submit": el manejador de config.js escucha el submit del formulario.'
        );
    }

    #[Test]
    public function el_boton_vive_dentro_del_formulario(): void
    {
        // Un submit fuera del <form> tampoco lo envia.
        $html = $this->get('/empresa')->assertOk()->getContent();

        $inicio = strpos($html, '<form id="empresaForm"');
        $this->assertNotFalse($inicio, 'No se encontro el formulario de empresa.');

        $fin = strpos($html, '</form>', $inicio);
        $formulario = substr($html, $inicio, $fin - $inicio);

        $this->assertStringContainsString('id="btnGuardar"', $formulario);
    }

    #[Test]
    public function guardar_persiste_los_cambios(): void
    {
        $empresa = $this->empresa();

        $respuesta = $this->post('/empresa/'.$empresa->id, $this->camposObligatorios($empresa) + [
            'nombre_comercial' => 'Bodega Nueva',
            'email_contabilidad' => 'contabilidad@prueba.test',
        ]);

        $respuesta->assertOk()->assertJson(['success' => true]);

        $this->assertSame('Bodega Nueva', $empresa->fresh()->nombre_comercial);
    }

    #[Test]
    public function un_ruc_invalido_responde_con_el_error_del_campo(): void
    {
        $empresa = $this->empresa();

        $this->post('/empresa/'.$empresa->id, array_merge($this->camposObligatorios($empresa), ['ruc' => '123']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['ruc']]);
    }

    #[Test]
    public function guardar_otros_datos_no_borra_las_credenciales(): void
    {
        $empresa = $this->empresa();
        $empresa->update(['clave' => 'clave-sunat', 'client_secret' => 'secreto']);

        // Los campos de contraseña se pintan vacios: enviarlos en blanco
        // significa "no la cambies", no "borrala".
        $this->post('/empresa/'.$empresa->id, $this->camposObligatorios($empresa) + [
            'nombre_comercial' => 'Otro nombre',
            'clave' => '',
            'client_secret' => '',
        ])->assertOk();

        $fresca = $empresa->fresh();
        $this->assertSame('clave-sunat', $fresca->clave);
        $this->assertSame('secreto', $fresca->client_secret);
    }

    #[Test]
    public function solo_el_administrador_entra_a_la_configuracion(): void
    {
        $rol = Role::firstOrCreate(['nombre' => 'Vendedor'], ['descripcion' => 'Ventas']);
        $vendedor = User::create([
            'name' => 'Vendedor empresa',
            'email' => 'vendedor.empresa.'.uniqid().'@prueba.test',
            'password' => Hash::make('secret'),
            'estado' => 1,
        ]);
        DB::table('user_roles')->insert(['user_id' => $vendedor->id, 'role_id' => $rol->id]);

        $this->actingAs($vendedor)->get('/empresa')->assertStatus(403);
    }
}
