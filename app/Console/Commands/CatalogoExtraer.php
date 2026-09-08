<?php

namespace App\Console\Commands;

use App\Catalogo\Csv;
use App\Catalogo\Fuentes\ClienteVtex;
use App\Catalogo\Fuentes\ExtractorVtex;
use App\Catalogo\Fuentes\FuenteNoAutomatizable;
use App\Catalogo\Fuentes\MapeoFuentes;
use App\Catalogo\Rutas;
use Illuminate\Console\Command;

/**
 * Baja el catalogo publico de una tienda y lo deja como CSV RAW.
 *
 * Antes de pedir nada consulta el robots.txt del sitio y verifica que la ruta
 * este permitida; si no lo esta, se niega. Entre peticiones espera, y ante un
 * 429 espera mas. No hay forma de saltear ninguna de las dos cosas.
 *
 * Es reanudable: si el archivo RAW ya existe, lee las URLs que contiene y no
 * vuelve a pedir esas categorias. Cortar con Ctrl+C y volver a lanzarlo
 * continua donde iba.
 */
class CatalogoExtraer extends Command
{
    protected $signature = 'catalogo:extraer
                            {fuente : plazavea o metro}
                            {--limite=0 : Corta despues de N productos (0 = sin limite)}
                            {--por-pagina=50 : Productos por peticion (max 50 en VTEX)}
                            {--reiniciar : Ignora lo ya descargado y empieza de cero}';

    protected $description = 'Descarga el catálogo público de una fuente al CSV RAW';

    /** Host y nombre de archivo de cada fuente habilitada. */
    private const FUENTES = [
        'plazavea' => ['host' => 'www.plazavea.com.pe', 'nombre' => 'PLAZAVEA'],
        'metro'    => ['host' => 'www.metro.pe',        'nombre' => 'METRO'],
    ];

    public function handle(): int
    {
        $clave = strtolower(trim((string) $this->argument('fuente')));

        if (! isset(self::FUENTES[$clave])) {
            $this->error('  Fuente desconocida. Disponibles: '.implode(', ', array_keys(self::FUENTES)));
            $this->line('  Tottus y Vivanda quedan fuera a propósito: ver database/README-catalogo.md');

            return self::FAILURE;
        }

        ['host' => $host, 'nombre' => $nombre] = self::FUENTES[$clave];

        $cliente = new ClienteVtex($host);
        $mapeo = MapeoFuentes::desdeArchivo();
        $extractor = new ExtractorVtex($nombre, $mapeo);

        $this->newLine();
        $this->line("  <fg=cyan>FUENTE</> {$nombre} · {$host}");

        if (! $this->verificarRobots($cliente)) {
            return self::FAILURE;
        }

        $archivo = Rutas::raw(strtolower($nombre).'.csv');
        $yaVistas = $this->option('reiniciar') ? [] : $this->urlsYaDescargadas($archivo);

        if ($yaVistas !== []) {
            $this->line('  <fg=gray>reanudando: '.count($yaVistas).' productos ya descargados</>');
        }

        $rutas = $mapeo->rutasDe($nombre);
        $this->line('  <fg=gray>'.count($rutas).' categorías a recorrer</>');
        $this->newLine();

        $filas = $this->recorrer($cliente, $extractor, $rutas, $host, $yaVistas);

        if ($filas === []) {
            $this->warn('  No se obtuvo ninguna fila nueva.');

            return self::SUCCESS;
        }

        $this->guardar($archivo, $filas, $yaVistas !== []);
        $this->resumen($archivo, $filas, $cliente, $extractor, $mapeo);

        return self::SUCCESS;
    }

    private function verificarRobots(ClienteVtex $cliente): bool
    {
        $robots = $cliente->robots();
        $ruta = '/api/catalog_system/pub/products/search';

        if (! $robots->accesible()) {
            $this->error('  No se pudo leer el robots.txt. La fuente no se automatiza.');

            return false;
        }

        if (! $robots->permite($ruta)) {
            $this->error("  robots.txt prohíbe {$ruta}. La fuente no se automatiza.");
            $this->line('  Cargala a mano en data/catalogo-minimarket/raw/ con el formato de _PLANTILLA.csv');

            return false;
        }

        $this->line('  <fg=green>robots.txt: la ruta está permitida</>');

        return true;
    }

