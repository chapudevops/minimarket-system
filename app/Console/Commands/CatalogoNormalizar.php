<?php

namespace App\Console\Commands;

use App\Catalogo\AptitudMinimarket;
use App\Catalogo\ClaveProducto;
use App\Catalogo\Csv;
use App\Catalogo\DetectorDuplicados;
use App\Catalogo\EsquemaMaestro;
use App\Catalogo\EsquemaRaw;
use App\Catalogo\GeneradorCodigoInterno;
use App\Catalogo\Normalizador;
use App\Catalogo\NormalizadorPresentacion;
use App\Catalogo\Rutas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RAW -> catalogo maestro.
 *
 * No toca la base de datos salvo para leer que codigos internos ya existen y
 * no repartirlos de nuevo. La importacion a productos es un paso aparte y
 * todavia no esta hecha.
 */
class CatalogoNormalizar extends Command
{
    protected $signature = 'catalogo:normalizar
                            {--solo-validar : Revisa el RAW y no escribe nada}
                            {--por-subcategoria=0 : Primer lote: como maximo N referencias por subcategoria (0 = todas)}
                            {--sin-bd : No consulta productos para sembrar los correlativos}';

    protected $description = 'Normaliza los CSV de data/catalogo-minimarket/raw y arma el catalogo maestro';

    public function handle(): int
    {
        $archivos = Rutas::archivosRaw();

        if ($archivos === []) {
            $this->warn('No hay archivos en ' . Rutas::raw() . '. Nada que normalizar.');

            return self::SUCCESS;
        }

        $normalizador = Normalizador::porDefecto($this->generador());
        $taxonomia = $normalizador->taxonomia();

        $filasRaw = [];
        $errores = [];
        $descartes = [];
        $aptitudes = new AptitudMinimarket();

        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            $contenido = Csv::leer($archivo);

            foreach ($contenido as $numero => $fila) {
                $problemas = EsquemaRaw::validar($fila, $taxonomia);

                if ($problemas !== []) {
                    $errores[] = [
                        'archivo'     => $nombre,
                        // +2: la cabecera ocupa la linea 1 y las filas se
                        // cuentan desde 0.
                        'linea'       => $numero + 2,
                        'descripcion' => $fila['descripcion'] ?? '',
                        'problemas'   => implode('; ', $problemas),
                    ];

                    continue;
                }

                // Los supermercados de los que sale el RAW tambien venden
                // secadoras de pelo y televisores. Lo que no es surtido de
                // minimarket no entra, y queda listado con su motivo.
                $aptitud = $aptitudes->evaluar($fila, $taxonomia);

                if ($aptitud !== AptitudMinimarket::APTO) {
                    $descartes[] = [
                        'archivo'      => $nombre,
                        'linea'        => $numero + 2,
                        'aptitud'      => $aptitud,
                        'categoria'    => $fila['categoria'] ?? '',
                        'subcategoria' => $fila['subcategoria'] ?? '',
                        'descripcion'  => $fila['descripcion'] ?? '',
                        'motivo'       => $aptitudes->motivo(),
                    ];

                    if ($aptitud === AptitudMinimarket::FUERA) {
                        continue;
                    }
                }

                $filasRaw[] = $fila;
            }

            $this->line(sprintf('  %-28s %d fila(s)', $nombre, count($contenido)));
        }

        $this->resumenErrores($errores);

        if ($this->option('solo-validar')) {
            $this->info('Solo validacion: no se escribio ningun archivo.');

            return $errores === [] ? self::SUCCESS : self::FAILURE;
        }

        // Se agrupa ANTES de codificar para que la repeticion no consuma un
        // correlativo: el codigo se emite una sola vez por producto real.
        $detector = new DetectorDuplicados(new ClaveProducto(
            $normalizador->marcas(),
            new NormalizadorPresentacion(),
        ));
        $detector->agregarTodas($filasRaw);

        $maestro = [];
        $sinClasificar = 0;

        foreach ($detector->unicos() as $raw) {
            $fila = $normalizador->normalizar($raw);

            if ($fila === null) {
                $sinClasificar++;

                continue;
            }

            $maestro[] = $fila;
        }

        $maestro = $this->fusionarPorEan($maestro);
        $maestro = $this->primerLote($maestro);
        $maestro = $this->conservarLoCargadoAMano($maestro, $normalizador);

