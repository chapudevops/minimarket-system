<?php

namespace Tests\Unit\Sunat;

use App\Sunat\Catalogo;
use App\Sunat\Tributos;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los tres ejes tributarios, separados.
 *
 * Antes `operacion` cargaba con todo. Estos tests fijan que la afectacion del
 * IGV siga siendo lo unico que llega al comprobante, y que PENDIENTE frene.
 */
class TributosTest extends TestCase
{
    #[Test]
    public function solo_las_tres_afectaciones_de_la_catalogo_07_son_validas(): void
    {
        $this->assertSame(['GRAVADO', 'EXONERADO', 'INAFECTO'], Tributos::AFECTACIONES);

        foreach (Tributos::AFECTACIONES as $afectacion) {
            $this->assertTrue(Tributos::esAfectacionValida($afectacion));
        }
    }

    #[Test]
    public function pendiente_no_es_una_afectacion(): void
    {
        // PENDIENTE es un marcador del pipeline de catalogo, no un valor de
        // negocio: significa "nadie determino todavia como tributa esto".
        $this->assertFalse(Tributos::esAfectacionValida(Tributos::PENDIENTE));
        $this->assertFalse(Tributos::esAfectacionValida(null));
        $this->assertFalse(Tributos::esAfectacionValida(''));
        $this->assertFalse(Tributos::esAfectacionValida('gravado'));
    }

    #[Test]
    public function un_producto_sin_afectacion_resuelta_no_es_vendible(): void
    {
        $this->assertTrue(Tributos::esVendible('GRAVADO'));
        $this->assertTrue(Tributos::esVendible('EXONERADO'));
        $this->assertTrue(Tributos::esVendible('INAFECTO'));

        $this->assertFalse(Tributos::esVendible(Tributos::PENDIENTE));
        $this->assertFalse(Tributos::esVendible(null));
    }

    #[Test]
    public function la_afectacion_sigue_traduciendo_a_los_codigos_de_sunat(): void
    {
        // El ISC y el IVAP no cambian esto: lo unico que viaja en el XML,
        // linea por linea, sigue siendo la afectacion del IGV.
        foreach (Tributos::AFECTACIONES as $afectacion) {
            $this->assertArrayHasKey($afectacion, Catalogo::AFECTACIONES_IGV);
            $this->assertArrayHasKey($afectacion, Catalogo::TRIBUTOS);
        }
    }

    #[Test]
    public function avisa_cuando_ivap_y_gravado_conviven(): void
    {
        // El IVAP de la Ley 28211 desplaza al IGV en las operaciones que
        // alcanza. No se bloquea —depende de que operacion haga el negocio—
        // pero tampoco se deja pasar en silencio.
        $avisos = Tributos::advertencias('GRAVADO', afectoIsc: false, afectoIvap: true);

        $this->assertNotEmpty($avisos);
        $this->assertStringContainsString('IVAP', $avisos[0]);
    }

    #[Test]
    public function un_gravado_con_isc_es_una_combinacion_normal(): void
    {
        // Es el caso de la cerveza y de la bebida energetica: no debe generar
        // ruido, es exactamente lo que el modelo nuevo viene a representar.
        $this->assertSame([], Tributos::advertencias('GRAVADO', afectoIsc: true, afectoIvap: false));
    }

    #[Test]
    public function avisa_cuando_la_afectacion_no_esta_resuelta(): void
    {
        $avisos = Tributos::advertencias(Tributos::PENDIENTE);

        $this->assertNotEmpty($avisos);
        $this->assertStringContainsString('IGV', $avisos[0]);
    }

    #[Test]
    public function la_etiqueta_de_pendiente_no_miente(): void
    {
        // Que la interfaz no muestre "Gravado" para algo sin clasificar.
        $this->assertStringNotContainsString('Gravado', Tributos::etiqueta(Tributos::PENDIENTE));
        $this->assertSame('Gravado - Operación Onerosa', Tributos::etiqueta('GRAVADO'));
    }
}
