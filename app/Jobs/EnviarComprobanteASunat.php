<?php

namespace App\Jobs;

use App\Models\NotaCredito;
use App\Models\NotaDebito;
use App\Models\Venta;
use App\Sunat\EnviadorSunat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Envía el comprobante a SUNAT fuera del request.
 *
 * El web service de SUNAT se cae seguido, y una venta en el mostrador no puede
 * quedar esperando por eso: la venta se cierra local y el envío viaja aparte.
 */
class EnviarComprobanteASunat implements ShouldQueue
{
    use Queueable;

    /** Reintentos antes de darla por fallida. */
    public int $tries = 4;

    /** Un envío que tarda más de esto es un servicio caído, no lento. */
    public int $timeout = 90;

    /**
     * Espera creciente entre reintentos: si SUNAT está caído, insistir cada
     * segundo no ayuda a nadie.
     */
    public array $backoff = [60, 300, 900];

    /** Clases que este job sabe enviar, indexadas por el nombre corto. */
    private const MODELOS = [
        'Venta'       => Venta::class,
        'NotaCredito' => NotaCredito::class,
        'NotaDebito'  => NotaDebito::class,
    ];

    /**
     * No es readonly promovida a proposito: los jobs que ya estaban en la cola
     * cuando se agrego esta propiedad se deserializan sin ella, y una typed
     * property sin inicializar revienta al leerla. Se lee siempre por tipo().
     */
    public string $tipo;

    public function __construct(public readonly int $ventaId, string $tipo = 'Venta')
    {
        $this->tipo = $tipo;
    }

    /** Tolera payloads viejos, anteriores a que el job soportara notas. */
    private function tipo(): string
    {
        return isset($this->tipo) ? $this->tipo : 'Venta';
    }

    public function handle(EnviadorSunat $enviador): void
    {
        $clase = self::MODELOS[$this->tipo()] ?? null;
        $documento = $clase ? $clase::find($this->ventaId) : null;

        if (! $documento) {
            return;
        }

        // Otro intento pudo haberlo resuelto mientras esperaba en la cola.
        if (in_array($documento->estado_sunat, ['ACEPTADO', 'OBSERVADO', 'RECHAZADO'], true)) {
            return;
        }

        $resultado = $enviador->enviar($documento);

        // Un rechazo de SUNAT no es un fallo del job: el comprobante ya quedó
        // marcado y reintentarlo daría exactamente lo mismo.
        if ($resultado['estado'] === 'PENDIENTE') {
            throw new \RuntimeException(
                "SUNAT no aceptó {$documento->documento}: {$resultado['mensaje']}"
            );
        }
    }

    /** Se llama cuando se agotaron los reintentos. */
    public function failed(\Throwable $e): void
    {
        Log::warning('Envío a SUNAT agotó los reintentos', [
            'tipo' => $this->tipo(),
            'id' => $this->ventaId,
            'error' => $e->getMessage(),
        ]);

        // Queda en PENDIENTE a proposito: el comando sunat:enviar la vuelve a
        // tomar cuando el servicio se recupere.
    }
}
