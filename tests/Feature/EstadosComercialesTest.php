<?php

namespace Tests\Feature;

use App\Estados\EstadoDevolucion;
use App\Estados\EstadoDocumento;
use App\Estados\EstadoPago;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use App\Models\NotaCredito;
use App\Models\Serie;
use App\Models\Venta;
use App\Ventas\RegistroDevolucion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El estado comercial de un documento y su estado ante SUNAT son dos cosas
 * distintas. Antes se confundian: la lista de ventas no pintaba el estado
 * comercial (el modelo Venta ni siquiera tenia el accessor), asi que lo unico
 * visible era el estado SUNAT y una venta recien hecha "aparecia" pendiente.
 *
 * Ademas `ventas.estado` cargaba con tres significados a la vez: validez
 * comercial, cobranza (PENDIENTE para ventas a credito) y anulacion.
 *
 * Estos tests fijan la separacion.
 */
class EstadosComercialesTest extends TestCase
{
    use DatabaseTransactions, CreaEscenarioDeVenta;

    private function ventaReciente(): Venta
    {
        return Venta::where('caja_id', $this->caja->id)->latest('id')->firstOrFail();
    }

    /** Serie de notas de credito para la caja del escenario. */
    private function serieNotaCredito(): Serie
    {
        return Serie::create([
            'serie' => 'FC'.random_int(10, 99),
            'correlativo' => 0,
            'tipo_comprobante' => 'NOTA_CREDITO',
            'caja_id' => $this->caja->id,
        ]);
    }

    /** Emite una nota de credito por el endpoint real. */
    private function emitirNota(Venta $venta, array $detalles, string $tipo = 'DEVOLUCION')
    {
        return $this->actingAs($this->vendedor)->postJson('/notas-credito', [
            'venta_id' => $venta->id,
            'cliente_id' => $venta->cliente_id,
            'motivo' => 'Devolución de prueba',
            'tipo_nota' => $tipo,
            'detalles' => $detalles,
        ]);
    }

    /* ---------------- VENTAS ---------------- */

