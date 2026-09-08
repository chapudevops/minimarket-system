<?php

namespace App\Console\Commands;

use App\Sistema\ResetDemo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deja la instalacion como una tienda nueva: sin productos, sin ventas, sin
 * stock, pero con la empresa, los usuarios, los almacenes, las cajas y las
 * series tal como estaban.
 *
 * Por defecto SIMULA. Escribir requiere --confirmar y ademas tipear LIMPIAR:
 * un Y/N se contesta por reflejo, y esto borra todo lo que la tienda produjo.
 */
class SistemaResetDemo extends Command
{
    protected $signature = 'sistema:reset-demo
                            {--confirmar : Ejecuta de verdad. Sin esto solo simula}
                            {--reiniciar-correlativos : Devuelve las series a 0}
                            {--limpiar-flota : Borra tambien conductores y vehiculos}
                            {--sin-backup : Omite el respaldo previo (no recomendado)}';

    protected $description = 'Borra la data operativa/demo conservando la configuración del sistema';

    /** Lo que hay que tipear para que el comando escriba. */
    private const PALABRA = 'LIMPIAR';

    public function handle(): int
    {
        if (! $this->entornoPermitido()) {
            return self::FAILURE;
        }

        $reset = new ResetDemo(
            limpiarFlota: (bool) $this->option('limpiar-flota'),
            reiniciarCorrelativos: (bool) $this->option('reiniciar-correlativos'),
        );

        $this->mostrarConexion();
        $inventario = $reset->inventario();
        $this->mostrarPlan($reset, $inventario);

        if (! $this->option('confirmar')) {
            $this->newLine();
            $this->warn('  MODO SIMULACIÓN — no se modificó nada.');
            $this->line('  Para ejecutar de verdad: <fg=yellow>php artisan sistema:reset-demo --confirmar</>');

            return self::SUCCESS;
        }

        if (! $this->confirmacionFuerte()) {
            $this->newLine();
            $this->error('  Cancelado. No se modificó nada.');

            return self::FAILURE;
        }

        if (! $this->option('sin-backup') && ! $this->respaldar()) {
            return self::FAILURE;
        }

        return $this->ejecutar($reset, $inventario);
    }

    /**
     * Produccion queda fuera, incluso con --confirmar.
     *
     * No hay flag que lo habilite: si algun dia hiciera falta, que sea una
     * decision consciente de alguien editando este archivo, no un parametro
     * que se pueda pegar en una terminal equivocada.
     */
    private function entornoPermitido(): bool
    {
        if (app()->environment('production')) {
            $this->newLine();
            $this->error('  PROHIBIDO EN PRODUCCIÓN');
            $this->line('  Este comando borra toda la data operativa y no corre con APP_ENV=production.');
            $this->line('  Si de verdad hace falta, se hace a mano y con respaldo verificado.');
            $this->newLine();

            return false;
        }

        return true;
    }

    private function mostrarConexion(): void
    {
        $conexion = config('database.default');
        $config = config("database.connections.{$conexion}");

        $this->newLine();
        $this->line('  <fg=cyan>CONEXIÓN</>');
        $this->line(sprintf('  %-14s %s', 'entorno', app()->environment()));
        $this->line(sprintf('  %-14s %s', 'conexión', $conexion));
        $this->line(sprintf('  %-14s %s:%s', 'host', $config['host'] ?? '?', $config['port'] ?? '?'));
        $this->line(sprintf('  %-14s <fg=yellow>%s</>', 'base', $config['database'] ?? '?'));
    }

    private function mostrarPlan(ResetDemo $reset, array $inventario): void
    {
        $this->newLine();
        $this->line('  <fg=cyan>SE VA A ELIMINAR</>');

        $total = 0;

        foreach ($inventario as $modulo => $tablas) {
            if (array_sum($tablas) === 0) {
                continue;
            }

            $this->newLine();
            $this->line("  <options=bold>{$modulo}</>");

            foreach ($tablas as $tabla => $filas) {
                $total += $filas;
                $color = $filas > 0 ? 'yellow' : 'gray';
                $this->line(sprintf('    %-26s <fg=%s>%6d</> → 0', $tabla, $color, $filas));
            }
        }

        $clientes = $reset->planClientes();
        $this->newLine();
        $this->line('  <options=bold>Excepción</>');
        $this->line(sprintf('    %-26s <fg=green>%6d</> conservado (%s)',
            'cliente genérico', $clientes['conservados'], $clientes['generico'] ?? 'se recreará'));

        $archivos = $reset->planArchivos();
        if (array_sum($archivos) > 0) {
            $this->newLine();
            $this->line('  <options=bold>Archivos</>');
            foreach ($archivos as $carpeta => $n) {
                $this->line(sprintf('    %-26s <fg=yellow>%6d</> → 0', $carpeta, $n));
            }
        }

        $this->newLine();
        $this->line('  <fg=cyan>SE CONSERVA</>');
        foreach ($reset->verificarIntocables() as $tabla => $filas) {
            $this->line(sprintf('    %-26s <fg=green>%6d</>', $tabla, $filas));
        }

        $correlativos = $reset->planCorrelativos();
        if ($correlativos !== []) {
            $this->newLine();
            $this->line('  <options=bold>Correlativos</>');
            foreach ($correlativos as $c) {
                $cambia = $c['antes'] !== $c['despues'];
                $this->line(sprintf('    %-8s %-16s %6d → <fg=%s>%d</>',
                    $c['serie'], $c['tipo'], $c['antes'], $cambia ? 'yellow' : 'green', $c['despues']));
            }
            if (! $this->option('reiniciar-correlativos')) {
                $this->line('    <fg=gray>(usa --reiniciar-correlativos para devolverlos a 0)</>');
            }
        }

        $this->newLine();
        $this->line("  <options=bold>TOTAL A ELIMINAR: {$total} registros</>");
    }

