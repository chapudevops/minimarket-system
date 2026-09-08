<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Sunat\Catalogo;
use App\Sunat\ConstructorComprobante;
use App\Sunat\Monto;
use App\Sunat\Tributos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El modelo tributario en el camino que mueve plata.
 *
 * Lo que se protege: que la afectacion del IGV siga siendo lo unico que llega
 * al comprobante, que el ISC y el IVAP no lo alteren, y que un producto sin
 * clasificar frene la venta en vez de salir facturado como GRAVADO por defecto.
 */
class TributosEnVentaTest extends TestCase
{
    use CreaEscenarioDeVenta, DatabaseTransactions;

    #[Test]
    public function un_producto_pendiente_no_se_puede_vender(): void
    {
        $producto = $this->montarEscenario(20);

        // Simula lo peor: una importacion defectuosa metio un PENDIENTE en la
        // tabla. La venta tiene que frenarse igual.
        Producto::where('id', $producto->id)->update(['operacion' => Tributos::PENDIENTE]);

        $respuesta = $this->vender(
            [['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]],
            10.00
        );

        $respuesta->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('afectación de IGV', $respuesta->json('message'));

        // Y el stock no se toco.
        $this->assertSame(20, $this->stockDe($producto));
    }

    #[Test]
    public function un_pendiente_bloquea_la_venta_entera_no_solo_su_linea(): void
    {
        $bueno = $this->montarEscenario(20);
        $malo = $this->crearProducto(20);

        Producto::where('id', $malo->id)->update(['operacion' => Tributos::PENDIENTE]);

        $respuesta = $this->vender([
            ['id' => $bueno->id, 'cantidad' => 1, 'precio' => 10.00],
            ['id' => $malo->id, 'cantidad' => 1, 'precio' => 10.00],
        ], 20.00);

        $respuesta->assertStatus(422);

        // Un comprobante a medias seria peor que ninguno.
        $this->assertSame(20, $this->stockDe($bueno));
        $this->assertSame(20, $this->stockDe($malo));
    }

    #[Test]
    public function las_tres_afectaciones_validas_si_se_venden(): void
    {
        $this->montarEscenario();

        foreach (Tributos::AFECTACIONES as $afectacion) {
            $producto = $this->crearProducto(10, $afectacion);

            $respuesta = $this->vender(
                [['id' => $producto->id, 'cantidad' => 1, 'precio' => 10.00]],
                10.00
            );

            $respuesta->assertOk()->assertJson(['success' => true], "fallo con {$afectacion}");
        }
    }

    #[Test]
    public function el_isc_no_altera_la_afectacion_de_igv_del_comprobante(): void
    {
        $this->montarEscenario();

        // Una cerveza: GRAVADA de IGV y ademas en el ambito del ISC. En el
        // comprobante tiene que salir exactamente igual que cualquier otro
        // producto gravado — el minimarket no declara ISC.
        $cerveza = $this->crearProducto(10, 'GRAVADO');
        $cerveza->update(['afecto_isc' => true, 'descripcion' => 'Cerveza de prueba']);

        $this->assertSame(
            Catalogo::AFECTACIONES_IGV['GRAVADO'],
            $cerveza->fresh()->afectacion_igv_sunat
        );
        $this->assertTrue($cerveza->fresh()->gravaIgv());
    }

    #[Test]
    public function el_ivap_no_altera_la_afectacion_de_igv_del_comprobante(): void
    {
        $this->montarEscenario();

        $arroz = $this->crearProducto(10, 'EXONERADO');
        $arroz->update(['afecto_ivap' => true, 'descripcion' => 'Arroz de prueba']);

        // La bandera de IVAP es informativa: lo que decide el XML sigue siendo
        // la columna operacion.
        $this->assertSame(
            Catalogo::AFECTACIONES_IGV['EXONERADO'],
            $arroz->fresh()->afectacion_igv_sunat
        );
        $this->assertFalse($arroz->fresh()->gravaIgv());
    }

    #[Test]
    public function un_producto_gravado_puede_estar_afecto_al_isc(): void
    {
        $this->montarEscenario();

        $producto = $this->crearProducto(10, 'GRAVADO');
        $producto->update(['afecto_isc' => true]);

        $producto = $producto->fresh();

        // El caso que el modelo viejo no sabia representar.
        $this->assertSame('GRAVADO', $producto->operacion);
        $this->assertTrue($producto->afecto_isc);
        $this->assertFalse($producto->afecto_ivap);
        $this->assertSame([], $producto->advertenciasTributarias());
    }

    #[Test]
    public function el_scope_vendibles_deja_fuera_lo_no_clasificado(): void
    {
        $this->montarEscenario();

        $bueno = $this->crearProducto(10, 'GRAVADO');
        $malo = $this->crearProducto(10);
        Producto::where('id', $malo->id)->update(['operacion' => Tributos::PENDIENTE]);

        $vendibles = Producto::vendibles()->pluck('id');

        $this->assertContains($bueno->id, $vendibles->all());
        $this->assertNotContains($malo->id, $vendibles->all());
    }

    #[Test]
    public function la_facturacion_electronica_sigue_armando_el_comprobante(): void
    {
        $producto = $this->montarEscenario(20);

        $this->vender([['id' => $producto->id, 'cantidad' => 2, 'precio' => 10.00]], 20.00)
            ->assertOk();

        $venta = \App\Models\Venta::latest('id')->first();

        // El constructor tiene que seguir produciendo un Invoice completo con
        // las columnas nuevas en la tabla.
        $invoice = (new ConstructorComprobante())->desdeVenta($venta);

        $this->assertNotEmpty($invoice->getDetails());
        $this->assertSame(
            Catalogo::AFECTACIONES_IGV['GRAVADO'],
            $invoice->getDetails()[0]->getTipAfeIgv()
        );

        // Y el IGV sigue cuadrando al centimo con lo que se cobro.
        $this->assertEqualsWithDelta(
            20.00,
            Monto::redondear($invoice->getValorVenta() + $invoice->getMtoIGV()),
            0.01
        );
    }

    #[Test]
    public function un_exonerado_no_paga_igv_en_el_comprobante(): void
    {
        $this->montarEscenario();

        $exonerado = $this->crearProducto(20, 'EXONERADO');

        $this->vender([['id' => $exonerado->id, 'cantidad' => 1, 'precio' => 10.00]], 10.00)
            ->assertOk();

        $venta = \App\Models\Venta::latest('id')->first();
        $invoice = (new ConstructorComprobante())->desdeVenta($venta);

        $this->assertSame(0.0, $invoice->getDetails()[0]->getIgv());
        $this->assertSame(10.00, $invoice->getMtoOperExoneradas());
        $this->assertSame(0.0, $invoice->getMtoIGV());
    }
}
