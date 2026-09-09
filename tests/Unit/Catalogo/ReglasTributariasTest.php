<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\ReglasTributarias;
use App\Sunat\Tributos;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La matriz tributaria del catalogo.
 *
 * Lo que se protege aca es que el sistema no clasifique por el nombre del
 * producto ni por analogia: si no hay una regla verificable, la respuesta es
 * PENDIENTE y alguien tiene que mirarlo.
 */
class ReglasTributariasTest extends TestCase
{
    #[Test]
    public function la_matriz_del_proyecto_carga_y_responde(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        $this->assertNotEmpty($reglas->todas());
        $this->assertSame('GRAVADO', $reglas->igv('LIMPIEZA', 'DETERGENTES'));
        $this->assertSame('EXONERADO', $reglas->igv('FRESCOS', 'FRUTAS'));
    }

    #[Test]
    public function una_categoria_no_declarada_no_se_da_por_gravada(): void
    {
        $reglas = new ReglasTributarias();

        // El riesgo real: que "no se cual es" degrade silenciosamente a
        // GRAVADO, que es el valor por defecto de la columna en la base.
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('LO QUE SEA', 'NO EXISTE'));
        $this->assertTrue($reglas->requiereRevision('LO QUE SEA', 'NO EXISTE'));
        $this->assertFalse($reglas->afectoIsc('LO QUE SEA', 'NO EXISTE'));
        $this->assertFalse($reglas->afectoIvap('LO QUE SEA', 'NO EXISTE'));
    }

    #[Test]
    public function isc_e_ivap_son_ejes_distintos_del_igv(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // Cerveza: GRAVADA de IGV y ademas en el ambito del ISC.
        $this->assertSame('GRAVADO', $reglas->igv('LICORES', 'CERVEZA'));
        $this->assertTrue($reglas->afectoIsc('LICORES', 'CERVEZA'));
        $this->assertFalse($reglas->afectoIvap('LICORES', 'CERVEZA'));

        // Detergente: GRAVADO y nada mas.
        $this->assertSame('GRAVADO', $reglas->igv('LIMPIEZA', 'DETERGENTES'));
        $this->assertFalse($reglas->afectoIsc('LIMPIEZA', 'DETERGENTES'));
    }

    #[Test]
    public function revisar_no_es_lo_mismo_que_si(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // El ISC de una gaseosa depende del azucar del producto concreto. La
        // matriz dice REVISAR, y REVISAR no puede convertirse en un "si".
        $this->assertFalse($reglas->afectoIsc('BEBIDAS', 'GASEOSAS'));
        $this->assertTrue($reglas->requiereRevision('BEBIDAS', 'GASEOSAS'));
    }

    #[Test]
    public function una_duda_de_isc_no_bloquea_el_igv(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // requiereRevision() avisa; requiereRevisionIgv() bloquea. Son cosas
        // distintas: el IGV de una gaseosa es seguro aunque su ISC no lo sea.
        $this->assertSame('GRAVADO', $reglas->igv('BEBIDAS', 'GASEOSAS'));
        $this->assertFalse($reglas->requiereRevisionIgv('BEBIDAS', 'GASEOSAS'));
        $this->assertTrue($reglas->requiereRevision('BEBIDAS', 'GASEOSAS'));
    }

    #[Test]
    public function no_todo_arroz_es_ivap(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // Sin precisar el tipo no se marca IVAP: la Ley 28211 alcanza a
        // determinadas operaciones con arroz pilado, no a la palabra "arroz".
        $this->assertFalse($reglas->afectoIvap('ABARROTES', 'ARROZ'));
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('ABARROTES', 'ARROZ'));

        // El arroz con cascara (paddy) esta en el Apendice I; el pilado no.
        $this->assertSame('EXONERADO', $reglas->igv('ABARROTES', 'ARROZ', 'ARROZ_CON_CASCARA'));
        $this->assertFalse($reglas->afectoIvap('ABARROTES', 'ARROZ', 'ARROZ_CON_CASCARA'));
    }

    #[Test]
    public function el_arroz_pilado_es_inafecto_y_no_exonerado(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // Ley 28211 art. 7 (mod. Ley 28309): tanto la operacion gravada con
        // IVAP como las ventas POSTERIORES del bien en el pais quedan
        // INAFECTAS al IGV. La venta del minimarket es una venta posterior.
        //
        // "Inafecto" y "exonerado" no son sinonimos: son los codigos 30 y 20
        // de la Catalogo 07 y el comprobante sale distinto.
        $this->assertSame('INAFECTO', $reglas->igv('ABARROTES', 'ARROZ', 'ARROZ_PILADO'));
        $this->assertTrue($reglas->afectoIvap('ABARROTES', 'ARROZ', 'ARROZ_PILADO'));
        $this->assertFalse($reglas->requiereRevisionIgv('ABARROTES', 'ARROZ', 'ARROZ_PILADO'));
    }

    #[Test]
    public function la_leche_no_se_clasifica_por_subcategoria(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('LACTEOS', 'LECHE'));

        // El Apendice I dice literalmente "Solo: leche cruda entera" en la
        // partida 0401.20.00.00. Ese "Solo" excluye a todas las demas.
        $this->assertSame('EXONERADO', $reglas->igv('LACTEOS', 'LECHE', 'LECHE_CRUDA_ENTERA'));
        $this->assertSame('GRAVADO', $reglas->igv('LACTEOS', 'LECHE', 'LECHE_EVAPORADA'));
        $this->assertSame('GRAVADO', $reglas->igv('LACTEOS', 'LECHE', 'LECHE_UHT'));
    }

    #[Test]
    public function los_huevos_no_estan_exonerados(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // Los huevos NO figuran en el Apendice I: entre la partida 03.07
        // (pescados) y la 04.01 (leche cruda) no existe la 04.07. Estuvieron
        // exonerados por la Ley 31452 del 1.5.2022 al 31.7.2022, que vencio
        // sin prorroga.
        //
        // Este test existe porque la matriz los daba por EXONERADOS y estaba
        // mal: habria emitido boletas sin IGV.
        $this->assertSame('GRAVADO', $reglas->igv('FRESCOS', 'HUEVOS'));
        $this->assertSame('GRAVADO', $reglas->igv('FRESCOS', 'POLLO'));
        $this->assertSame('GRAVADO', $reglas->igv('PANADERIA', 'PAN'));
        $this->assertSame('GRAVADO', $reglas->igv('ABARROTES', 'AZUCAR'));
        $this->assertSame('GRAVADO', $reglas->igv('ABARROTES', 'FIDEOS'));
    }

    #[Test]
    public function lo_fresco_del_apendice_i_si_esta_exonerado(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // Frutas (0803/0810.90.90), hortalizas (0701/0709.90.90), pescados
        // (0301/0307.99.90.90) y legumbres secas (0713) figuran con rangos
        // que incluyen clausulas de cierre.
        $this->assertSame('EXONERADO', $reglas->igv('FRESCOS', 'FRUTAS'));
        $this->assertSame('EXONERADO', $reglas->igv('FRESCOS', 'VERDURAS'));
        $this->assertSame('EXONERADO', $reglas->igv('FRESCOS', 'PESCADOS'));
        $this->assertSame('EXONERADO', $reglas->igv('ABARROTES', 'MENESTRAS'));

        // Pero lo industrializado de esas mismas familias, no.
        $this->assertSame('GRAVADO', $reglas->igv('FRESCOS', 'CARNES'));
        $this->assertSame('GRAVADO', $reglas->igv('FRESCOS', 'EMBUTIDOS'));
        $this->assertSame('GRAVADO', $reglas->igv('ABARROTES', 'CONSERVAS'));
        $this->assertSame('GRAVADO', $reglas->igv('ABARROTES', 'HARINAS'));
    }

    #[Test]
    public function el_te_se_distingue_de_la_infusion_de_hierbas(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // El te de la partida 09.02 figura en el Apendice I; la manzanilla y
        // el anis no son te y van por otra partida. Clasificar la subcategoria
        // entera en cualquiera de los dos sentidos seria un error.
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('ABARROTES', 'INFUSIONES'));
        $this->assertSame('EXONERADO', $reglas->igv('ABARROTES', 'INFUSIONES', 'TE'));
        $this->assertSame('GRAVADO', $reglas->igv('ABARROTES', 'INFUSIONES', 'INFUSION_DE_HIERBAS'));
    }

    #[Test]
    public function solo_los_frutos_secos_nominados_estan_exonerados(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // El Apendice I nombra cocos, nueces del Brasil y de maranon. Nada mas.
        $this->assertSame('EXONERADO', $reglas->igv('SNACKS', 'FRUTOS SECOS', 'COCO'));
        $this->assertSame('GRAVADO', $reglas->igv('SNACKS', 'FRUTOS SECOS', 'MANI'));
        // Una almendra no tiene regla propia y no hereda de la familia.
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('SNACKS', 'FRUTOS SECOS', 'ALMENDRA'));
    }

    #[Test]
    public function toda_regla_dice_cuando_se_verifico_contra_la_norma(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        foreach ($reglas->todas() as $regla) {
            $ref = "{$regla['categoria']}/{$regla['subcategoria']}/{$regla['producto_tipo']}";

            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}$/',
                $regla['verificado_el'],
                "{$ref} no dice cuando se verifico"
            );
        }

        // Las reglas caducan: la Ley 31452 exonero pollo, huevos, azucar,
        // fideos y pan del 1.5.2022 al 31.7.2022 y nadie la prorrogo. Un
        // catalogo armado entonces y nunca revisado seguiria sin cobrar IGV.
        $this->assertSame(
            [],
            $reglas->sinVerificarDesdeHace(24),
            'hay reglas sin verificar contra la norma desde hace mas de dos años'
        );
    }

    #[Test]
    public function un_tipo_sin_regla_propia_no_hereda_de_una_familia_que_distingue(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        // LACTEOS/LECHE declara reglas por tipo. Un tipo nuevo no puede tomar
        // prestada la regla general de la subcategoria: seria clasificar por
        // parecido, que es justo lo que hay que evitar.
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('LACTEOS', 'LECHE', 'LECHE_DE_ALMENDRAS'));
        $this->assertTrue($reglas->requiereRevisionIgv('LACTEOS', 'LECHE', 'LECHE_DE_ALMENDRAS'));
    }

    #[Test]
    public function ninguna_regla_de_la_matriz_esta_incompleta(): void
    {
        foreach (ReglasTributarias::desdeArchivo()->todas() as $regla) {
            $ref = "{$regla['categoria']}/{$regla['subcategoria']}/{$regla['producto_tipo']}";

            $this->assertContains($regla['igv'], ['GRAVADO', 'EXONERADO', 'INAFECTO', Tributos::PENDIENTE], "igv invalido en {$ref}");
            $this->assertContains($regla['isc'], ['SI', 'NO', 'REVISAR'], "isc invalido en {$ref}");
            $this->assertContains($regla['ivap'], ['SI', 'NO', 'REVISAR'], "ivap invalido en {$ref}");

            // Una regla sin norma detras es una opinion. Si no se puede citar
            // de donde sale, tiene que estar marcada para revision.
            if (! $regla['requiere_revision']) {
                $this->assertNotSame('', $regla['fuente_normativa'], "{$ref} afirma sin citar norma");
            }

            $this->assertNotSame('', $regla['observacion'], "{$ref} no explica nada");
        }
    }

    #[Test]
    public function una_afectacion_afirmada_nunca_queda_marcada_para_revision(): void
    {
        foreach (ReglasTributarias::desdeArchivo()->todas() as $regla) {
            if ($regla['requiere_revision']) {
                // Si hay que revisar el IGV, la columna no puede estar
                // afirmando una afectacion: seria una decision disfrazada.
                $this->assertSame(
                    Tributos::PENDIENTE,
                    $regla['igv'],
                    "{$regla['categoria']}/{$regla['subcategoria']}/{$regla['producto_tipo']}: "
                        ."esta marcada para revision pero afirma {$regla['igv']}"
                );
            }
        }
    }
}
