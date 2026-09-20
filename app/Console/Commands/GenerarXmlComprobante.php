<?php

namespace App\Console\Commands;

use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use App\Models\Venta;
use App\Sunat\GeneradorXml;
use Illuminate\Console\Command;

class GenerarXmlComprobante extends Command
{
    protected $signature = 'sunat:xml
                            {venta? : ID de la venta; si se omite toma las pendientes}
                            {--limite=10 : Cuantas pendientes procesar}
                            {--dry-run : Genera y valida sin guardar ni tocar la base}';

    protected $description = 'Genera y firma el XML UBL 2.1 de un comprobante (no lo envia a SUNAT)';

    public function handle(GeneradorXml $generador): int
    {
        $ventas = $this->argument('venta')
            ? Venta::where('id', $this->argument('venta'))->get()
            : Venta::where('estado', EstadoVenta::APROBADA)
                ->where('estado_sunat', EstadoSunat::NO_ENVIADO)
                ->whereNull('ruta_xml')
                ->orderBy('id')
                ->limit((int) $this->option('limite'))
                ->get();

        if ($ventas->isEmpty()) {
            $this->info('No hay comprobantes para generar.');

            return self::SUCCESS;
        }

        $simulacion = (bool) $this->option('dry-run');
        $fallidos = 0;

        foreach ($ventas as $venta) {
            try {
                $resultado = $generador->paraVenta($venta, guardar: ! $simulacion);

                if (! $simulacion) {
                    $venta->update([
                        'hash_xml' => $resultado['hash'],
                        'ruta_xml' => $resultado['ruta'],
                    ]);

                    // El QR lleva el hash, asi que se rearma DESPUES de
                    // guardarlo: dentro del mismo update, contenidoQr() leeria
                    // todavia el valor viejo.
                    $venta->update(['codigo_qr' => $venta->contenidoQr()]);
                }

                $this->line(sprintf(
                    '  <info>%s</info>  %s  %s bytes  hash %s',
                    $venta->documento,
                    $resultado['nombre'],
                    number_format(strlen($resultado['xml'])),
                    $resultado['hash']
                ));
            } catch (\Throwable $e) {
                $fallidos++;
                $this->error(sprintf('  %s  %s', $venta->documento, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%d generados, %d con error%s',
            $ventas->count() - $fallidos,
            $fallidos,
            $simulacion ? ' (simulacion: no se guardo nada)' : ''
        ));

        return $fallidos > 0 ? self::FAILURE : self::SUCCESS;
    }
}
