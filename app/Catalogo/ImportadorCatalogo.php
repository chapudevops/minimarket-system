<?php

namespace App\Catalogo;

use App\Models\Producto;
use App\Sunat\Tributos;
use Illuminate\Support\Facades\DB;

/**
 * Carga el catalogo maestro en la tabla productos.
 *
 * La clave natural es codigo_interno, asi que volver a correrlo actualiza en
 * vez de duplicar. Esta pensado para miles de filas: lee con un generador y
 * escribe por lotes.
 *
 * --------------------------------------------------------------------------
 * Que manda el catalogo y que manda la tienda
 * --------------------------------------------------------------------------
 *
 * Es la decision central del importador. El escenario a evitar es concreto: la
 * tienda importa 2.000 productos, pasa tres semanas ajustando precios en el
 * POS, vuelve a importar un catalogo actualizado y pierde todo ese trabajo.
 *
 *   CAMPOS_DEL_CATALOGO  se refrescan siempre. Son descripcion, marca,
 *                        presentacion, unidad y los tributos: si el catalogo
 *                        corrigio la afectacion de IGV de un producto, esa
 *                        correccion tiene que llegar.
 *
 *   precio_compra        solo se pisan con --actualizar-precios. Son del
 *   precio_venta         negocio, no del catalogo.
 *
 *   codigo_barras        se asigna si el producto no tenia; NUNCA se borra ni
 *                        se pisa. El EAN que alguien escaneo del producto
 *                        fisico vale mas que la celda vacia del CSV.
 *
 *   stock_minimo         no se tocan nunca en una actualizacion. Dependen de
 *   fecha_vencimiento    la rotacion real, del lote recibido y de decisiones
 *   detraccion           que toma la tienda, no el catalogo.
 *   foto
 *   estado
 *
 * --------------------------------------------------------------------------
 * Que NO hace
 * --------------------------------------------------------------------------
 *
 *  - No crea stock. Un producto importado nace sin filas en producto_almacen y
 *    entra al inventario por una compra. Sembrar stock seria inventarlo.
 *  - No desactiva productos que desaparecieron del CSV. Un catalogo recortado
 *    no es una orden de dar de baja media tienda.
 *  - No inventa nada: lo que viene vacio queda NULL.
 *  - No escribe una entrada de auditoria por producto. Una importacion es UNA
 *    accion humana, no 4.800: el comando deja una sola entrada con el resumen.
 *    El propio trait Auditable lo dice — una bitacora que nadie puede leer no
 *    sirve para nada — y 4.800 lineas de "Creo producto X" la vuelven ilegible.
 */
class ImportadorCatalogo
{
    /** Del catalogo: se refrescan en cada corrida. */
    public const CAMPOS_DEL_CATALOGO = [
        'descripcion',
        'subcategoria_id',
        'marca',
        'presentacion',
        'unidad',
        'operacion',
        'afecto_isc',
        'afecto_ivap',
        'tipo_producto',
    ];

    /** De la tienda: solo se escriben al crear el producto. */
    public const CAMPOS_DE_LA_TIENDA = [
        'precio_compra',
        'precio_venta',
        'stock_minimo',
        'fecha_vencimiento',
        'detraccion',
        'foto',
    ];

    /** Cuantas filas por transaccion. */
    private const LOTE = 500;

    /** @var array<string,true> codigos internos ya vistos en este archivo */
    private array $codigosVistos = [];

    /** @var array<string,string> codigo_barras => codigo_interno, en este archivo */
    private array $barrasVistos = [];

    /** @var array<string,int|null> "CATEGORIA|SUBCATEGORIA" => subcategorias.id */
    private array $subcategorias = [];

    /** Filas cuya categoria no existe en la tabla: quedan sin clasificar. */
    private int $sinClasificar = 0;

    public function __construct(
        private readonly bool $simular = false,
        private readonly bool $actualizarPrecios = false,
    ) {}

    /**
     * @param  iterable<array<string,string>>  $filas
     * @param  callable|null  $avance  se llama con cada fila procesada
     */
    public function importar(iterable $filas, ?callable $avance = null): ResultadoImportacion
    {
        $resultado = new ResultadoImportacion();
        $lote = [];

        foreach ($filas as $fila) {
            $resultado->leidas++;

            $preparada = $this->preparar($fila, $resultado);

            if ($preparada !== null) {
                $lote[] = $preparada;
            }

            if (count($lote) >= self::LOTE) {
                $this->escribirLote($lote, $resultado);
                $lote = [];
            }

            if ($avance !== null) {
                $avance();
            }
        }

        $this->escribirLote($lote, $resultado);

        return $resultado;
    }