        $this->escribirDescartes($descartes);
        $this->escribirDuplicados($detector);
        $this->escribirSospechosos($detector);
        $this->escribirErrores($errores);
        $this->escribirPendientes($maestro);
        $this->escribirMarcasDesconocidas($normalizador->marcas()->desconocidas());

        Csv::escribir(Rutas::procesados('catalogo_normalizado.csv'), EsquemaMaestro::COLUMNAS, $maestro);
        Csv::escribir(Rutas::maestro(), EsquemaMaestro::COLUMNAS, $maestro);

        $this->newLine();
        $this->table(['Concepto', 'Cantidad'], [
            ['Filas RAW leidas',            $detector->totalFilas() + count($errores)],
            ['Filas rechazadas',            count($errores)],
            ['Fuera del surtido',            count(array_filter($descartes, fn ($d) => $d['aptitud'] === AptitudMinimarket::FUERA))],
            ['A revisar por aptitud',        count(array_filter($descartes, fn ($d) => $d['aptitud'] === AptitudMinimarket::REVISAR))],
            ['Repeticiones descartadas',    $detector->totalRepetidas()],
            ['Grupos a revisar a mano',      count($detector->sospechosos())],
            ['Sin clasificar',              $sinClasificar],
            ['Productos en el maestro',     count($maestro)],
            ['Marcas fuera del diccionario', count($normalizador->marcas()->desconocidas())],
            ['Bloqueados por IGV PENDIENTE',  $this->contarPendientes($maestro)],
            ['A revisar por un contador',     $this->contarRevisiones($maestro)],
        ]);

        $this->info('Maestro escrito en ' . Rutas::maestro());

