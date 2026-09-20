<?php

namespace Tests\Feature;

use App\Estados\EstadoDocumento;
use App\Estados\EstadoSunat;
use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Conductor;
use App\Models\GuiaRemision;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * Resto de documentos del modulo comercial.
 *
 * La guia de remision era el caso mas claro de confusion: era el UNICO
 * documento sin estado interno, solo tenia estado_sunat. Una guia emitida
 * figuraba como "Pendiente" nada mas que por no estar declarada todavia.
 *
 * Notas de venta, cotizaciones y compras son documentos internos y no deben
 * tener estado SUNAT en absoluto.
 */
class EstadosOtrosDocumentosTest extends TestCase
{
    use DatabaseTransactions, CreaEscenarioDeVenta;

    #[Test]
    public function la_guia_de_remision_tiene_estado_propio_ademas_del_de_sunat(): void
    {
        $this->montarEscenario();

        $guia = GuiaRemision::create([
            'serie' => 'T'.random_int(100, 999),
            'numero' => random_int(1, 99999),
            'fecha_emision' => now(),
            'fecha_traslado' => now()->addDay()->toDateString(),
            'motivo_traslado' => '01',
            'cliente_id' => $this->cliente->id,
            'peso_bruto_total' => 10,
            'unidad_peso' => 'KGM',
            'modalidad_traslado' => '02',
            'ubigeo_partida' => '150114',
            'direccion_partida' => 'Av. Prueba 1',
            'ubigeo_llegada' => '150101',
            'direccion_llegada' => 'Av. Prueba 2',
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->vendedor->id,
        ]);

        // El default de la columna nueva: la guia existe operativamente.
        $this->assertSame(EstadoDocumento::REGISTRADA, $guia->fresh()->estado);
        $this->assertSame(EstadoSunat::NO_ENVIADO, $guia->fresh()->estado_sunat);
    }

    #[Test]
    public function el_rechazo_de_sunat_no_cambia_el_estado_operativo_de_la_guia(): void
    {
        $this->montarEscenario();

        $guia = GuiaRemision::create([
            'serie' => 'T'.random_int(100, 999), 'numero' => random_int(1, 99999),
            'fecha_emision' => now(), 'fecha_traslado' => now()->addDay()->toDateString(),
            'motivo_traslado' => '01', 'cliente_id' => $this->cliente->id,
            'peso_bruto_total' => 10, 'unidad_peso' => 'KGM', 'modalidad_traslado' => '02',
            'ubigeo_partida' => '150114', 'direccion_partida' => 'Av. Prueba 1',
            'ubigeo_llegada' => '150101', 'direccion_llegada' => 'Av. Prueba 2',
            'caja_id' => $this->caja->id, 'usuario_id' => $this->vendedor->id,
        ]);

        $guia->update(['estado_sunat' => EstadoSunat::RECHAZADO]);

        $this->assertSame(EstadoDocumento::REGISTRADA, $guia->fresh()->estado);
        $this->assertStringContainsString('Registrada', $guia->fresh()->estado_badge);
        $this->assertStringContainsString('Rechazado', $guia->fresh()->estado_sunat_badge);
    }

    #[Test]
    public function los_documentos_internos_no_tienen_estado_sunat(): void
    {
        // notas_venta, cotizaciones y compras son internos: si alguien les
        // agrega un estado_sunat, es que se colo integracion donde no toca.
        foreach (['notas_venta', 'cotizaciones', 'compras'] as $tabla) {
            $this->assertFalse(
                \Illuminate\Support\Facades\Schema::hasColumn($tabla, 'estado_sunat'),
                "{$tabla} no debe tener estado_sunat: es un documento interno."
            );
        }
    }

    #[Test]
    public function los_documentos_con_sunat_tienen_los_dos_campos(): void
    {
        foreach (['ventas', 'notas_credito', 'notas_debito', 'guias_remision'] as $tabla) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn($tabla, 'estado'),
                "{$tabla} necesita estado comercial propio."
            );
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn($tabla, 'estado_sunat'),
                "{$tabla} necesita estado_sunat separado."
            );
        }
    }

    #[Test]
    public function el_vocabulario_de_sunat_es_unico(): void
    {
        // Antes GuiaRemision pintaba 'ENVIADO' y EnviadorSunat emitia
        // 'OBSERVADO': dos vocabularios para lo mismo. Ahora todos los badges
        // salen de EstadoSunat.
        $this->assertStringContainsString('Aceptado', EstadoSunat::badge(EstadoSunat::ACEPTADO));
        $this->assertStringContainsString('No enviado', EstadoSunat::badge(EstadoSunat::NO_ENVIADO));
        $this->assertStringContainsString('Error de envío', EstadoSunat::badge(EstadoSunat::ERROR));

        $this->assertTrue(EstadoSunat::esAceptado(EstadoSunat::OBSERVADO));
        $this->assertFalse(EstadoSunat::esAceptado(EstadoSunat::RECHAZADO));
        $this->assertTrue(EstadoSunat::esReintentable(EstadoSunat::ERROR));
        $this->assertFalse(EstadoSunat::esReintentable(EstadoSunat::ACEPTADO));
    }

    #[Test]
    public function un_documento_sin_sunat_configurado_sigue_siendo_valido(): void
    {
        // La regla de fondo: el ERP funciona sin credenciales SUNAT.
        $this->assertSame(EstadoSunat::NO_APLICA, EstadoSunat::NO_APLICA);
        $this->assertFalse(EstadoSunat::necesitaAtencion(EstadoSunat::NO_ENVIADO));
        $this->assertFalse(EstadoSunat::necesitaAtencion(EstadoSunat::NO_APLICA));
        $this->assertTrue(EstadoSunat::necesitaAtencion(EstadoSunat::RECHAZADO));
        $this->assertTrue(EstadoSunat::necesitaAtencion(EstadoSunat::ERROR));
    }
}
