<?php

namespace Tests\Feature;

use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El camino que mueve plata: emitir una venta desde el terminal.
 *
 * Se usa DatabaseTransactions y no RefreshDatabase porque el esquema viene del
 * dump, no de las migraciones: recrear la base dejaria solo las tablas de
 * usuarios. Cada test se revierte al terminar.
 */
class VentaEnTerminalTest extends TestCase
{
    use DatabaseTransactions;
    use CreaEscenarioDeVenta;

    #[Test]
    public function una_venta_descuenta_el_stock_del_almacen(): void
    {
        $producto = $this->montarEscenario(stockInicial: 20);

        $this->vender([
            ['id' => $producto->id, 'cantidad' => 3, 'precio' => 10.00],
        ], total: 30.00)->assertOk()->assertJson(['success' => true]);

        $this->assertSame(17, $this->stockDe($producto));
    }

    #[Test]
    public function no_deja_vender_mas_de_lo_que_hay_en_stock(): void
    {
        $producto = $this->montarEscenario(stockInicial: 2);

        $this->vender([
            ['id' => $producto->id, 'cantidad' => 5, 'precio' => 10.00],
        ], total: 50.00)
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // Ni descuenta ni deja la venta a medias.
        $this->assertSame(2, $this->stockDe($producto));
        $this->assertSame(0, Venta::where('caja_id', $this->caja->id)->count());
    }

    #[Test]
    public function el_stock_nunca_queda_negativo_vendiendo_la_ultima_unidad(): void
    {
        $producto = $this->montarEscenario(stockInicial: 1);

        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)
            ->assertOk();

        $this->assertSame(0, $this->stockDe($producto));

        // La siguiente ya no tiene de donde sacar.
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)
            ->assertStatus(422);

        $this->assertSame(0, $this->stockDe($producto));
    }

    #[Test]
    public function el_igv_desagregado_suma_exactamente_el_total_cobrado(): void
    {
        $producto = $this->montarEscenario();

        $this->vender([['id' => $producto->id, 'cantidad' => 3, 'precio' => 2.50]], total: 7.50)
            ->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        $this->assertSame('7.50', (string) $venta->total);
        $this->assertEqualsWithDelta(
            (float) $venta->total,
            (float) $venta->subtotal + (float) $venta->igv,
            0.001,
            'subtotal + igv debe dar el total exacto'
        );
        // 7.50 con IGV incluido -> 6.36 + 1.14
        $this->assertSame('6.36', (string) $venta->subtotal);
        $this->assertSame('1.14', (string) $venta->igv);
    }

    #[Test]
    public function el_correlativo_avanza_de_a_uno_y_queda_guardado_en_la_serie(): void
    {
        $producto = $this->montarEscenario(stockInicial: 50);

        foreach (range(1, 3) as $esperado) {
            $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)
                ->assertOk();

            $this->assertSame($esperado, (int) $this->serieBoleta->fresh()->correlativo);
        }

        $numeros = Venta::where('caja_id', $this->caja->id)->orderBy('numero')->pluck('numero')->all();
        $this->assertSame([1, 2, 3], array_map('intval', $numeros));
    }

    #[Test]
    public function la_base_rechaza_un_comprobante_repetido(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        // Ultima red de contencion: aunque falle un bloqueo, el indice unico
        // impide dos comprobantes con el mismo tipo, serie y numero.
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Venta::create($venta->only([
            'tipo_comprobante', 'serie', 'numero', 'fecha_emision', 'cliente_id',
            'tipo_venta', 'forma_pago', 'subtotal', 'igv', 'total', 'pagado',
            'cambio', 'caja_id', 'usuario_id', 'estado',
        ]));
    }

    #[Test]
    public function la_venta_queda_ligada_a_la_caja_real_y_no_a_la_apertura(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        // ventas.caja_id tiene FK contra cajas: guardar el id de la apertura
        // rompia la integridad referencial.
        $this->assertSame($this->caja->id, (int) $venta->caja_id);
    }

    #[Test]
    public function sin_caja_abierta_no_se_puede_vender(): void
    {
        $producto = $this->montarEscenario();
        \App\Models\AperturaCaja::where('responsable_id', $this->vendedor->id)->update(['estado' => 'CERRADA']);

        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function el_comprobante_nace_pendiente_ante_sunat(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = Venta::where('caja_id', $this->caja->id)->firstOrFail();

        $this->assertSame('PENDIENTE', $venta->estado_sunat);
        $this->assertNull($venta->ruta_xml);
        $this->assertNull($venta->hash_xml);
    }
}
