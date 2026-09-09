<?php

namespace App\Catalogo\Imagenes;

use App\Models\Producto;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Le busca imagen a los productos que tienen EAN.
 *
 * Tres propiedades que importan mas que la cobertura:
 *
 *   Incremental — un producto ya resuelto no se vuelve a consultar. Sin esto,
 *   cada corrida sobre miles de productos repetiria el trabajo entero y le
 *   pegaria a la fuente sin motivo.
 *
 *   Reanudable — el estado vive en la fila del producto, no en memoria. Si el
 *   proceso se corta, la siguiente corrida sigue donde iba.
 *
 *   Aislado — un error en una imagen no detiene el lote. Se anota y se sigue.
 *
 * Y una regla que no se negocia: una foto propia no se pisa nunca.
 */
class EnriquecedorImagenes
{
    private const REINTENTOS = 2;

    public function __construct(
        private readonly FuenteImagen $fuente,
        private readonly VerificadorCorrespondencia $verificador,
        private readonly RegistroFuentesImagen $registro,
        private readonly bool $simular = false,
        private readonly bool $pausar = true,
        /**
         * Microsegundos entre consultas. No es cosmetico: cada fuente publica
         * su limite y superarlo puede costar el acceso. Open Food Facts admite
         * 15 por minuto, no las 50 que darian 1,2 s.
         */
        private readonly int $pausaMicrosegundos = 1_200_000,
        /** Sin descargador, la imagen solo se referencia por su URL. */
        private readonly ?DescargadorImagen $descargador = null,
    ) {}

