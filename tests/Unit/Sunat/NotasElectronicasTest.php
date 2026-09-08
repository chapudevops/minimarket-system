<?php

namespace Tests\Unit\Sunat;

use App\Models\NotaCredito;
use App\Models\NotaCreditoDetalle;
use App\Models\NotaDebito;
use App\Models\NotaDebitoDetalle;
use App\Models\Venta;
use App\Sunat\ConstructorComprobante;
use Greenter\Model\Sale\Note;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * Una nota que no llega a SUNAT no existe para ellos: la venta original sigue
 * gravada. Estos tests fijan la traducción de notas al modelo de Greenter.
 */
class NotasElectronicasTest extends TestCase
{
    use DatabaseTransactions;
    use CreaEscenarioDeVenta;

    private function ventaEmitida(): Venta
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)->assertOk();

        return Venta::where('caja_id', $this->caja->id)->firstOrFail();
    }

    private function notaCredito(Venta $venta, string $tipo = 'DEVOLUCION'): NotaCredito
    {
        $nota = NotaCredito::create([
            'tipo_comprobante' => 'NOTA_CREDITO',
            'serie' => 'FC01', 'numero' => 1,
            'fecha_emision' => now(),
            'cliente_id' => $venta->cliente_id,
            'venta_id' => $venta->id,
            'motivo' => 'Devolución de mercadería',
            'tipo_nota' => $tipo,
            // El precio de la nota NO incluye IGV: el controller lo suma encima.
            'subtotal' => 10.00, 'igv' => 1.80, 'total' => 11.80,
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->vendedor->id,
            'estado' => 'EMITIDA',
        ]);

        NotaCreditoDetalle::create([
            'nota_credito_id' => $nota->id,
            'producto_id' => $venta->detalles->first()->producto_id,
            'cantidad' => 1, 'precio_unitario' => 10.00, 'total' => 10.00,
            'almacen_id' => $this->almacen->id,
        ]);

        return $nota->fresh();
    }

    #[Test]
    public function una_nota_de_credito_se_traduce_a_una_nota_de_greenter(): void
    {
        $venta = $this->ventaEmitida();
        $nota = $this->notaCredito($venta);

        $doc = (new ConstructorComprobante())->desde($nota);

        $this->assertInstanceOf(Note::class, $doc);
        $this->assertSame('07', $doc->getTipoDoc());   // catalogo 01
        $this->assertSame('FC01', $doc->getSerie());
    }

    #[Test]
    public function declara_el_comprobante_que_modifica(): void
    {
        $venta = $this->ventaEmitida();
        $doc = (new ConstructorComprobante())->desde($this->notaCredito($venta));

        // Sin la referencia al documento afectado SUNAT rechaza la nota.
        $this->assertSame($venta->tipo_comprobante_sunat, $doc->getTipDocAfectado());
        $this->assertSame($venta->serie . '-' . $venta->numero, $doc->getNumDocfectado());
    }

    #[Test]
    public function usa_el_codigo_de_motivo_del_catalogo_09(): void
    {
        $venta = $this->ventaEmitida();

        $casos = ['ANULACION' => '01', 'DESCUENTO' => '04', 'DEVOLUCION' => '06', 'OTRO' => '10'];

        foreach ($casos as $tipo => $codigo) {
            $doc = (new ConstructorComprobante())->desde($this->notaCredito($venta, $tipo));
            $this->assertSame($codigo, $doc->getCodMotivo(), "tipo_nota {$tipo}");
        }
    }

    #[Test]
    public function en_las_notas_el_igv_se_agrega_no_se_desagrega(): void
    {
        $venta = $this->ventaEmitida();
        $doc = (new ConstructorComprobante())->desde($this->notaCredito($venta));

        // 10.00 sin IGV -> 1.80 de IGV -> 11.80. Desagregar habria dado 8.47,
        // que es lo que hacia rechazar la nota con el codigo 3280.
        $this->assertSame(10.00, $doc->getMtoOperGravadas());
        $this->assertSame(1.80, $doc->getMtoIGV());
        $this->assertSame(11.80, $doc->getMtoImpVenta());
    }

    #[Test]
    public function una_nota_de_debito_lleva_conceptos_con_unidad_de_servicio(): void
    {
        $venta = $this->ventaEmitida();

        $nota = NotaDebito::create([
            'tipo_comprobante' => 'NOTA_DEBITO',
            'serie' => 'FD01', 'numero' => 1,
            'fecha_emision' => now(),
            'cliente_id' => $venta->cliente_id,
            'venta_id' => $venta->id,
            'motivo' => 'Intereses por mora',
            'tipo_nota' => 'INTERESES',
            'subtotal' => 15.00, 'igv' => 2.70, 'total' => 17.70,
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->vendedor->id,
            'estado' => 'EMITIDA',
        ]);

        NotaDebitoDetalle::create([
            'nota_debito_id' => $nota->id,
            'concepto' => 'Interés moratorio 30 días',
            'cantidad' => 1, 'precio_unitario' => 15.00, 'total' => 15.00,
        ]);

        $doc = (new ConstructorComprobante())->desde($nota->fresh());

        $this->assertSame('08', $doc->getTipoDoc());
        $this->assertSame('01', $doc->getCodMotivo());   // catalogo 10: intereses
        // Un concepto no es un producto del catalogo: va como servicio.
        $this->assertSame('ZZ', $doc->getDetails()[0]->getUnidad());
        $this->assertSame('Interés moratorio 30 días', $doc->getDetails()[0]->getDescripcion());
    }

    #[Test]
    public function una_nota_sin_comprobante_afectado_no_se_construye(): void
    {
        $venta = $this->ventaEmitida();
        $nota = $this->notaCredito($venta);
        $nota->update(['venta_id' => null]);

        $this->expectException(\RuntimeException::class);
        (new ConstructorComprobante())->desde($nota->fresh());
    }
}