    private function confirmacionFuerte(): bool
    {
        $this->newLine();
        $this->line('  <bg=red;fg=white;options=bold>                                                          </>');
        $this->line('  <bg=red;fg=white;options=bold>  VAS A ELIMINAR TODOS LOS DATOS OPERATIVOS               </>');
        $this->line('  <bg=red;fg=white;options=bold>  DE LA BASE '.str_pad((string) config('database.connections.'.config('database.default').'.database'), 45).'</>');
        $this->line('  <bg=red;fg=white;options=bold>                                                          </>');
        $this->newLine();

        $respuesta = $this->ask('  Escribe '.self::PALABRA.' para continuar');

        return trim((string) $respuesta) === self::PALABRA;
    }

    private function respaldar(): bool
    {
        $conexion = config('database.default');
        $config = config("database.connections.{$conexion}");
        $directorio = storage_path('backups');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // La hora va en el huso de la tienda, no en el de la app. app.timezone
        // es UTC y no se toca —de ahi salen las fechas de emision de los
        // comprobantes— pero un backup llamado "145249" cuando el reloj marca
        // las 09:52 es exactamente el archivo que uno elige mal apurado.
        $archivo = sprintf(
            '%s/pre-reset-%s-%s.sql',
            $directorio,
            $config['database'] ?? 'bd',
            now()->setTimezone(config('app.timezone_local', 'America/Lima'))->format('Ymd-His'),
        );

        $comando = sprintf(
            'mysqldump -h%s -P%s -u%s %s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? 'root'),
            ($config['password'] ?? '') !== '' ? '-p'.escapeshellarg($config['password']) : '',
            escapeshellarg($config['database'] ?? ''),
            escapeshellarg($archivo),
        );

        $this->newLine();
        $this->line('  Generando respaldo…');

        exec($comando, $salida, $codigo);

        if ($codigo !== 0 || ! is_file($archivo) || filesize($archivo) === 0) {
            $this->error('  No se pudo generar el respaldo. El reset se cancela.');
            $this->line('  '.implode("\n  ", $salida));
            $this->line('  Si el respaldo ya existe, repetí con <fg=yellow>--sin-backup</>.');

            return false;
        }

        $this->info(sprintf('  Respaldo: %s (%s KB)', $archivo, number_format(filesize($archivo) / 1024)));

        return true;
    }

    private function ejecutar(ResetDemo $reset, array $inventario): int
    {
        $this->newLine();
        $this->line('  Limpiando base…');

        try {
            $eliminados = $reset->ejecutar();
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('  La limpieza falló y se revirtió por completo.');
            $this->line('  '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('  Base limpia. Transacción confirmada.');

        $archivos = $reset->limpiarArchivos();

        $this->line(sprintf('  Archivos eliminados: %d', $archivos['eliminados']));

        foreach ($archivos['fallos'] as $fallo) {
            $this->warn("  No se pudo borrar: {$fallo}");
        }

        $reset->registrarEnBitacora($eliminados, $archivos);

        $this->resumen($eliminados, $reset);

        return $this->verificar($reset);
    }

    private function resumen(array $eliminados, ResetDemo $reset): void
    {
        $this->newLine();
        $this->line('  <fg=cyan>ELIMINADO</>');

        foreach (array_filter($eliminados) as $tabla => $filas) {
            $this->line(sprintf('    %-26s %6d', $tabla, $filas));
        }

        $this->newLine();
        $this->line('  <fg=cyan>CONSERVADO</>');

        foreach ($reset->verificarIntocables() as $tabla => $filas) {
            $this->line(sprintf('    %-26s %6d', $tabla, $filas));
        }

        $this->line(sprintf('    %-26s %6d', 'clientes (genérico)', DB::table('clientes')->count()));
        $this->line(sprintf('    %-26s %6d', 'auditorias (el reset)', DB::table('auditorias')->count()));
    }

    /** Chequeo final: si algo quedo con filas, el comando falla. */
    private function verificar(ResetDemo $reset): int
    {
        $obligatorias = [
            'productos', 'ventas', 'venta_detalles', 'compras', 'compra_detalles',
            'producto_almacen', 'notas_credito', 'notas_debito', 'cotizaciones',
            'notas_venta', 'guias_remision', 'combos', 'gastos', 'apertura_cajas',
        ];

        $sucias = [];

        foreach ($obligatorias as $tabla) {
            if (DB::getSchemaBuilder()->hasTable($tabla) && ($n = DB::table($tabla)->count()) > 0) {
                $sucias[$tabla] = $n;
            }
        }

        $this->newLine();

        if ($sucias !== []) {
            $this->error('  El reset no quedó limpio:');
            foreach ($sucias as $tabla => $n) {
                $this->line("    {$tabla}: {$n}");
            }

            return self::FAILURE;
        }

        $this->info('  Reset completo. La base quedó como una instalación nueva.');
        $this->line('  <fg=gray>Siguiente paso: cargar el catálogo real con catalogo:importar</>');

        return self::SUCCESS;
    }
}