    /**
     * Valida una fila y la deja lista para escribir, o la rechaza.
     *
     * @return array<string,mixed>|null
     */
    private function preparar(array $fila, ResultadoImportacion $resultado): ?array
    {
        $codigo = trim((string) ($fila['codigo_interno'] ?? ''));

        // 1. Lo que el catalogo ya sabe que falta: precios sin definir y, sobre
        //    todo, la afectacion de IGV en PENDIENTE.
        $faltantes = EsquemaMaestro::faltantesParaImportar($fila);

        if ($faltantes !== []) {
            $motivo = in_array('operacion', $faltantes, true) && ! Tributos::esAfectacionValida($fila['operacion'] ?? '')
                ? 'Afectación de IGV sin resolver: '.implode(', ', $faltantes)
                : 'Faltan datos: '.implode(', ', $faltantes);

            $resultado->rechazar($fila, $motivo);

            return null;
        }

        // 2. El mismo codigo interno dos veces en el archivo. La segunda
        //    pisaria a la primera sin que nadie se entere.
        if (isset($this->codigosVistos[$codigo])) {
            $resultado->rechazar($fila, "Código interno repetido en el archivo: {$codigo}");

            return null;
        }

        $this->codigosVistos[$codigo] = true;

        $barras = $this->codigoBarras($fila);

        if ($barras !== null) {
            // 3. Dos productos con el mismo EAN dentro del archivo. Sin este
            //    control, el indice unico corta la importacion a la mitad.
            if (isset($this->barrasVistos[$barras])) {
                $resultado->rechazar(
                    $fila,
                    "Código de barras repetido en el archivo: {$barras} ya es de {$this->barrasVistos[$barras]}"
                );

                return null;
            }

            // 4. El EAN ya pertenece a OTRO producto de la base.
            $duenio = Producto::where('codigo_barras', $barras)
                ->where('codigo_interno', '!=', $codigo)
                ->value('codigo_interno');

            if ($duenio !== null) {
                $resultado->rechazar(
                    $fila,
                    "Código de barras ya registrado: {$barras} pertenece a {$duenio}"
                );

                return null;
            }

            $this->barrasVistos[$barras] = $codigo;
        }

        return [
            'codigo_interno' => $codigo,
            'codigo_barras'  => $barras,
            'atributos'      => $this->atributos($fila),
        ];
    }

    /** @param array<int,array<string,mixed>> $lote */
    private function escribirLote(array $lote, ResultadoImportacion $resultado): void
    {
        if ($lote === []) {
            return;
        }

        // Los existentes del lote en UNA consulta. Buscarlos de a uno era el
        // cuello de botella: en un catalogo de miles de referencias son miles
        // de SELECT que no hacen falta.
        $existentes = Producto::whereIn('codigo_interno', array_column($lote, 'codigo_interno'))
            ->get()
            ->keyBy('codigo_interno');

        // En simulacion se calcula el cambio real de cada fila, no solo si el
        // producto existe. Contar como "actualizado" todo lo que ya esta en la
        // base exageraba el impacto —714 en vez de los 39 que de verdad
        // cambiaban— y el dry-run existe justamente para poder confiar en el.
        if ($this->simular) {
            foreach ($lote as $item) {
                $producto = $existentes[$item['codigo_interno']] ?? null;

                if ($producto === null) {
                    $resultado->creados++;

                    continue;
                }

                $cambios = $this->cambiosParaExistente($producto, $item);

                if ($cambios === []) {
                    $resultado->sinCambios++;

                    continue;
                }

                if (array_key_exists('codigo_barras', $cambios)) {
                    $resultado->codigosBarrasAsignados++;
                }

                $resultado->actualizados++;
            }

            return;
        }

        // Una transaccion por lote y no una sola para todo el archivo: con
        // miles de filas, un unico bloque mantendria la tabla bloqueada
        // mientras el POS intenta vender. Si un lote falla, los anteriores
        // quedan importados y basta con volver a correr el comando, que es
        // idempotente.
        // withoutEvents apaga la auditoria fila por fila. Producto no tiene
        // otros observadores, asi que no se pierde nada mas; el rastro de la
        // importacion lo deja el comando en una sola entrada.
        Producto::withoutEvents(function () use ($lote, $existentes, $resultado) {
            DB::transaction(function () use ($lote, $existentes, $resultado) {
                foreach ($lote as $item) {
                    $this->guardar($item, $existentes[$item['codigo_interno']] ?? null, $resultado);
                }
            });
        });
    }

    private function guardar(array $item, ?Producto $producto, ResultadoImportacion $resultado): void
    {
        if ($producto === null) {
            Producto::create(array_merge($item['atributos'], [
                'codigo_interno' => $item['codigo_interno'],
                'codigo_barras'  => $item['codigo_barras'],
                'estado'         => 1,
            ]));

            $resultado->creados++;

            return;
        }

        $cambios = $this->cambiosParaExistente($producto, $item);

        if ($cambios === []) {
            $resultado->sinCambios++;

            return;
        }

        if (array_key_exists('codigo_barras', $cambios)) {
            $resultado->codigosBarrasAsignados++;
        }

        $producto->update($cambios);
        $resultado->actualizados++;
    }

