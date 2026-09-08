<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\GeneradorCodigoInterno;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Un codigo interno termina impreso en una etiqueta de gondola. Si una segunda
 * corrida del normalizador renumera el catalogo, esas etiquetas apuntan a otro
 * producto. Estos tests existen para que eso no pase.
 */
class GeneradorCodigoInternoTest extends TestCase
{
    #[Test]
    public function usa_el_formato_prefijo_y_seis_digitos(): void
    {
        $generador = new GeneradorCodigoInterno();

        $this->assertSame('BEB-GAS-000001', $generador->para('BEB-GAS', 'coca'));
        $this->assertSame('BEB-AGU-000001', $generador->para('BEB-AGU', 'cielo'));
        $this->assertSame('ABA-ARR-000001', $generador->para('ABA-ARR', 'costeno'));
    }

    #[Test]
    public function cada_prefijo_lleva_su_propio_correlativo(): void
    {
        $generador = new GeneradorCodigoInterno();

        $this->assertSame('BEB-GAS-000001', $generador->para('BEB-GAS', 'a'));
        $this->assertSame('BEB-GAS-000002', $generador->para('BEB-GAS', 'b'));
        $this->assertSame('LIM-DET-000001', $generador->para('LIM-DET', 'c'));
        $this->assertSame('BEB-GAS-000003', $generador->para('BEB-GAS', 'd'));
    }

    #[Test]
    public function la_misma_clave_devuelve_siempre_el_mismo_codigo(): void
    {
        $generador = new GeneradorCodigoInterno();

        $primero = $generador->para('GAL-DUL', 'sublime|chocolate|30 g');
        $segundo = $generador->para('GAL-DUL', 'sublime|chocolate|30 g');

        $this->assertSame($primero, $segundo);
        // No consumio dos correlativos.
        $this->assertSame('GAL-DUL-000002', $generador->para('GAL-DUL', 'otra clave'));
    }

    #[Test]
    public function respeta_los_codigos_ya_emitidos(): void
    {
        // Lo que ya existe en catalogo_maestro.csv o en la tabla productos.
        $generador = new GeneradorCodigoInterno(['coca|original|500ml' => 'BEB-GAS-000007']);

        $this->assertSame('BEB-GAS-000007', $generador->para('BEB-GAS', 'coca|original|500ml'));
        // El siguiente arranca despues del correlativo mas alto ya usado, no
        // en 1: si no, el codigo 000001 quedaria asignado a dos productos.
        $this->assertSame('BEB-GAS-000008', $generador->para('BEB-GAS', 'clave nueva'));
    }

    #[Test]
    public function un_codigo_reservado_sin_clave_igual_bloquea_el_numero(): void
    {
        $generador = new GeneradorCodigoInterno();

        // Caso real: se leyo el codigo de la tabla productos y no se sabe a
        // que clave natural corresponde.
        $generador->reservar('', 'ABA-ACE-000004');

        $this->assertSame('ABA-ACE-000005', $generador->para('ABA-ACE', 'primor 1 L'));
    }

    #[Test]
    public function un_prefijo_vacio_es_un_error_y_no_un_codigo_raro(): void
    {
        $this->expectException(\RuntimeException::class);

        (new GeneradorCodigoInterno())->para('', 'lo que sea');
    }
}
