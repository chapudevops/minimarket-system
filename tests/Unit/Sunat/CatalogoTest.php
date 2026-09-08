<?php

namespace Tests\Unit\Sunat;

use App\Sunat\Catalogo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los codigos son los del Anexo V de SUNAT. Si alguien cambia un mapeo por
 * error, el comprobante entero se rechaza, asi que quedan fijados acá.
 */
class CatalogoTest extends TestCase
{
    #[Test]
    public function catalogo_01_tipo_de_comprobante(): void
    {
        $this->assertSame('01', Catalogo::comprobante('FACTURA'));
        $this->assertSame('03', Catalogo::comprobante('BOLETA'));
        $this->assertSame('07', Catalogo::comprobante('NOTA_CREDITO'));
        $this->assertSame('08', Catalogo::comprobante('NOTA_DEBITO'));
        $this->assertNull(Catalogo::comprobante('INVENTADO'));
    }

    #[Test]
    public function catalogo_06_documento_de_identidad(): void
    {
        $this->assertSame('1', Catalogo::documentoIdentidad('DNI'));
        $this->assertSame('4', Catalogo::documentoIdentidad('CE'));
        $this->assertSame('6', Catalogo::documentoIdentidad('RUC'));
        // Sin documento es '0', no una excepcion: la boleta al publico existe.
        $this->assertSame('0', Catalogo::documentoIdentidad(null));
        $this->assertSame('0', Catalogo::documentoIdentidad('LO_QUE_SEA'));
    }

    #[Test]
    public function catalogo_03_unidad_de_medida(): void
    {
        $this->assertSame('NIU', Catalogo::unidad('UNIDAD'));
        $this->assertSame('BX', Catalogo::unidad('CAJA'));
        $this->assertSame('KGM', Catalogo::unidad('KG'));
        $this->assertSame('LTR', Catalogo::unidad('LITRO'));
        $this->assertSame('ZZ', Catalogo::unidad('SERVICIO'));
        // NIU es el comodin: una unidad sin mapear no debe romper la emision.
        $this->assertSame('NIU', Catalogo::unidad('BOLSA_RARA'));
    }

    #[Test]
    public function catalogo_07_afectacion_del_igv(): void
    {
        $this->assertSame('10', Catalogo::afectacionIgv('GRAVADO'));
        $this->assertSame('20', Catalogo::afectacionIgv('EXONERADO'));
        $this->assertSame('30', Catalogo::afectacionIgv('INAFECTO'));
        $this->assertSame('10', Catalogo::afectacionIgv(null));
    }

    #[Test]
    public function catalogo_05_tributo_por_afectacion(): void
    {
        $this->assertSame('1000', Catalogo::tributo('GRAVADO')['codigo']);
        $this->assertSame('9997', Catalogo::tributo('EXONERADO')['codigo']);
        $this->assertSame('9998', Catalogo::tributo('INAFECTO')['codigo']);
    }

    #[Test]
    public function solo_lo_gravado_paga_igv(): void
    {
        $this->assertTrue(Catalogo::gravaIgv('GRAVADO'));
        $this->assertTrue(Catalogo::gravaIgv(null));
        $this->assertFalse(Catalogo::gravaIgv('EXONERADO'));
        $this->assertFalse(Catalogo::gravaIgv('INAFECTO'));
    }
}
