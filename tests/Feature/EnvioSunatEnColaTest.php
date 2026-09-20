<?php

namespace Tests\Feature;

use App\Jobs\EnviarComprobanteASunat;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El envío a SUNAT no puede bloquear una venta en el mostrador: su web service
 * se cae seguido. Estos tests fijan que la venta se cierre local y el envío
 * viaje aparte.
 */
class EnvioSunatEnColaTest extends TestCase
{
    use DatabaseTransactions;
    use CreaEscenarioDeVenta;

    #[Test]
    public function una_venta_encola_su_envio_a_sunat(): void
    {
        Queue::fake();

        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)
            ->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        Queue::assertPushed(
            EnviarComprobanteASunat::class,
            fn ($job) => $job->ventaId === $venta->id
        );
    }

    #[Test]
    public function la_venta_se_cierra_aunque_el_envio_no_haya_salido(): void
    {
        Queue::fake();

        $producto = $this->montarEscenario(stockInicial: 10);
        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)
            ->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        // Lo que le importa al mostrador ya pasó: comprobante emitido y stock
        // descontado, sin depender de que SUNAT conteste.
        //
        // Los dos estados son independientes: la venta nace APROBADA y el
        // envio arranca en NO_ENVIADO. Antes la venta nacia 'COMPLETADA' y el
        // envio 'PENDIENTE', y ese 'PENDIENTE' se leia como si la venta
        // estuviera pendiente de algo.
        $this->assertSame(EstadoVenta::APROBADA, $venta->estado);
        $this->assertSame(EstadoSunat::NO_ENVIADO, $venta->estado_sunat);
        $this->assertSame(8, $this->stockDe($producto));
    }

    #[Test]
    public function el_job_no_reenvia_un_comprobante_ya_resuelto(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();
        $venta->update(['estado_sunat' => 'ACEPTADO']);

        // Si otro intento ya lo resolvió, el job en cola no debe volver a
        // enviarlo: seria un duplicado ante SUNAT.
        $enviador = \Mockery::mock(\App\Sunat\EnviadorSunat::class);
        $enviador->shouldNotReceive('enviar');

        (new EnviarComprobanteASunat($venta->id))->handle($enviador);
    }

    #[Test]
    public function el_job_sobre_una_venta_borrada_no_explota(): void
    {
        $enviador = \Mockery::mock(\App\Sunat\EnviadorSunat::class);
        $enviador->shouldNotReceive('enviar');

        (new EnviarComprobanteASunat(999999))->handle($enviador);

        $this->assertTrue(true, 'una venta inexistente se ignora en silencio');
    }

    #[Test]
    public function un_fallo_de_red_deja_el_comprobante_para_reintentar(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        $enviador = \Mockery::mock(\App\Sunat\EnviadorSunat::class);
        // Un timeout ya no devuelve 'PENDIENTE' sino ERROR: es un envio que se
        // intento y fallo, distinto de uno que nunca se intento (NO_ENVIADO).
        $enviador->shouldReceive('enviar')->once()->andReturn([
            'estado' => EstadoSunat::ERROR, 'codigo' => null,
            'mensaje' => 'Connection timed out', 'cdr' => null,
        ]);

        // El job falla a proposito para que la cola lo reintente.
        $this->expectException(\RuntimeException::class);
        (new EnviarComprobanteASunat($venta->id))->handle($enviador);
    }

    #[Test]
    public function un_rechazo_de_sunat_no_se_reintenta(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        $enviador = \Mockery::mock(\App\Sunat\EnviadorSunat::class);
        $enviador->shouldReceive('enviar')->once()->andReturn([
            'estado' => 'RECHAZADO', 'codigo' => '3244',
            'mensaje' => 'Falta el tipo de transacción', 'cdr' => null,
        ]);

        // Un rechazo es definitivo: reintentarlo daria lo mismo y solo gasta
        // intentos, asi que el job termina bien.
        (new EnviarComprobanteASunat($venta->id))->handle($enviador);

        $this->assertTrue(true, 'un rechazo no relanza el job');
    }
}
