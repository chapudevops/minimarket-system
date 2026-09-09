<?php

namespace App\Console\Commands;

use App\Catalogo\Csv;
use App\Catalogo\Imagenes\EstadoFoto;
use App\Catalogo\Imagenes\RegistroFuentesImagen;
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
                            {--fuentes : Solo muestra el registro de fuentes y sale}';

    protected $description = 'Estado del enriquecimiento de imágenes del catálogo';

    public function handle(): int
    {
        $registro = RegistroFuentesImagen::desdeArchivo();

        $this->mostrarFuentes($registro);

        if ($this->option('fuentes')) {
            return self::SUCCESS;
        }

        $this->mostrarCobertura();

        $candidatos = $this->candidatos((int) $this->option('limite'));

        $this->newLine();
        $this->line('  Productos que entrarían al enriquecimiento: <info>'.number_format($candidatos->count()).'</info>');

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
        $this->warn('  Hay fuentes habilitadas en el registro, pero ninguna tiene todavía una');
        $this->warn('  implementación de FuenteImagen. Nada que consultar.');

        return self::SUCCESS;
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