    /**
     * Que se le cambia a un producto que ya existe.
     *
     * @return array<string,mixed>
     */
    private function cambiosParaExistente(Producto $producto, array $item): array
    {
        $cambios = [];

        foreach (self::CAMPOS_DEL_CATALOGO as $campo) {
            $nuevo = $item['atributos'][$campo] ?? null;

            // Comparacion laxa a proposito: los booleanos vienen del CSV como
            // '1'/'0' y del modelo como true/false.
            if ($nuevo != $producto->{$campo}) {
                $cambios[$campo] = $nuevo;
            }
        }

        if ($this->actualizarPrecios) {
            foreach (['precio_compra', 'precio_venta'] as $campo) {
                if ((float) $item['atributos'][$campo] !== (float) $producto->{$campo}) {
                    $cambios[$campo] = $item['atributos'][$campo];
                }
            }
        }

        // El codigo de barras solo se completa, nunca se pisa ni se borra: si
        // alguien escaneo el producto fisico, ese EAN vale mas que la celda
        // vacia del CSV.
        if ($item['codigo_barras'] !== null && $producto->codigo_barras === null) {
            $cambios['codigo_barras'] = $item['codigo_barras'];
        }

        return $cambios;
    }

    /** @return array<string,mixed> */
    private function atributos(array $fila): array
    {
        return [
            'descripcion'       => trim((string) $fila['descripcion']),
            'subcategoria_id'   => $this->subcategoriaId($fila),
            'marca'             => $this->oNulo($fila['marca'] ?? null),
            'presentacion'      => $this->oNulo($fila['presentacion'] ?? null),
            'unidad'            => strtoupper(trim((string) $fila['unidad'])),
            'operacion'         => strtoupper(trim((string) $fila['operacion'])),
            'afecto_isc'        => $this->booleano($fila['afecto_isc'] ?? null),
            'afecto_ivap'       => $this->booleano($fila['afecto_ivap'] ?? null),
            'tipo_producto'     => strtoupper(trim((string) $fila['tipo_producto'])),
            // Celda vacia -> 0.00, que es el default de la columna y significa
            // "sin precio todavia". No se deriva del precio de gondola: la
            // vitrina de un supermercado no es el costo de un minimarket.
            'precio_compra'     => round((float) ($fila['precio_compra'] ?: 0), 2),
            'precio_venta'      => round((float) ($fila['precio_venta'] ?: 0), 2),
            'stock_minimo'      => (int) ($fila['stock_minimo'] ?? 0),
            'fecha_vencimiento' => $this->oNulo($fila['fecha_vencimiento'] ?? null),
            'detraccion'        => $this->booleano($fila['detraccion'] ?? null),
            'foto'              => $this->oNulo($fila['foto'] ?? null),
        ];
    }

    /**
     * Traduce categoria/subcategoria del CSV al id de la tabla.
     *
     * Se resuelve una vez por combinacion y queda cacheado: un catalogo de
     * miles de filas usa cuarenta y pico de subcategorias, no miles.
     *
     * Si la combinacion no existe en la tabla devuelve null, o sea "sin
     * clasificar". No se crea la subcategoria al vuelo: la taxonomia es una
     * decision de negocio y un CSV con una categoria mal escrita no deberia
     * poder inventarla.
     */
    private function subcategoriaId(array $fila): ?int
    {
        $categoria = strtoupper(trim((string) ($fila['categoria'] ?? '')));
        $subcategoria = strtoupper(trim((string) ($fila['subcategoria'] ?? '')));

        if ($categoria === '' || $subcategoria === '') {
            $this->sinClasificar++;

            return null;
        }

        $clave = $categoria.'|'.$subcategoria;

        if (! array_key_exists($clave, $this->subcategorias)) {
            $this->subcategorias[$clave] = DB::table('subcategorias')
                ->join('categorias', 'categorias.id', '=', 'subcategorias.categoria_id')
                ->whereRaw('UPPER(categorias.nombre) = ?', [$categoria])
                ->whereRaw('UPPER(subcategorias.nombre) = ?', [$subcategoria])
                ->value('subcategorias.id');
        }

        if ($this->subcategorias[$clave] === null) {
            $this->sinClasificar++;
        }

        return $this->subcategorias[$clave];
    }

    public function sinClasificar(): int
    {
        return $this->sinClasificar;
    }

    /**
     * Celda vacia = sin dato = NULL.
     *
     * Nunca la cadena vacia: '' en codigo_barras choca contra el indice unico
     * en cuanto haya un segundo producto sin EAN.
     */
    private function oNulo(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function codigoBarras(array $fila): ?string
    {
        return $this->oNulo($fila['codigo_barras'] ?? null);
    }

    private function booleano(?string $valor): bool
    {
        return in_array(strtolower(trim((string) $valor)), ['1', 'true', 'si', 'sí'], true);
    }
}