    #[Test]
    public function una_venta_nueva_nace_aprobada_y_sin_enviar_a_sunat(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);

        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)
            ->assertOk();

        $venta = $this->ventaReciente();

        $this->assertSame(EstadoVenta::APROBADA, $venta->estado);
        $this->assertSame(EstadoSunat::NO_ENVIADO, $venta->estado_sunat);
        $this->assertSame(EstadoDevolucion::SIN_DEVOLUCION, $venta->estado_devolucion);
        $this->assertSame(EstadoPago::PAGADA, $venta->estado_pago);
        $this->assertTrue($venta->esValida());
    }

    #[Test]
    public function una_venta_a_credito_es_aprobada_pero_queda_por_cobrar(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);

        $this->vender(
            [['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]],
            total: 20.00,
            extra: ['tipo_venta' => 'CREDITO', 'pagado' => 5.00, 'numero_cuotas' => 3]
        )->assertOk();

        $venta = $this->ventaReciente();

        // Antes nacia con estado='PENDIENTE', que se confundia con SUNAT.
        // La venta es valida; lo que falta es el dinero.
        $this->assertSame(EstadoVenta::APROBADA, $venta->estado);
        $this->assertSame(EstadoPago::PENDIENTE, $venta->estado_pago);
        $this->assertTrue($venta->esValida());
    }

    /* ---------------- SUNAT NO MANDA SOBRE EL NEGOCIO ---------------- */

    /**
     * @return list<array{0:string}>
     */
    public static function estadosDeSunat(): array
    {
        return [
            'no enviado' => [EstadoSunat::NO_ENVIADO],
            'en cola' => [EstadoSunat::EN_COLA],
            'enviando' => [EstadoSunat::ENVIANDO],
            'aceptado' => [EstadoSunat::ACEPTADO],
            'observado' => [EstadoSunat::OBSERVADO],
            'rechazado' => [EstadoSunat::RECHAZADO],
            'error' => [EstadoSunat::ERROR],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('estadosDeSunat')]
    public function ningun_estado_de_sunat_altera_el_estado_comercial(string $estadoSunat): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = $this->ventaReciente();
        $venta->update(['estado_sunat' => $estadoSunat]);

        $this->assertSame(
            EstadoVenta::APROBADA,
            $venta->fresh()->estado,
            "El estado SUNAT {$estadoSunat} no debe tocar el estado comercial."
        );
        $this->assertTrue($venta->fresh()->esValida());
    }

    #[Test]
    public function un_rechazo_de_sunat_pide_regularizacion_pero_la_venta_sigue_valida(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = $this->ventaReciente();
        $venta->update(['estado_sunat' => EstadoSunat::RECHAZADO]);

        $this->assertTrue(EstadoSunat::necesitaAtencion($venta->fresh()->estado_sunat));
        $this->assertSame(EstadoVenta::APROBADA, $venta->fresh()->estado);
        $this->assertFalse(EstadoSunat::esReintentable(EstadoSunat::RECHAZADO));
    }

    #[Test]
    public function la_lista_de_ventas_muestra_los_dos_estados_por_separado(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();
        $this->ventaReciente()->update(['estado_sunat' => EstadoSunat::RECHAZADO]);

        $fila = collect($this->actingAs($this->vendedor)->getJson('/ventas/data')->json('data'))->first();

        // El caso que motivo todo: SUNAT rechazado, venta aprobada.
        $this->assertStringContainsString('Aprobada', $fila['estado_badge']);
        $this->assertStringContainsString('Rechazado', $fila['sunat']);
        $this->assertSame(EstadoVenta::APROBADA, $fila['estado']);
    }

    /* ---------------- DEVOLUCIONES ---------------- */

    #[Test]
    public function una_devolucion_parcial_no_anula_la_venta_y_devuelve_su_stock(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 5, 'precio' => 10.00]], total: 50.00)->assertOk();
        $venta = $this->ventaReciente();
        $this->assertSame(5, $this->stockDe($producto));

        $this->emitirNota($venta, [[
            'producto_id' => $producto->id, 'cantidad' => 1,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertOk();

        $venta->refresh();

        $this->assertSame(EstadoVenta::APROBADA, $venta->estado, 'Devolver 1 de 5 no anula la venta.');
        $this->assertSame(EstadoDevolucion::PARCIAL, $venta->estado_devolucion);
        $this->assertSame(6, $this->stockDe($producto), 'La unidad devuelta vuelve al almacén.');
        $this->assertSame(1, $venta->unidadesDevueltas());
    }

    #[Test]
    public function una_devolucion_total_conserva_la_venta_como_aprobada(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 3, 'precio' => 10.00]], total: 30.00)->assertOk();
        $venta = $this->ventaReciente();

        $this->emitirNota($venta, [[
            'producto_id' => $producto->id, 'cantidad' => 3,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertOk();

        $venta->refresh();

        // ANULADA y DEVOLUCION_TOTAL no son lo mismo: la venta ocurrio, se
        // facturo y despues se devolvio. Perder esa diferencia borraria el
        // rastro contable.
        $this->assertSame(EstadoVenta::APROBADA, $venta->estado);
        $this->assertSame(EstadoDevolucion::TOTAL, $venta->estado_devolucion);
        $this->assertSame(10, $this->stockDe($producto));
    }

    #[Test]
    public function no_se_puede_devolver_mas_de_lo_vendido(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)->assertOk();
        $venta = $this->ventaReciente();

        $this->emitirNota($venta, [[
            'producto_id' => $producto->id, 'cantidad' => 5,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertStatus(422);

        $this->assertSame(0, NotaCredito::where('venta_id', $venta->id)->count());
        $this->assertSame(8, $this->stockDe($producto), 'Un rechazo no debe tocar el stock.');
    }

    #[Test]
    public function la_segunda_nota_cuenta_lo_ya_devuelto(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 4, 'precio' => 10.00]], total: 40.00)->assertOk();
        $venta = $this->ventaReciente();

        $linea = fn (int $cantidad) => [[
            'producto_id' => $producto->id, 'cantidad' => $cantidad,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]];

        $this->emitirNota($venta, $linea(3))->assertOk();
        // Ya se devolvieron 3 de 4: pedir 2 mas debe rebotar.
        $this->emitirNota($venta, $linea(2))->assertStatus(422);
        // Pero 1 mas si cabe, y completa la devolucion.
        $this->emitirNota($venta, $linea(1))->assertOk();

        $venta->refresh();

        $this->assertSame(4, $venta->unidadesDevueltas());
        $this->assertSame(EstadoDevolucion::TOTAL, $venta->estado_devolucion);
        $this->assertSame(10, $this->stockDe($producto), 'El stock vuelve una sola vez por unidad.');
    }

    #[Test]
    public function no_se_puede_devolver_un_producto_que_no_estaba_en_la_venta(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $ajeno = $this->crearProducto(stock: 5);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $this->emitirNota($this->ventaReciente(), [[
            'producto_id' => $ajeno->id, 'cantidad' => 1,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertStatus(422);

        $this->assertSame(5, $this->stockDe($ajeno));
    }

    #[Test]
    public function anular_no_se_puede_usar_como_devolucion(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)->assertOk();
        $venta = $this->ventaReciente();

        $this->emitirNota($venta, [[
            'producto_id' => $producto->id, 'cantidad' => 1,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertOk();

        // Con nota de credito de por medio, anular sumaria el stock dos veces.
        $this->actingAs($this->vendedor)
            ->postJson("/ventas/{$venta->id}/anular")
            ->assertStatus(422);

        $this->assertSame(EstadoVenta::APROBADA, $venta->fresh()->estado);
        $this->assertSame(9, $this->stockDe($producto));
    }

    /* ---------------- NOTAS DE CREDITO ---------------- */

    #[Test]
    public function la_nota_de_credito_nace_registrada_y_sin_enviar(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();
        $this->emitirNota($this->ventaReciente(), [[
            'producto_id' => $producto->id, 'cantidad' => 1,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertOk();

        $nota = NotaCredito::latest('id')->firstOrFail();

        $this->assertSame(EstadoDocumento::REGISTRADA, $nota->estado);
        $this->assertSame(EstadoSunat::NO_ENVIADO, $nota->estado_sunat);
    }

    #[Test]
    public function el_rechazo_de_sunat_no_revierte_la_nota_de_credito(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);
        $this->serieNotaCredito();

        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)->assertOk();
        $venta = $this->ventaReciente();
        $this->emitirNota($venta, [[
            'producto_id' => $producto->id, 'cantidad' => 1,
            'precio_unitario' => 10.00, 'almacen_id' => $this->almacen->id,
        ]])->assertOk();

        $nota = NotaCredito::latest('id')->firstOrFail();
        $nota->update(['estado_sunat' => EstadoSunat::RECHAZADO]);

        // La nota sigue existiendo, el stock sigue devuelto y la venta sigue
        // marcada como devuelta parcialmente.
        $this->assertSame(EstadoDocumento::REGISTRADA, $nota->fresh()->estado);
        $this->assertSame(9, $this->stockDe($producto));
        $this->assertSame(EstadoDevolucion::PARCIAL, $venta->fresh()->estado_devolucion);
    }

    /* ---------------- SERVICIO DE DEVOLUCION ---------------- */

    #[Test]
    public function el_servicio_rechaza_devolver_sobre_una_venta_anulada(): void
    {
        Queue::fake();
        $producto = $this->montarEscenario(stockInicial: 10);

        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();
        $venta = $this->ventaReciente();
        $venta->update(['estado' => EstadoVenta::ANULADA]);

        $this->expectException(ValidationException::class);

        (new RegistroDevolucion())->validar($venta->fresh(), [
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]);
    }

    #[Test]
    public function las_transiciones_de_venta_estan_acotadas(): void
    {
        $this->assertTrue(EstadoVenta::puedeTransicionar(EstadoVenta::APROBADA, EstadoVenta::ANULADA));
        // Una venta anulada no vuelve atras.
        $this->assertFalse(EstadoVenta::puedeTransicionar(EstadoVenta::ANULADA, EstadoVenta::APROBADA));
    }
}
