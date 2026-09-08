<?php

namespace App\Console\Commands;

use App\Catalogo\Csv;
use App\Catalogo\Rutas;
use Illuminate\Console\Command;

/**
 * Saca una muestra del catalogo maestro para que la revise una persona.
 *
 * Va entre la simulacion y la importacion. Un resumen de "1.240 filas listas"
 * no permite darse cuenta de que las galletas quedaron clasificadas como
 * golosinas o de que una marca se normalizo mal: eso se ve mirando filas
 * concretas, repartidas entre categorias, con la URL al lado para poder
 * comprobar cualquiera contra la fuente.
 */
class CatalogoMuestra extends Command
{
    protected $signature = 'catalogo:muestra
                            {--archivo= : CSV a muestrear; por defecto catalogo_maestro.csv}
                            {--cantidad=50 : Cuántas filas mostrar}
                            {--categoria= : Solo esta categoría}
                            {--sin-ean : Solo las filas que quedaron sin código de barras}
                            {--pendientes : Solo las que quedaron PENDIENTE tributariamente}
                            {--csv= : Además, escribe la muestra a este archivo}';

    protected $description = 'Muestra representativa del catálogo maestro para revisión humana';

    public function handle(): int
    {
        $archivo = $this->option('archivo') ?: Rutas::maestro();

        if (! is_file($archivo)) {
            $this->error("  No existe: {$archivo}");
            $this->line('  Corré primero <fg=yellow>php artisan catalogo:normalizar</>');

            return self::FAILURE;
        }

        $filas = $this->filtrar(Csv::leer($archivo));

        if ($filas === []) {
            $this->warn('  Ninguna fila cumple el filtro.');

            return self::SUCCESS;
        }

        $muestra = $this->repartir($filas, max(1, (int) $this->option('cantidad')));

        $this->mostrar($muestra, count($filas));

        if ($destino = $this->option('csv')) {
            Csv::escribir($destino, array_keys($muestra[0]), $muestra);
            $this->newLine();
            $this->info("  Muestra escrita en {$destino}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int,array<string,string>>  $filas
     * @return array<int,array<string,string>>
     */
    private function filtrar(array $filas): array
    {
        if ($categoria = $this->option('categoria')) {
            $filas = array_filter(
                $filas,
                fn (array $f) => strcasecmp(trim($f['categoria'] ?? ''), trim($categoria)) === 0,
            );
        }

        if ($this->option('sin-ean')) {
            $filas = array_filter($filas, fn (array $f) => trim($f['codigo_barras'] ?? '') === '');
        }

        if ($this->option('pendientes')) {
            $filas = array_filter($filas, fn (array $f) => strtoupper(trim($f['operacion'] ?? '')) === 'PENDIENTE');
        }

        return array_values($filas);
    }

    /**
     * Reparte la muestra entre categorias en vez de tomar las primeras N.
     *
     * Las primeras N serian todas de la misma categoria —el CSV sale ordenado—
     * y no dirian nada sobre el resto del catalogo.
     *
     * @param  array<int,array<string,string>>  $filas
     * @return array<int,array<string,string>>
     */
    private function repartir(array $filas, int $cantidad): array
    {
        $porCategoria = [];

        foreach ($filas as $fila) {
            $porCategoria[$fila['categoria'] ?? 'SIN CATEGORIA'][] = $fila;
        }

        ksort($porCategoria);

        $cupo = max(1, (int) ceil($cantidad / max(1, count($porCategoria))));
        $muestra = [];

        foreach ($porCategoria as $grupo) {
            // Del medio del grupo, no del principio: las primeras filas de una
            // categoria suelen ser las mismas marcas grandes.
            $paso = max(1, (int) floor(count($grupo) / $cupo));

            for ($i = 0; $i < count($grupo) && count($muestra) < $cantidad; $i += $paso) {
                $muestra[] = $grupo[$i];
            }
        }

        return array_slice($muestra, 0, $cantidad);
    }

    /** @param array<int,array<string,string>> $muestra */
    private function mostrar(array $muestra, int $total): void
    {
        $this->newLine();
        $this->line(sprintf('  <fg=cyan>MUESTRA</> %d de %d filas del maestro', count($muestra), $total));
        $this->newLine();

        $filas = array_map(fn (array $f) => [
            $f['codigo_interno'] ?? '',
            $this->corto($f['categoria'] ?? '', 14).'/'.$this->corto($f['subcategoria'] ?? '', 12),
            $this->corto($f['marca'] ?? '', 14),
            $this->corto($f['descripcion'] ?? '', 44),
            $f['presentacion'] ?? '',
            $f['codigo_barras'] !== '' ? $f['codigo_barras'] : '—',
            $f['operacion'] ?? '',
            ($f['afecto_isc'] ?? '0') === '1' ? 'ISC' : '',
            ($f['afecto_ivap'] ?? '0') === '1' ? 'IVAP' : '',
            $f['fuente'] ?? '',
        ], $muestra);

        $this->table(
            ['SKU', 'CATEGORÍA/SUBCAT', 'MARCA', 'DESCRIPCIÓN', 'PRES.', 'EAN', 'IGV', 'ISC', 'IVAP', 'FUENTE'],
            $filas,
        );

        $this->line('  <fg=gray>Cada fila se puede comprobar entrando a su url_fuente en el CSV maestro.</>');
        $this->line('  <fg=gray>Si algo está mal clasificado, se corrige el diccionario y se vuelve a normalizar.</>');
    }

    private function corto(string $texto, int $largo): string
    {
        return mb_strlen($texto) > $largo ? mb_substr($texto, 0, $largo - 1).'…' : $texto;
    }
}