        return self::SUCCESS;
    }

    /**
     * Siembra el generador con los codigos ya emitidos: los del maestro
     * anterior y los que ya viven en productos. Un codigo interno impreso en
     * una etiqueta no puede cambiar de producto entre corridas.
     */
    /**
     * Junta las filas que comparten un EAN verificable.
     *
     * Dos fuentes escriben el mismo producto distinto —"Gaseosa COCA COLA
     * Botella 1.5L" y "COCA COLA Gaseosa 1.5 L"— y la clave por marca +
     * descripcion + presentacion no las une. El EAN si: es el identificador
     * que le puso el fabricante, y dos filas con el mismo GTIN valido son el
     * mismo producto. Es la evidencia mas fuerte de identidad que tenemos.
     *
     * Se conserva la fila con mas datos completos y se cuenta la fusion. Esto
     * es DUPLICADO_CONFIRMADO: no se toca nada de lo que el detector marco
     * como sospechoso, que sigue yendo a revision a mano.
     *
     * @param  array<int,array<string,string>>  $maestro
     * @return array<int,array<string,string>>
     */
    private function fusionarPorEan(array $maestro): array
    {
        $porEan = [];
        $sinEan = [];
        $fusionadas = 0;

        foreach ($maestro as $fila) {
            $ean = trim((string) ($fila['codigo_barras'] ?? ''));

            if ($ean === '') {
                $sinEan[] = $fila;

                continue;
            }

            if (! isset($porEan[$ean])) {
                $porEan[$ean] = $fila;

                continue;
            }

            $fusionadas++;
            $porEan[$ean] = $this->masCompleta($porEan[$ean], $fila);
        }

        if ($fusionadas > 0) {
            $this->line(sprintf(
                '  <fg=gray>%d filas fusionadas por compartir EAN verificable (duplicado confirmado)</>',
                $fusionadas,
            ));
        }

        return array_merge(array_values($porEan), $sinEan);
    }

    /**
     * De dos filas del mismo producto, la que trae mas celdas llenas.
     *
     * No se mezclan campos de una y otra: se elige una fila entera. Combinar
     * la marca de una con la presentacion de la otra produciria un producto
     * que ninguna fuente publico.
     *
     * @param  array<string,string>  $a
     * @param  array<string,string>  $b
     * @return array<string,string>
     */
    private function masCompleta(array $a, array $b): array
    {
        $llenas = fn (array $f) => count(array_filter($f, fn ($v) => trim((string) $v) !== ''));

        return $llenas($b) > $llenas($a) ? $b : $a;
    }

    /**
     * Recorta el maestro a un primer lote manejable.
     *
     * El RAW guarda todo lo que se observo —es el registro de la consulta y no
     * se toca— pero el catalogo de un minimarket no es el de un supermercado.
     * La fuente trae 1.969 shampoos y 1.417 utiles de limpieza; una bodega
     * lleva diez de cada uno. Importar la cola larga entera llena el POS de
     * referencias que nadie va a comprarle a un distribuidor.
     *
     * El criterio de corte es explicito y no inventa nada:
     *
     *   1. primero las que tienen EAN verificable, porque son las unicas que
     *      el escaner del mostrador va a poder leer;
     *   2. dentro de cada grupo, las que tienen precio de referencia, que son
     *      las que la tienda publica como surtido activo;
     *   3. a igualdad de todo, orden alfabetico, para que dos corridas sobre
     *      el mismo RAW den el mismo lote.
     *
     * @param  array<int,array<string,string>>  $maestro
     * @return array<int,array<string,string>>
     */
    private function primerLote(array $maestro): array
    {
        $tope = max(0, (int) $this->option('por-subcategoria'));

        if ($tope === 0) {
            return $maestro;
        }

        $porSubcategoria = [];

        foreach ($maestro as $fila) {
            $porSubcategoria[($fila['categoria'] ?? '').'|'.($fila['subcategoria'] ?? '')][] = $fila;
        }

        ksort($porSubcategoria);
        $lote = [];
        $recortadas = 0;

        foreach ($porSubcategoria as $grupo) {
            usort($grupo, function (array $a, array $b) {
                $porEan = ($b['codigo_barras'] !== '' ? 1 : 0) <=> ($a['codigo_barras'] !== '' ? 1 : 0);

                if ($porEan !== 0) {
                    return $porEan;
                }

                $porPrecio = (($b['precio_venta'] ?? '') !== '' ? 1 : 0) <=> (($a['precio_venta'] ?? '') !== '' ? 1 : 0);

                return $porPrecio !== 0 ? $porPrecio : strcmp($a['descripcion'] ?? '', $b['descripcion'] ?? '');
            });

            $recortadas += max(0, count($grupo) - $tope);
            $lote = array_merge($lote, array_slice($grupo, 0, $tope));
        }

        $this->line(sprintf(
            '  <fg=gray>primer lote: %d referencias (máx. %d por subcategoría; %d quedaron en el RAW para después)</>',
            count($lote),
            $tope,
            $recortadas,
        ));

        return $lote;
    }

    private function generador(): GeneradorCodigoInterno
    {
        $generador = new GeneradorCodigoInterno();
        $normalizador = Normalizador::porDefecto();

        // La base va PRIMERO y manda: esos productos existen de verdad. Si el
        // maestro recuerda un codigo que en la base ya es de otro producto, la
        // reserva del maestro se rechaza y la fila recibe un codigo nuevo.
        //
        // Sin este orden, el importador tomaria el codigo repetido por una
        // coincidencia y actualizaria un producto que no tiene nada que ver.
        if (! $this->option('sin-bd')) {
            $this->reservarDesdeLaBase($generador, $normalizador);
        }

        if (! is_file(Rutas::maestro())) {
            return $generador;
        }

        $conflictos = [];

        foreach (Csv::leer(Rutas::maestro()) as $fila) {
            $codigo = (string) ($fila['codigo_interno'] ?? '');

            if (! $generador->reservar($normalizador->clave($fila), $codigo)) {
                $conflictos[] = $codigo;
            }
        }

        if ($conflictos !== []) {
            $this->warn(sprintf(
                '%d código(s) del maestro ya pertenecen a otro producto de la base y se van a reemitir: %s',
                count($conflictos),
                implode(', ', array_slice($conflictos, 0, 5)).(count($conflictos) > 5 ? '…' : '')
            ));
        }

        return $generador;
    }

    /**
     * Siembra los codigos que ya viven en productos, con su clave natural.
     *
     * La clave hace falta para distinguir "este codigo es de este mismo
     * producto, ya importado" de "este codigo es de otro producto".
     */
    private function reservarDesdeLaBase(GeneradorCodigoInterno $generador, Normalizador $normalizador): void
    {
        try {
            if (! Schema::hasTable('productos')) {
                return;
            }

            DB::table('productos')
                ->select('codigo_interno', 'marca', 'descripcion', 'presentacion')
                ->orderBy('id')
                ->chunk(1000, function ($productos) use ($generador, $normalizador) {
                    foreach ($productos as $producto) {
                        $generador->reservar(
                            $normalizador->clave((array) $producto),
                            (string) $producto->codigo_interno
                        );
                    }
                });
        } catch (\Throwable $e) {
            // Sin base disponible el comando sigue sirviendo: solo pierde la
            // proteccion contra chocar con un codigo ya insertado.
            $this->warn('No se pudo leer productos ('.$e->getMessage().'). Use --sin-bd para omitir este aviso.');
        }
    }

    private function resumenErrores(array $errores): void
    {
        if ($errores === []) {
            return;
        }

        $this->newLine();
        $this->warn(count($errores) . ' fila(s) rechazada(s):');

        foreach (array_slice($errores, 0, 10) as $error) {
            $this->line("  {$error['archivo']}:{$error['linea']}  {$error['problemas']}");
        }

        if (count($errores) > 10) {
            $this->line('  ... el detalle completo va a procesados/errores_raw.csv');
        }
    }

    private function escribirErrores(array $errores): void
    {
        Csv::escribir(
            Rutas::procesados('errores_raw.csv'),
            ['archivo', 'linea', 'descripcion', 'problemas'],
            $errores
        );
    }

    private function escribirDuplicados(DetectorDuplicados $detector): void
    {
        $filas = [];

        foreach ($detector->repetidos() as $clave => $grupo) {
            foreach ($grupo as $posicion => $fila) {
                $filas[] = [
                    'clave'         => $clave,
                    'rol'           => $posicion === 0 ? 'CONSERVADA' : 'REPETIDA',
                    'fuente'        => $fila['fuente'] ?? '',
                    'marca'         => $fila['marca'] ?? '',
                    'descripcion'   => $fila['descripcion'] ?? '',
                    'presentacion'  => $fila['presentacion'] ?? '',
                    'url_fuente'    => $fila['url_fuente'] ?? '',
                ];
            }
        }

        Csv::escribir(
            Rutas::procesados('duplicados.csv'),
            ['clave', 'rol', 'fuente', 'marca', 'descripcion', 'presentacion', 'url_fuente'],
            $filas
        );
    }

    /**
     * Misma marca y presentacion con descripciones distintas: candidatos que
     * el texto no puede resolver solo. Se reportan, no se fusionan.
     */
    private function escribirSospechosos(DetectorDuplicados $detector): void
    {
        $filas = [];

        foreach ($detector->sospechosos() as $clave => $grupo) {
            foreach ($grupo as $fila) {
                $filas[] = [
                    'clave_debil'  => $clave,
                    'fuente'       => $fila['fuente'] ?? '',
                    'marca'        => $fila['marca'] ?? '',
                    'descripcion'  => $fila['descripcion'] ?? '',
                    'presentacion' => $fila['presentacion'] ?? '',
                    'url_fuente'   => $fila['url_fuente'] ?? '',
                ];
            }
        }

        Csv::escribir(
            Rutas::procesados('posibles_duplicados.csv'),
            ['clave_debil', 'fuente', 'marca', 'descripcion', 'presentacion', 'url_fuente'],
            $filas
        );
    }

    /**
     * Devuelve al maestro los datos que alguien cargo a mano.
     *
     * El normalizador reescribe catalogo_maestro.csv entero en cada corrida.
     * Sin esto, la primera vez que llegan filas nuevas del RAW se borran los
     * precios, los codigos de barras y los stocks minimos que un humano
     * completo para poder importar — y el catalogo vuelve a ser inimportable.
     *
     * Son exactamente las columnas que el pipeline deja vacias a proposito
     * (EsquemaMaestro::PENDIENTES_DE_DATO): el catalogo no las sabe, asi que
     * tampoco tiene derecho a pisarlas.
     *
     * @param  array<int,array<string,string>>  $maestro
     * @return array<int,array<string,string>>
     */
    private function conservarLoCargadoAMano(array $maestro, Normalizador $normalizador): array
    {
        if (! is_file(Rutas::maestro())) {
            return $maestro;
        }

        $previo = [];

        // Se indexa por clave natural y no por codigo interno: un codigo puede
        // reemitirse si resulta que ya era de otro producto de la base, y en
        // ese caso los precios cargados a mano se perderian.
        foreach (Csv::leer(Rutas::maestro()) as $fila) {
            $previo[$normalizador->clave($fila)] = $fila;
        }

        $conservados = 0;

        foreach ($maestro as $i => $fila) {
            $anterior = $previo[$normalizador->clave($fila)] ?? null;

            if ($anterior === null) {
                continue;
            }

            foreach (EsquemaMaestro::PENDIENTES_DE_DATO as $columna) {
                $valor = trim((string) ($anterior[$columna] ?? ''));

                // Solo se repone lo que la corrida nueva deja vacio: si el
                // normalizador ahora sabe el dato, manda el dato nuevo.
                if ($valor !== '' && trim((string) ($maestro[$i][$columna] ?? '')) === '') {
                    $maestro[$i][$columna] = $valor;
                    $conservados++;
                }
            }
        }

        if ($conservados > 0) {
            $this->line("  Se conservaron <info>{$conservados}</info> dato(s) cargados a mano en el maestro anterior.");
        }

        return $maestro;
    }

    /**
     * Lo que no es surtido de minimarket, con el motivo.
     *
     * Nunca se descarta en silencio: si el filtro se pasa de listo, el reporte
     * es donde se ve.
     */
    private function escribirDescartes(array $descartes): void
    {
        Csv::escribir(
            Rutas::procesados('fuera_de_alcance.csv'),
            ['archivo', 'linea', 'aptitud', 'categoria', 'subcategoria', 'descripcion', 'motivo'],
            $descartes
        );
    }

    /** Filas cuya afectacion de IGV no se pudo resolver: no se importan. */
    private function contarPendientes(array $maestro): int
    {
        return count(array_filter(
            $maestro,
            fn (array $fila) => ! \App\Sunat\Tributos::esAfectacionValida($fila['operacion'] ?? '')
        ));
    }

    /** Filas con alguna duda tributaria (IGV, ISC o IVAP). No bloquean. */
    private function contarRevisiones(array $maestro): int
    {
        return count(array_filter(
            $maestro,
            fn (array $fila) => ($fila['requiere_revision_tributaria'] ?? '0') === '1'
        ));
    }

    /**
     * Marcas que aparecieron en el RAW y el diccionario todavia no cubre.
     *
     * No bloquean nada: el producto entra igual con la marca capitalizada.
     * Pero mientras no esten en marcas.csv su grafia depende de como la
     * escribio la fuente, y dos fuentes pueden escribirla distinto.
     */
    private function escribirMarcasDesconocidas(array $claves): void
    {
        Csv::escribir(
            Rutas::procesados('marcas_desconocidas.csv'),
            ['clave'],
            array_map(fn (string $clave) => ['clave' => $clave], $claves)
        );
    }

    /** Que le falta a cada fila para poder insertarse en productos. */
    private function escribirPendientes(array $maestro): void
    {
        $filas = [];

        foreach ($maestro as $fila) {
            $faltantes = EsquemaMaestro::faltantesParaImportar($fila);

            if ($faltantes === []) {
                continue;
            }

            $filas[] = [
                'codigo_interno'    => $fila['codigo_interno'],
                'descripcion'       => $fila['descripcion'],
                'categoria'         => $fila['categoria'],
                'subcategoria'      => $fila['subcategoria'],
                'producto_tipo'     => $fila['producto_tipo'],
                'operacion'         => $fila['operacion'],
                'afecto_isc'        => $fila['afecto_isc'],
                'afecto_ivap'       => $fila['afecto_ivap'],
                'requiere_revision' => $fila['requiere_revision_tributaria'],
                'faltantes'         => implode('; ', $faltantes),
            ];
        }

        Csv::escribir(
            Rutas::procesados('pendientes_para_importar.csv'),
            ['codigo_interno', 'descripcion', 'categoria', 'subcategoria', 'producto_tipo',
                'operacion', 'afecto_isc', 'afecto_ivap', 'requiere_revision', 'faltantes'],
            $filas
        );
    }
}
