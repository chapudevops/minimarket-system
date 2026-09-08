<?php

namespace Tests\Unit\Sunat;

use App\Sunat\ConstructorComprobante;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * Fija la traduccion de una Venta al modelo de Greenter. Un error acá se
 * traduce en un comprobante rechazado por SUNAT.
 */
class ConstructorComprobanteTest extends TestCase
{
    use DatabaseTransactions;
    use CreaEscenarioDeVenta;

    #[Test]
    public function los_importes_del_comprobante_cuadran_con_la_venta(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 3, 'precio' => 2.50]], total: 7.50)->assertOk();

        $venta = \App\Models\Venta::where('caja_id', $this->caja->id)->firstOrFail();
        $comprobante = (new ConstructorComprobante())->desdeVenta($venta);

        $this->assertSame(6.36, $comprobante->getMtoOperGravadas());
        $this->assertSame(1.14, $comprobante->getMtoIGV());
        $this->assertSame(7.50, $comprobante->getMtoImpVenta());

        // Lo que SUNAT valida: las partes suman el total.
        $this->assertEqualsWithDelta(
            $comprobante->getMtoImpVenta(),
            $comprobante->getValorVenta() + $comprobante->getTotalImpuestos(),
            0.001
        );
    }

    #[Test]
    public function lleva_la_forma_de_pago_que_sunat_exige_en_facturas(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = \App\Models\Venta::where('caja_id', $this->caja->id)->firstOrFail();
        $comprobante = (new ConstructorComprobante())->desdeVenta($venta);

        // Sin esto SUNAT rechaza las facturas con el codigo 3244.
        $this->assertNotNull($comprobante->getFormaPago());
        $this->assertSame('Contado', $comprobante->getFormaPago()->getTipo());
    }

    #[Test]
    public function usa_los_codigos_de_catalogo_en_las_lineas(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], total: 20.00)->assertOk();

        $venta = \App\Models\Venta::where('caja_id', $this->caja->id)->firstOrFail();
        $linea = (new ConstructorComprobante())->desdeVenta($venta)->getDetails()[0];

        $this->assertSame('NIU', $linea->getUnidad());          // catalogo 03
        $this->assertSame('10', $linea->getTipAfeIgv());        // catalogo 07: gravado
        $this->assertSame(18.0, $linea->getPorcentajeIgv());
        $this->assertSame($producto->codigo_interno, $linea->getCodProducto());
    }

    #[Test]
    public function el_comprobante_lleva_el_monto_en_letras(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 7.50]], total: 7.50)->assertOk();

        $venta = \App\Models\Venta::where('caja_id', $this->caja->id)->firstOrFail();
        $leyendas = (new ConstructorComprobante())->desdeVenta($venta)->getLegends();

        // Leyenda 1000: obligatoria en todo comprobante.
        $this->assertSame('1000', $leyendas[0]->getCode());
        $this->assertSame('SIETE CON 50/100 SOLES', $leyendas[0]->getValue());
    }

    #[Test]
    public function el_cliente_va_con_su_codigo_de_documento(): void
    {
        $producto = $this->montarEscenario();
        $this->vender([['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]], total: 10.00)->assertOk();

        $venta = \App\Models\Venta::where('caja_id', $this->caja->id)->firstOrFail();
        $cliente = (new ConstructorComprobante())->desdeVenta($venta)->getClient();

        $this->assertSame('1', $cliente->getTipoDoc());   // catalogo 06: DNI
        $this->assertSame($this->cliente->numero_documento, $cliente->getNumDoc());
    }
}