    /**
     * @param  array<int,array{ruta:string,segmentos:array<int,string>}>  $rutas
     * @param  array<string,true>  $yaVistas
     * @return array<int,array<string,string>>
     */
    private function recorrer(
        ClienteVtex $cliente,
        ExtractorVtex $extractor,
        array $rutas,
        string $host,
        array $yaVistas,
    ): array {
        $porPagina = max(1, min(50, (int) $this->option('por-pagina')));
        $limite = max(0, (int) $this->option('limite'));
        $fecha = now()->setTimezone(config('app.timezone_local', 'America/Lima'))->format('Y-m-d');

        $filas = [];
        $vistas = $yaVistas;

        $barra = $this->output->createProgressBar(count($rutas));
        $barra->setFormat('  %current%/%max% [%bar%] %message%');
        $barra->setMessage('');
        $barra->start();

        foreach ($rutas as $categoria) {
            $barra->setMessage(implode('/', $categoria['segmentos']));

            $desde = 0;

            while (true) {
                try {
                    $pagina = $cliente->porCategoria($categoria['segmentos'], $desde, $porPagina);
                } catch (FuenteNoAutomatizable $e) {
                    $barra->clear();
                    $this->warn('  '.$e->getMessage());
                    $barra->display();

                    break;
                }

                if ($pagina === []) {
                    break;
                }

                foreach ($pagina as $producto) {
                    foreach ($extractor->filasDe($producto, $host, $fecha) as $fila) {
                        $huella = $fila['url_fuente'].'|'.$fila['descripcion'];

                        if (isset($vistas[$huella])) {
                            continue;
                        }

                        $vistas[$huella] = true;
                        $filas[] = $fila;
                    }
                }

                $desde += $porPagina;

                // VTEX no devuelve mas alla de 2.500 posiciones por consulta.
                if (count($pagina) < $porPagina || $desde >= 2500) {
                    break;
                }

                if ($limite > 0 && count($filas) >= $limite) {
                    break;
                }
            }

            $barra->advance();

            if ($limite > 0 && count($filas) >= $limite) {
                break;
            }
        }

        $barra->finish();
        $this->newLine(2);

        return $limite > 0 ? array_slice($filas, 0, $limite) : $filas;
    }

    /** @param array<int,array<string,string>> $filas */
    private function guardar(string $archivo, array $filas, bool $anexar): void
    {
        if (! is_dir(dirname($archivo))) {
            mkdir(dirname($archivo), 0755, true);
        }

        if ($anexar && is_file($archivo)) {
            $previas = Csv::leer($archivo);
            $filas = array_merge($previas, $filas);
        }

        Csv::escribir($archivo, ExtractorVtex::columnas(), $filas);
    }

    /**
     * URLs ya presentes en el RAW, para no volver a pedirlas.
     *
     * @return array<string,true>
     */
    private function urlsYaDescargadas(string $archivo): array
    {
        if (! is_file($archivo)) {
            return [];
        }

        $vistas = [];

        foreach (Csv::leer($archivo) as $fila) {
            $vistas[($fila['url_fuente'] ?? '').'|'.($fila['descripcion'] ?? '')] = true;
        }

        return $vistas;
    }

    /** @param array<int,array<string,string>> $filas */
    private function resumen(
        string $archivo,
        array $filas,
        ClienteVtex $cliente,
        ExtractorVtex $extractor,
        MapeoFuentes $mapeo,
    ): void {
        $stats = $extractor->estadisticas();

        $this->line('  <fg=cyan>DESCARGA</>');
        $this->line(sprintf('    %-30s %s', 'archivo', $archivo));
        $this->line(sprintf('    %-30s %d', 'filas nuevas', count($filas)));
        $this->line(sprintf('    %-30s %d', 'peticiones HTTP', $cliente->peticiones()));
        $this->line(sprintf('    %-30s %d', 'reintentos', $cliente->reintentos()));

        $this->newLine();
        $this->line('  <fg=cyan>CÓDIGOS DE BARRAS</>');
        $this->line(sprintf('    %-30s <fg=green>%d</>', 'con EAN verificable', $stats['con_ean']));
        $this->line(sprintf('    %-30s %d', 'sin EAN (queda NULL)', $stats['sin_ean']));
        $this->line(sprintf('    %-30s %d', 'EAN roto (no cierra dígito)', $stats['ean_invalido']));
        $this->line(sprintf('    %-30s %d', 'GTIN-8 interno de tienda', $stats['ean_interno_retailer']));

        $this->newLine();
        $this->line('  <fg=cyan>DESCARTADOS</>');
        $this->line(sprintf('    %-30s %d', 'packs y combos de tienda', $stats['descartados_pack']));
        $this->line(sprintf('    %-30s %d', 'categoría sin mapear', $stats['descartados_sin_categoria']));
        $this->line(sprintf('    %-30s %d', 'sin nombre', $stats['descartados_sin_nombre']));

        $sinMapear = $mapeo->sinMapear();

        if ($sinMapear !== []) {
            $this->newLine();
            $this->line('  <fg=gray>rutas de origen sin mapear (las 8 más frecuentes):</>');

            foreach (array_slice($sinMapear, 0, 8, true) as $ruta => $veces) {
                $this->line(sprintf('    <fg=gray>%5d  %s</>', $veces, $ruta));
            }
        }
    }
}
