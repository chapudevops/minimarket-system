<?php

namespace App\Console\Commands;

use App\Catalogo\Csv;
use App\Catalogo\Imagenes\DescargadorImagen;
use App\Catalogo\Imagenes\EnriquecedorImagenes;
use App\Catalogo\Imagenes\EstadoFoto;
use App\Catalogo\Imagenes\Fuentes\ClienteOpenFoodFacts;
use App\Catalogo\Imagenes\Fuentes\FuenteOpenFoodFacts;
use App\Catalogo\Imagenes\RegistroFuentesImagen;
use App\Catalogo\Imagenes\VerificadorCorrespondencia;
use App\Catalogo\Rutas;
use App\Models\Producto;
use Illuminate\Console\Command;

/**
 * Enriquecimiento visual del catalogo.
 *
 * Hoy no hay ninguna fuente habilitada para consulta automatizada, asi que el
 * comando muestra el estado del registro y que productos entrarian. No inventa
 * imagenes ni consulta sitios que no lo permiten.
 *
 * Cuando exista una fuente autorizada se implementa FuenteImagen y se habilita
 * en diccionarios/fuentes_imagen.csv; el resto del pipeline ya esta hecho y
 * probado (EnriquecedorImagenes).
 */
class CatalogoImagenes extends Command
{
    protected $signature = 'catalogo:imagenes
                            {--limite=0 : Cuantos productos considerar (0 = todos)}
                            {--fuentes : Solo muestra el registro de fuentes y sale}
                            {--sondear=0 : Mide cobertura con N productos y no escribe nada}
                            {--ejecutar : Consulta la fuente y guarda las imagenes}';

    protected $description = 'Estado del enriquecimiento de imágenes del catálogo';