    /**
     * @param  iterable<Producto>  $productos
     */
    public function enriquecer(iterable $productos, ?callable $avance = null): ResultadoEnriquecimiento
    {
        $resultado = new ResultadoEnriquecimiento();
        $nombreFuente = $this->fuente->nombre();

        // Se comprueba una sola vez, no por producto: si la fuente no esta
        // habilitada no se le pide nada y se dice por que.
        if (! $this->registro->sePuedeConsultar($nombreFuente)) {
            throw new FuenteImagenNoAutorizada(
                "La fuente {$nombreFuente} no está habilitada en diccionarios/fuentes_imagen.csv "
                .'(robots.txt o condiciones de uso). No se consulta.'
            );
        }

        $puedeAlmacenar = $this->registro->sePuedeAlmacenar($nombreFuente);

        // Acceder no alcanza: sin una licencia determinable no hay con que
        // justificar el uso de la foto. Se consulta igual —los datos sirven
        // para saber si la fuente conoce el producto— pero la imagen no se
        // publica sola.
        $licencia = $this->registro->licenciaImagen($nombreFuente);
        $atribucion = $this->registro->atribucion($nombreFuente);

        foreach ($productos as $producto) {
            if ($avance !== null) {
                $avance();
            }

            if (! $this->corresponde($producto)) {
                $resultado->omitidos++;

                continue;
            }

            // Alimentos, cosmetica, limpieza y mascotas viven en sitios
            // distintos de la misma familia.
            if ($this->fuente instanceof FuenteEnrutablePorCategoria) {
                $this->fuente->paraCategoria((string) $producto->categoria()?->nombre);
            }

            try {
                $this->procesar($producto, $resultado, $nombreFuente, $puedeAlmacenar, $licencia, $atribucion);
            } catch (Throwable $e) {
                // Una imagen que falla no puede tumbar el lote entero.
                $resultado->errores++;
                Log::warning('Enriquecimiento de imagen falló', [
                    'producto' => $producto->codigo_interno,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($this->pausar) {
                usleep($this->pausaMicrosegundos);
            }
        }

        return $resultado;
    }

    /** Si a este producto le toca que lo consulten en esta corrida. */
    private function corresponde(Producto $producto): bool
    {
        if (trim((string) $producto->codigo_barras) === '') {
            return false;
        }

        return EstadoFoto::seReintenta(
            $producto->foto_estado,
            $producto->foto_fecha_consulta?->toDateString(),
        );
    }

    private function procesar(
        Producto $producto,
        ResultadoEnriquecimiento $resultado,
        string $nombreFuente,
        bool $puedeAlmacenar,
        ?string $licencia,
        ?array $atribucion,
    ): void {
        $resultado->consultados++;

        $candidata = $this->consultarConReintento((string) $producto->codigo_barras);

        if ($candidata === null) {
            $this->guardar($producto, ['foto_estado' => EstadoFoto::SIN_IMAGEN,
                'foto_fecha_consulta' => now()->toDateString()]);
            $resultado->sinImagen++;
            $resultado->anotar($this->fila($producto, null, EstadoFoto::SIN_IMAGEN, 'la fuente no conoce el código'));

            return;
        }

        $veredicto = $this->verificador->verificar($producto, $candidata);
        $estado = $veredicto['estado'];

        $cambios = [
            'foto_estado' => $estado,
            'foto_fuente' => $nombreFuente,
            'foto_url_origen' => $candidata->url,
            'foto_fecha_consulta' => now()->toDateString(),
            // Se guarda la licencia vigente al momento de obtenerla, no se
            // deriva del registro al mostrarla: si la fuente cambia sus
            // condiciones, esta foto sigue amparada por lo que decia hoy.
            'foto_licencia' => $licencia,
            'foto_atribucion' => $atribucion['texto'] ?? null,
        ];

        // Procedencia sin licencia determinable: la imagen no se publica.
        if ($licencia === null) {
            $estado = EstadoFoto::REVISAR;
            $cambios['foto_estado'] = $estado;
            $veredicto['motivos'][] = 'la fuente no declara una licencia de imagen determinable';
        }

        // Solo se guarda una copia si las condiciones de la fuente lo permiten.
        // Si solo dejan enlazar, queda la URL de origen y `foto` sigue en NULL.
        if ($estado === EstadoFoto::VERIFICADA && $licencia !== null
            && $puedeAlmacenar && $this->descargador !== null && ! $this->simular) {
            try {
                $cambios['foto'] = $this->descargador->guardar($candidata->url, (int) $producto->id);
            } catch (Throwable $e) {
                // La ficha estaba bien pero el archivo no se pudo traer. Queda
                // para revision en vez de dar por buena una imagen que no esta.
                $estado = EstadoFoto::REVISAR;
                $cambios['foto_estado'] = $estado;
                $veredicto['motivos'][] = 'no se pudo descargar la imagen: '.$e->getMessage();
            }
        }

        $this->guardar($producto, $cambios);

        $estado === EstadoFoto::VERIFICADA ? $resultado->verificadas++ : $resultado->aRevisar++;
        $resultado->anotar($this->fila($producto, $candidata, $estado, implode('; ', $veredicto['motivos'])));
    }

    private function consultarConReintento(string $ean): ?CandidataImagen
    {
        $ultimo = null;

        for ($intento = 0; $intento <= self::REINTENTOS; $intento++) {
            try {
                return $this->fuente->buscarPorEan($ean);
            } catch (Throwable $e) {
                $ultimo = $e;

                if ($this->pausar) {
                    sleep(2 ** $intento);
                }
            }
        }

        throw $ultimo;
    }

    /** @param array<string,mixed> $cambios */
    private function guardar(Producto $producto, array $cambios): void
    {
        if ($this->simular) {
            return;
        }

        // Cinturon y tirantes: corresponde() ya filtro, pero una foto propia
        // no se pisa ni por error de programacion.
        if ($producto->foto_estado === EstadoFoto::PROPIA) {
            return;
        }

        $producto->forceFill($cambios)->save();
    }

    /** @return array<string,string> */
    private function fila(Producto $producto, ?CandidataImagen $candidata, string $estado, string $motivo): array
    {
        return [
            'codigo_interno' => (string) $producto->codigo_interno,
            'codigo_barras' => (string) $producto->codigo_barras,
            'descripcion' => (string) $producto->descripcion,
            'marca' => (string) $producto->marca,
            'presentacion' => (string) $producto->presentacion,
            'fuente' => $candidata?->fuente ?? '',
            'url_origen' => $candidata?->url ?? '',
            'estado' => $estado,
            'motivo' => $motivo,
        ];
    }
}
