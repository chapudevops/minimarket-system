<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Recuperar contraseña estaba roto por dos motivos distintos:
 *
 * 1. MAIL_HOST apuntaba a "mailpit", el nombre del contenedor que trae el
 *    .env.example de Laravel Sail. Corriendo nativo ese host no resuelve, asi
 *    que pedir el enlace reventaba con un 500 en vez de enviar nada.
 *
 * 2. RouteServiceProvider::HOME valia '/index', una ruta que dejo de existir
 *    al quitar el comodin `/{view}`. Aun enviando el correo, al terminar de
 *    restablecer la contraseña el usuario aterrizaba en un 404.
 *
 * Los tests usan Notification::fake(), asi que no dependen de la configuracion
 * de correo de la maquina: comprueban que la notificacion SALE y que el token
 * que lleva sirve para cambiar la clave.
 */
class RecuperarPasswordTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(string $password = 'password'): User
    {
        $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total']);

        $usuario = User::create([
            'name' => 'Usuario recupera',
            'email' => 'recupera.'.uniqid().'@prueba.test',
            'password' => Hash::make($password),
            'estado' => 1,
        ]);
        DB::table('user_roles')->insert(['user_id' => $usuario->id, 'role_id' => $rol->id]);

        return $usuario;
    }

    #[Test]
    public function la_ruta_home_existe(): void
    {
        // HOME es a donde caen el login, el restablecimiento y el middleware
        // de invitados. Si apunta a una ruta inexistente, todos terminan en 404.
        $this->actingAs($this->usuario())
            ->get(RouteServiceProvider::HOME)
            ->assertOk();
    }

    #[Test]
    public function la_pantalla_de_recuperar_abre(): void
    {
        $this->get('/password/reset')->assertOk();
    }

    #[Test]
    public function pedir_el_enlace_envia_la_notificacion(): void
    {
        Notification::fake();
        $usuario = $this->usuario();

        $this->post('/password/email', ['email' => $usuario->email])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    #[Test]
    public function el_enlace_permite_cambiar_la_contrasena_y_entrar_con_ella(): void
    {
        Notification::fake();
        $usuario = $this->usuario('claveVieja123');

        $this->post('/password/email', ['email' => $usuario->email]);

        $token = null;
        Notification::assertSentTo($usuario, ResetPassword::class, function ($notificacion) use (&$token) {
            $token = $notificacion->token;

            return true;
        });

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'claveNueva456',
            'password_confirmation' => 'claveNueva456',
        ])->assertRedirect(RouteServiceProvider::HOME);

        $this->assertTrue(Hash::check('claveNueva456', $usuario->fresh()->password));
    }

    #[Test]
    public function el_token_no_sirve_dos_veces(): void
    {
        Notification::fake();
        $usuario = $this->usuario();

        $this->post('/password/email', ['email' => $usuario->email]);

        $token = null;
        Notification::assertSentTo($usuario, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $datos = [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'primeraClave123',
            'password_confirmation' => 'primeraClave123',
        ];

        $this->post('/password/reset', $datos)->assertRedirect(RouteServiceProvider::HOME);

        // El segundo intento con el mismo enlace debe rebotar.
        $this->post('/password/reset', array_merge($datos, [
            'password' => 'segundaClave123',
            'password_confirmation' => 'segundaClave123',
        ]))->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('primeraClave123', $usuario->fresh()->password));
    }

    #[Test]
    public function un_correo_desconocido_no_cambia_nada(): void
    {
        Notification::fake();

        $this->post('/password/email', ['email' => 'nadie@ninguna.test'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    #[Test]
    public function los_mensajes_del_flujo_estan_en_espanol(): void
    {
        // El resto del sistema esta en español; estas cadenas las emite Laravel
        // y salian en ingles en mitad de la pantalla de recuperar acceso.
        $this->assertSame(
            'Te enviamos un enlace para restablecer tu contraseña.',
            __(Password::RESET_LINK_SENT)
        );
        $this->assertSame(
            'No encontramos ninguna cuenta con ese correo electrónico.',
            __(Password::INVALID_USER)
        );
        $this->assertSame(
            'El enlace de recuperación no es válido o ya expiró.',
            __(Password::INVALID_TOKEN)
        );
    }

    #[Test]
    public function un_usuario_logueado_que_entra_a_login_va_al_dashboard(): void
    {
        // RedirectIfAuthenticated manda a HOME: con HOME roto, esto era un 404.
        $this->actingAs($this->usuario())
            ->get('/login')
            ->assertRedirect(RouteServiceProvider::HOME);
    }
}