    public function handle(): int
    {
        $registro = RegistroFuentesImagen::desdeArchivo();

        $this->mostrarFuentes($registro);

        if ($this->option('fuentes')) {
            return self::SUCCESS;
        }

        $this->mostrarCobertura();

        $sondeo = (int) $this->option('sondear');
        $candidatos = $sondeo > 0
            ? $this->muestraRepartida($sondeo)
            : $this->candidatos((int) $this->option('limite'));

        $this->newLine();
        $this->line('  Productos que entrarían al enriquecimiento: <info>'.number_format($candidatos->count()).'</info>');

        if ($sondeo > 0 || $this->option('ejecutar')) {
            return $this->enriquecer($candidatos, $registro, simular: $sondeo > 0);
        }

        $ruta = Rutas::procesados('imagenes_candidatos.csv');
        Csv::escribir($ruta, ['codigo_interno', 'codigo_barras', 'marca', 'descripcion', 'presentacion', 'foto_estado'],
            $candidatos->map(fn (Producto $p) => [
                'codigo_interno' => $p->codigo_interno,
                'codigo_barras' => (string) $p->codigo_barras,
                'marca' => (string) $p->marca,
                'descripcion' => (string) $p->descripcion,
                'presentacion' => (string) $p->presentacion,
                'foto_estado' => (string) $p->foto_estado,
            ])->all());

        $this->line('  Listado en <info>'.$ruta.'</info>');

        if ($registro->consultables() === []) {
            $this->newLine();
            $this->warn('  Ninguna fuente está habilitada para consulta automatizada: no se consultó nada.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('  Para medir cobertura antes de escalar:  <info>php artisan catalogo:imagenes --sondear=60</info>');
        $this->line('  Para enriquecer de verdad:              <info>php artisan catalogo:imagenes --ejecutar</info>');

        return self::SUCCESS;
    }

    /**
     * Corre el enriquecimiento sobre los productos dados.
     *
     * En modo sondeo no escribe: solo mide cuanto del catalogo conoce la
     * fuente. Es la puerta de decision antes de invertir 50 minutos en las 740.
     */
    private function enriquecer($productos, RegistroFuentesImagen $registro, bool $simular): int
    {
        $cliente = new ClienteOpenFoodFacts();
        $fuente = new FuenteOpenFoodFacts($cliente);

        if (! $registro->sePuedeConsultar($fuente->nombre())) {
            $this->error('  '.$fuente->nombre().' no está habilitada en el registro de fuentes.');

            return self::FAILURE;
        }

        $this->newLine();

        if ($simular) {
            $this->line('  <options=bold>Sondeo de cobertura</> — solo diagnóstico');
            $this->line('  <fg=gray>No modifica productos, no guarda imágenes, no cambia foto_estado</>');
        } else {
            $this->line('  <options=bold>Enriqueciendo</> — esto SÍ escribe en la base');
        }
        $this->line('  Agente: <info>'.ClienteOpenFoodFacts::AGENTE.'</info>');
        $this->line('  Pausa:  <info>'.round(ClienteOpenFoodFacts::PAUSA / 1_000_000, 1).' s</info> entre consultas (límite de OFF: 15/min)');

        $minutos = ceil($productos->count() * ClienteOpenFoodFacts::PAUSA / 1_000_000 / 60);
        $this->line('  Tardará aproximadamente <info>'.$minutos.'</info> minuto(s). Se puede cortar y retomar.');
        $this->newLine();

        $barra = $this->output->createProgressBar($productos->count());
        $barra->setFormat('  %current%/%max% [%bar%] %message%');
        $barra->setMessage('');
        $barra->start();

        $enriquecedor = new EnriquecedorImagenes(
            $fuente,
            VerificadorCorrespondencia::porDefecto(),
            $registro,
            simular: $simular,
            pausar: true,
            pausaMicrosegundos: ClienteOpenFoodFacts::PAUSA,
            descargador: $simular ? null : new DescargadorImagen(storage_path('app/public')),
        );

        $resultado = $enriquecedor->enriquecer($productos, function () use ($barra) {
            $barra->advance();
        });

        $barra->finish();
        $this->newLine(2);

        $this->resumenEnriquecimiento($resultado, $cliente, $simular);

        return self::SUCCESS;
    }

    private function resumenEnriquecimiento($resultado, ClienteOpenFoodFacts $cliente, bool $simular): void
    {
        $consultados = max(1, $resultado->consultados);

        $this->table(['Concepto', 'Cantidad'], [
            ['Consultados', number_format($resultado->consultados)],
            ['Imágenes verificadas', number_format($resultado->verificadas)],
            ['A revisar', number_format($resultado->aRevisar)],
            ['Sin imagen en la fuente', number_format($resultado->sinImagen)],
            ['Omitidos (ya resueltos o sin EAN)', number_format($resultado->omitidos)],
            ['Errores', number_format($resultado->errores)],
            ['Peticiones HTTP', number_format($cliente->peticiones())],
            ['Reintentos', number_format($cliente->reintentos())],
            ['Cobertura del sondeo', round($resultado->verificadas / $consultados * 100, 1).' %'],
        ]);

        // El sondeo escribe su propio archivo: si compartiera el del
        // enriquecimiento real, un diagnostico pisaria el reporte de la ultima
        // corrida que si escribio en la base.
        $ruta = Rutas::procesados($simular ? 'imagenes_sondeo.csv' : 'imagenes_resultado.csv');
        Csv::escribir($ruta, ['codigo_interno', 'codigo_barras', 'descripcion', 'marca', 'presentacion',
            'fuente', 'url_origen', 'estado', 'motivo'], $resultado->detalle);
        $this->line('  Detalle en <info>'.$ruta.'</info>');

        if (! $simular) {
            return;
        }

        $this->newLine();

        // La puerta de decision del plan: por debajo de este umbral, seguir
        // consultando las 740 es tiempo tirado y conviene ir a fotos propias.
        if ($resultado->verificadas / $consultados < 0.15) {
            $this->warn('  Cobertura por debajo del 15 %: Open Food Facts conoce poco de este catálogo.');
            $this->warn('  Conviene ir por fotos propias antes que enriquecer las 740.');

            return;
        }

        $this->info('  Cobertura suficiente. Se puede escalar con --ejecutar.');
    }

    /** Muestra repartida entre categorías, para que el sondeo sea representativo. */
    private function muestraRepartida(int $cuantos)
    {
        $candidatos = $this->candidatos(0);
        $porCategoria = $candidatos->groupBy(fn (Producto $p) => $p->categoria()?->nombre ?? 'SIN CATEGORIA');
        $cupo = max(1, intdiv($cuantos, max(1, $porCategoria->count())));

        return $porCategoria
            ->flatMap(fn ($grupo) => $grupo->take($cupo))
            ->take($cuantos)
            ->values();
    }

    private function mostrarFuentes(RegistroFuentesImagen $registro): void
    {
        $this->newLine();
        $this->line('  <options=bold>Registro de fuentes de imagen</>');

        $this->table(
            ['Fuente', 'Estado', 'Licencia', 'robots', 'Almacenar', 'Enlazar'],
            array_map(fn (array $f) => [
                $f['fuente'],
                $f['estado'],
                mb_strimwidth($f['licencia'], 0, 34, '…'),
                $f['robots_permite'],
                $f['permite_almacenar'],
                $f['permite_enlazar'],
            ], $registro->todas())
        );
    }

    private function mostrarCobertura(): void
    {
        $total = Producto::count();
        $conEan = Producto::whereNotNull('codigo_barras')->count();
        $conFoto = Producto::whereNotNull('foto')
            ->orWhere(fn ($q) => $q->whereNotNull('foto_url_origen')->where('foto_estado', EstadoFoto::VERIFICADA))
            ->count();

        $filas = [
            ['Productos', number_format($total)],
            ['Con EAN', number_format($conEan)],
            ['Con imagen', number_format($conFoto)],
            ['Cobertura de imagen', $total > 0 ? round($conFoto / $total * 100, 1).' %' : '-'],
        ];

        foreach (EstadoFoto::TODOS as $estado) {
            $filas[] = ["Estado {$estado}", number_format(Producto::where('foto_estado', $estado)->count())];
        }

        $this->table(['Concepto', 'Cantidad'], $filas);
    }

    /** @return \Illuminate\Support\Collection<int,Producto> */
    private function candidatos(int $limite)
    {
        $consulta = Producto::whereNotNull('codigo_barras')
            ->whereNotIn('foto_estado', [EstadoFoto::PROPIA, EstadoFoto::VERIFICADA])
            ->orderBy('codigo_interno');

        if ($limite > 0) {
            $consulta->limit($limite);
        }

        return $consulta->get();
    }
}
