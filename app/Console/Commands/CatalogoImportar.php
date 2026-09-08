<?php

namespace App\Console\Commands;

use App\Catalogo\Csv;
use App\Catalogo\ImportadorCatalogo;
use App\Catalogo\ResultadoImportacion;
use App\Catalogo\Rutas;
use App\Models\Auditoria;
use Illuminate\Console\Command;

/**
 * catalogo_maestro.csv -> tabla productos.
 *
 * Por defecto simula: hay que pedir --confirmar para que escriba. Cargar miles
 * de productos sobre la base de una tienda que ya opera no es algo que deba
 * pasar por teclear mal un comando.
 */
class CatalogoImportar extends Command
{
    protected $signature = 'catalogo:importar
                            {--archivo= : CSV a importar; por defecto catalogo_maestro.csv}
                            {--confirmar : Escribe en la base. Sin esto solo simula}
                            {--actualizar-precios : Pisa los precios de los productos que ya existen}';

    protected $description = 'Importa el catálogo maestro a la tabla productos';

    public function handle(): int
    {
        $archivo = $this->option('archivo') ?: Rutas::maestro();

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        $total = Csv::contar($archivo);

        if ($total === 0) {
            $this->warn("{$archivo} no tiene filas. Nada que importar.");

            return self::SUCCESS;
        }

        $simular = ! $this->option('confirmar');

        $this->line('  Archivo: <info>'.$archivo.'</info>');
        $this->line('  Filas:   <info>'.number_format($total).'</info>');
        $this->line('  Modo:    '.($simular ? '<comment>SIMULACIÓN (no escribe)</comment>' : '<info>ESCRITURA</info>'));

        if ($this->option('actualizar-precios') && ! $simular) {
            $this->warn('  Los precios de los productos existentes se van a sobrescribir.');
        }

        $this->newLine();

        $barra = $this->output->createProgressBar($total);
        $barra->start();

        $importador = new ImportadorCatalogo($simular, (bool) $this->option('actualizar-precios'));

        $resultado = $importador->importar(
            Csv::porFilas($archivo),
            fn () => $barra->advance()
        );

        $barra->finish();
        $this->newLine(2);

        $this->resumen($resultado, $simular);
        $this->escribirRechazos($resultado);

        if (! $simular) {
            $this->dejarRastro($resultado, $archivo);
        }

        // Que haya rechazos no es un fallo del comando: es informacion. El
        // codigo de salida solo avisa si NO entro nada.
        if ($resultado->totalEscritas() === 0 && $resultado->totalRechazadas() > 0) {
            $this->error('No se importó ningún producto.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function resumen(ResultadoImportacion $resultado, bool $simular): void
    {
        $this->table(['Concepto', 'Cantidad'], [
            ['Filas leídas',              number_format($resultado->leidas)],
            ['Productos creados',         number_format($resultado->creados)],
            ['Productos actualizados',    number_format($resultado->actualizados)],
            ['Sin cambios',               number_format($resultado->sinCambios)],
            ['Códigos de barras nuevos',  number_format($resultado->codigosBarrasAsignados)],
            ['Filas rechazadas',          number_format($resultado->totalRechazadas())],
        ]);

        if ($resultado->totalRechazadas() > 0) {
            $this->newLine();
            $this->warn('Motivos de rechazo:');

            foreach ($resultado->rechazosPorMotivo() as $motivo => $cantidad) {
                // str_pad cuenta bytes y los acentos desalinearian la tabla.
                $relleno = str_repeat(' ', max(1, 45 - mb_strlen($motivo)));
                $this->line('  '.$motivo.$relleno.number_format($cantidad));
            }
        }

        $this->newLine();

        if ($simular) {
            $this->info('Simulación: no se escribió nada. Repetí con --confirmar para importar.');

            return;
        }

        $this->info(sprintf(
            '%s producto(s) en la base. El stock no se toca: entra por compras.',
            number_format($resultado->totalEscritas())
        ));
    }

    /**
     * Una sola entrada en la bitacora por importacion.
     *
     * El importador apaga la auditoria por fila a proposito: una importacion es
     * UNA accion, y 4.800 lineas de "Creo producto X" dejarian la bitacora
     * inservible para ver los cambios que si hizo una persona a mano.
     */
    private function dejarRastro(ResultadoImportacion $resultado, string $archivo): void
    {
        if ($resultado->totalEscritas() === 0) {
            return;
        }

        Auditoria::registrar(
            'IMPORTO',
            'Producto',
            null,
            sprintf(
                'Importó el catálogo desde %s: %s creado(s), %s actualizado(s), %s rechazado(s)',
                basename($archivo),
                number_format($resultado->creados),
                number_format($resultado->actualizados),
                number_format($resultado->totalRechazadas())
            ),
        );
    }

    private function escribirRechazos(ResultadoImportacion $resultado): void
    {
        $ruta = Rutas::procesados('rechazos_importacion.csv');

        Csv::escribir(
            $ruta,
            ['codigo_interno', 'descripcion', 'operacion', 'motivo'],
            $resultado->rechazos
        );

        if ($resultado->totalRechazadas() > 0) {
            $this->line('  Detalle de los rechazos en <info>'.$ruta.'</info>');
        }
    }
}
