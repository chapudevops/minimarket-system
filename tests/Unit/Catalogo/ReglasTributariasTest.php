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
        $this->assertSame('EXONERADO', $reglas->igv('FRESCOS', 'HUEVOS'));
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

        // Sin precisar el tipo, no se marca IVAP: la Ley 28211 alcanza a
        // determinadas operaciones con arroz pilado, no a la palabra "arroz".
        $this->assertFalse($reglas->afectoIvap('ABARROTES', 'ARROZ'));
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('ABARROTES', 'ARROZ'));

        // Precisado como arroz pilado si entra al ambito, pero la afectacion
        // de IGV sigue pendiente: depende de que operacion haga el negocio.
        $this->assertTrue($reglas->afectoIvap('ABARROTES', 'ARROZ', 'ARROZ_PILADO'));
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('ABARROTES', 'ARROZ', 'ARROZ_PILADO'));
    }

    #[Test]
    public function la_leche_no_se_clasifica_por_subcategoria(): void
    {
        $reglas = ReglasTributarias::desdeArchivo();

        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('LACTEOS', 'LECHE'));
        $this->assertSame('EXONERADO', $reglas->igv('LACTEOS', 'LECHE', 'LECHE_CRUDA_ENTERA'));
        // La evaporada NO hereda la exoneracion de la cruda por analogia.
        $this->assertSame(Tributos::PENDIENTE, $reglas->igv('LACTEOS', 'LECHE', 'LECHE_EVAPORADA'));
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
