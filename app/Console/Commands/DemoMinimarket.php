<?php

namespace App\Console\Commands;

use App\Demo\SimuladorMinimarket;
use Illuminate\Console\Command;

/**
 * Genera un escenario DEMO de operacion completa del minimarket.
 *
 * Los datos son SIMULADOS. No representan cifras comerciales reales ni deben
 * citarse como tales.
 *
 * Por defecto SIMULA: dice que generaria sin escribir nada. Escribir exige
 * --confirmar y ademas tipear una palabra, igual que sistema:reset-demo: esto
 * borra ventas, compras, caja y gastos para rehacerlos.
 */
class DemoMinimarket extends Command
{
    protected $signature = 'demo:minimarket
                            {--simular : Muestra que generaria sin escribir (por defecto)}
                            {--confirmar : Ejecuta de verdad}
                            {--dias=75 : Dias de operacion a simular (60-90)}
                            {--semilla= : Semilla para reproducir el mismo escenario}';

    protected $description = 'Genera un escenario DEMO coherente de operación del minimarket';

    /** Lo que hay que tipear para que el comando escriba. */
    private const PALABRA = 'GENERAR';

    /** Semilla por defecto: escenario reproducible entre corridas y tests. */
    public const SEMILLA_POR_DEFECTO = 20260920;

    public function handle(): int
    {
        if (! $this->entornoPermitido()) {
            return self::FAILURE;
        }

        $dias = (int) $this->option('dias');

        if ($dias < 60 || $dias > 90) {
            $this->error("  El periodo debe estar entre 60 y 90 dias; se pidio {$dias}.");

            return self::FAILURE;
        }

        $semilla = (int) ($this->option('semilla') ?: env('DEMO_SEED', self::SEMILLA_POR_DEFECTO));
        $escribir = (bool) $this->option('confirmar');

        $this->mostrarCabecera($semilla, $dias, $escribir);

        if (! $escribir) {
            $plan = (new SimuladorMinimarket($semilla, $dias, escribir: false))->ejecutar();
            $this->mostrarPlan($plan);

            $this->newLine();
            $this->warn('  MODO SIMULACIÓN — no se escribió nada.');
            $this->line('  Para generarlo: <fg=yellow>php artisan demo:minimarket --confirmar</>');

            return self::SUCCESS;
        }

        if (! $this->confirmacionFuerte()) {
            $this->error('  Cancelado. No se modificó nada.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  Generando escenario...');

        $resumen = (new SimuladorMinimarket($semilla, $dias, escribir: true))->ejecutar();

        $this->mostrarResumen($resumen);
        $this->newLine();
        $this->info('  Escenario DEMO generado. Verifícalo con:');
        $this->line('  <fg=yellow>php artisan demo:validar-rentabilidad</>');

        return self::SUCCESS;
    }

    /**
     * Produccion queda fuera pase lo que pase: este comando borra la operacion
     * para rehacerla con datos inventados.
     */
    private function entornoPermitido(): bool
    {
        if (app()->environment('production')) {
            $this->newLine();
            $this->error('  PROHIBIDO EN PRODUCCIÓN');
            $this->line('  Este comando borra ventas, compras, caja y gastos para sustituirlos');
            $this->line('  por datos simulados. No corre con APP_ENV=production.');
            $this->newLine();

            return false;
        }

        return true;
    }

    private function confirmacionFuerte(): bool
    {
        $this->newLine();
        $this->warn('  Esto BORRA ventas, compras, gastos, caja y notas de crédito');
        $this->warn('  y los reemplaza por datos simulados. El catálogo no se toca.');
        $this->newLine();

        // Un si/no se contesta por reflejo; tipear la palabra, no.
        return $this->ask('  Escribe '.self::PALABRA.' para continuar') === self::PALABRA;
    }

    private function mostrarCabecera(int $semilla, int $dias, bool $escribir): void
    {
        $this->newLine();
        $this->line('  <fg=cyan>ESCENARIO DEMO — MINIMARKET</>');
        $this->line('  <fg=gray>Datos simulados. No son cifras comerciales reales.</>');
        $this->newLine();
        $this->line(sprintf('  entorno   : %s', app()->environment()));
        $this->line(sprintf('  base      : %s', config('database.connections.'.config('database.default').'.database')));
        $this->line(sprintf('  semilla   : %d', $semilla));
        $this->line(sprintf('  periodo   : %d días', $dias));
        $this->line(sprintf('  modo      : %s', $escribir ? 'ESCRITURA' : 'simulación'));
        $this->newLine();
    }

    private function mostrarPlan(array $plan): void
    {
        $this->line('  <fg=cyan>Generaría</>');
        $this->line(sprintf('    período               %s → %s', $plan['desde'], $plan['hasta']));
        $this->line(sprintf('    productos operativos  %d', $plan['productos_operativos']));
        $this->line(sprintf('    proveedores           %d', $plan['proveedores']));
        $this->line(sprintf('    ventas estimadas      ~%d', $plan['ventas_estimadas']));
        $this->newLine();

        $this->line('  <fg=cyan>Rotación</>');
        foreach ($plan['por_rotacion'] as $nivel => $n) {
            $this->line(sprintf('    %-18s %4d productos', $nivel, $n));
        }
        $this->newLine();

        $this->line('  <fg=cyan>Por categoría</>');
        foreach ($plan['por_categoria'] as $categoria => $n) {
            $this->line(sprintf('    %-22s %4d', $categoria, $n));
        }
    }

    private function mostrarResumen(array $r): void
    {
        $this->newLine();
        $this->line('  <fg=cyan>Generado</>');
        foreach ([
            'productos_operativos' => 'productos operativos',
            'margen_medio' => 'margen medio (%)',
            'proveedores' => 'proveedores',
            'clientes' => 'clientes',
            'compras' => 'compras',
            'unidades_compradas' => 'unidades compradas',
            'inversion_mercaderia' => 'inversión mercadería',
            'ventas' => 'ventas',
            'unidades_vendidas' => 'unidades vendidas',
            'ingresos' => 'ingresos (con IGV)',
            'ticket_promedio' => 'ticket promedio',
            'notas_credito' => 'notas de crédito',
        ] as $clave => $etiqueta) {
            if (isset($r[$clave])) {
                $this->line(sprintf('    %-22s %s', $etiqueta, is_float($r[$clave]) ? number_format($r[$clave], 2) : $r[$clave]));
            }
        }

        if (! empty($r['pagos'])) {
            $this->newLine();
            $this->line('  <fg=cyan>Medios de pago</>');
            foreach ($r['pagos'] as $forma => $n) {
                $this->line(sprintf('    %-16s %4d ventas', $forma, $n));
            }
        }
    }
}
