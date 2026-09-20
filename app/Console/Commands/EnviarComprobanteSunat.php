<?php

namespace App\Console\Commands;

use App\Estados\EstadoVenta;
use App\Estados\EstadoSunat;
use App\Models\NotaCredito;
use App\Models\NotaDebito;
use App\Models\Venta;
use App\Sunat\EnviadorSunat;
use Illuminate\Console\Command;

class EnviarComprobanteSunat extends Command
{
    protected $signature = 'sunat:enviar
                            {venta? : ID del comprobante; si se omite toma los pendientes}
                            {--tipo=Venta : Venta, NotaCredito o NotaDebito}
                            {--limite=10 : Cuantas pendientes procesar}
                            {--reintentos=3 : No reintentar comprobantes que ya fallaron mas de N veces}';

    protected $description = 'Envía el comprobante a SUNAT y guarda el CDR';

    public function handle(EnviadorSunat $enviador): int
    {
        $this->line('  Ambiente: <info>' . config('sunat.ambiente') . '</info>');

        $clase = match ($this->option('tipo')) {
            'NotaCredito' => NotaCredito::class,
            'NotaDebito'  => NotaDebito::class,
            default       => Venta::class,
        };

        $ventas = $this->argument('venta')
            ? $clase::where('id', $this->argument('venta'))->get()
            // Reintentables: lo que todavia no se envio o fallo de forma
            // recuperable. Un RECHAZADO definitivo no vuelve a la cola.
            : $clase::whereIn('estado_sunat', [EstadoSunat::NO_ENVIADO, EstadoSunat::EN_COLA, EstadoSunat::ERROR])
                // Un comprobante que ya fallo muchas veces necesita revision
                // manual: seguir reintentando solo tapa el problema.
                ->where('intentos_envio', '<', (int) $this->option('reintentos'))
                ->when($clase === Venta::class, fn ($q) => $q->where('estado', EstadoVenta::APROBADA))
                ->orderBy('id')
                ->limit((int) $this->option('limite'))
                ->get();

        if ($ventas->isEmpty()) {
            $this->info('No hay comprobantes por enviar.');

            return self::SUCCESS;
        }

        $aceptados = $rechazados = $pendientes = 0;

        foreach ($ventas as $venta) {
            try {
                $r = $enviador->enviar($venta);

                match ($r['estado']) {
                    'ACEPTADO', 'OBSERVADO' => $aceptados++,
                    'RECHAZADO' => $rechazados++,
                    default => $pendientes++,
                };

                $color = match ($r['estado']) {
                    'ACEPTADO' => 'info',
                    'OBSERVADO' => 'comment',
                    'RECHAZADO' => 'error',
                    default => 'comment',
                };

                $this->line(sprintf(
                    '  %s  <%s>%s</%s>  %s  %s',
                    $venta->documento,
                    $color, $r['estado'], $color,
                    $r['codigo'] ? "[{$r['codigo']}]" : '',
                    mb_strimwidth($r['mensaje'], 0, 80, '…')
                ));
            } catch (\Throwable $e) {
                $pendientes++;
                $this->error(sprintf('  %s  %s', $venta->documento, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '  <info>%d aceptados</info>, <error>%d rechazados</error>, %d pendientes de reintento',
            $aceptados, $rechazados, $pendientes
        ));

        return $rechazados > 0 ? self::FAILURE : self::SUCCESS;
    }
}
