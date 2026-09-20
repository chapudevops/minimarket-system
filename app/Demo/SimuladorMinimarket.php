<?php

namespace App\Demo;

use App\Estados\EstadoDevolucion;
use App\Estados\EstadoDocumento;
use App\Estados\EstadoPago;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use App\Sunat\Monto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Genera un escenario DEMO de operacion de un minimarket.
 *
 * TODO lo que produce es SIMULADO. No son cifras comerciales reales ni
 * observadas: sirven para demostrar el sistema, no como referencia de negocio.
 *
 * El circuito respeta el orden real del negocio y no inserta atajos:
 *
 *     proveedor -> compra -> stock -> venta -> caja -> gasto -> utilidad
 *
 * Nada se vende antes de haberse comprado, el stock nunca baja de cero y los
 * agotados lo estan porque se vendieron, no porque nacieran vacios.
 *
 * Es reproducible: con la misma semilla produce el mismo escenario, para poder
 * grabar demos y escribir tests contra numeros estables.
 */
class SimuladorMinimarket
{
    /** Cuantos productos del catalogo entran en la operacion. */
    private const PRODUCTOS_OPERATIVOS = 200;

    private array $stock = [];        // producto_id => unidades disponibles
    private array $productos = [];    // producto_id => datos + rotacion
    private array $resumen = [];
    private ?array $ruleta = null;        // sorteo ponderado de productos
    private ?array $cacheClientes = null; // ids de cliente por tipo

    public function __construct(
        private readonly int $semilla,
        private readonly int $dias = 75,
        private readonly bool $escribir = false,
    ) {
    }

    /** @return array<string,mixed> */
    public function ejecutar(): array
    {
        // Toda la aleatoriedad sale de aqui: misma semilla, mismo escenario.
        mt_srand($this->semilla);

        $this->elegirProductos();

        if (! $this->escribir) {
            return $this->planificar();
        }

        return DB::transaction(function () {
            $this->limpiar();
            $this->asignarCostos();
            $proveedores = $this->proveedores();
            $clientes = $this->clientes();

            // El periodo termina HOY, no ayer: el dashboard busca ventas del
            // dia y una caja abierta, y sin el dia en curso la demo abre con
            // las tarjetas de "hoy" en cero.
            $inicio = now()->subDays($this->dias - 1)->startOfDay();

            $this->comprasIniciales($proveedores, $inicio);
            $this->operar($inicio, $clientes, $proveedores);

            return $this->resumen;
        });
    }

    /** Lo que generaria, sin tocar la base. */
    private function planificar(): array
    {
        $porRotacion = array_count_values(array_column($this->productos, 'rotacion'));
        $porCategoria = [];
        foreach ($this->productos as $p) {
            $porCategoria[$p['categoria']] = ($porCategoria[$p['categoria']] ?? 0) + 1;
        }
        arsort($porCategoria);

        return [
            'modo' => 'SIMULACION',
            'semilla' => $this->semilla,
            'dias' => $this->dias,
            'desde' => now()->subDays($this->dias - 1)->toDateString(),
            'hasta' => now()->toDateString(),
            'productos_operativos' => count($this->productos),
            'por_rotacion' => $porRotacion,
            'por_categoria' => $porCategoria,
            'proveedores' => count(ProveedoresDemo::CATALOGO),
            'compras_iniciales_estimadas' => count(ProveedoresDemo::CATALOGO),
            'ventas_estimadas' => (int) round($this->dias * 26),
        ];
    }

    /**
     * Muestra representativa del catalogo.
     *
     * No participan los ~790 productos: un minimarket que abre no mueve todo
     * su surtido, y un dashboard donde todo vende lo mismo no enseña nada.
     * Se reparte por categoria de forma proporcional y solo entran productos
     * con la afectacion de IGV resuelta y con precio.
     */
    private function elegirProductos(): void
    {
        $candidatos = DB::table('productos as p')
            ->join('subcategorias as s', 's.id', '=', 'p.subcategoria_id')
            ->join('categorias as c', 'c.id', '=', 's.categoria_id')
            ->where('p.estado', 1)
            ->where('p.precio_venta', '>', 0)
            // Mismo filtro tributario que usa el POS: lo no clasificado no se
            // puede cobrar, asi que tampoco puede entrar en la simulacion.
            ->whereIn('p.operacion', \App\Sunat\Tributos::AFECTACIONES)
            ->orderBy('p.id')
            ->get(['p.id', 'p.descripcion', 'p.precio_venta', 'c.nombre as categoria', 's.nombre as subcategoria']);

        $porCategoria = $candidatos->groupBy('categoria');
        $total = $candidatos->count();
        $elegidos = collect();

        foreach ($porCategoria as $categoria => $items) {
            // Cuota proporcional al peso de la categoria en el catalogo.
            $cuota = max(4, (int) round(self::PRODUCTOS_OPERATIVOS * $items->count() / $total));
            // Orden estable por id: la seleccion no depende del random.
            $elegidos = $elegidos->merge($items->sortBy('id')->take($cuota));
        }

        $i = 0;
        foreach ($elegidos as $p) {
            $this->productos[$p->id] = [
                'id' => $p->id,
                'descripcion' => $p->descripcion,
                'categoria' => $p->categoria,
                'subcategoria' => $p->subcategoria,
                'precio_venta' => (float) $p->precio_venta,
                'rotacion' => $this->rotacionDe($i++),
            ];
        }
    }

    /** Reparto 20/35/45 estable, intercalado para no agrupar por categoria. */
    private function rotacionDe(int $indice): string
    {
        $posicion = ($indice * 7) % 100;

        return match (true) {
            $posicion < 20 => PerfilComercial::ALTA,
            $posicion < 55 => PerfilComercial::MEDIA,
            default => PerfilComercial::BAJA,
        };
    }

    /**
     * Fija el costo desde el precio de gondola y el margen de la familia.
     *
     * Se toca `precio_compra`, nunca el precio de venta ni ningun dato del
     * catalogo maestro (EAN, marca, descripcion, tributacion): la simulacion
     * trabaja ENCIMA del catalogo, no lo reescribe.
     */
    private function asignarCostos(): void
    {
        $filas = [];

        foreach ($this->productos as $p) {
            $margen = PerfilComercial::margen($p['id'], $p['categoria'], $p['subcategoria']);
            // El precio de gondola trae IGV; el costo se guarda neto.
            $ventaNeta = Monto::desagregarIgv($p['precio_venta'])['gravado'];
            $costo = round($ventaNeta * (1 - $margen), 2);

            $filas[] = ['id' => $p['id'], 'costo' => max(0.10, $costo)];
            $this->productos[$p['id']]['costo'] = max(0.10, $costo);
            $this->productos[$p['id']]['margen'] = $margen;
            $this->productos[$p['id']]['venta_neta'] = $ventaNeta;
        }

        foreach (array_chunk($filas, 200) as $lote) {
            $casos = '';
            foreach ($lote as $f) {
                $casos .= sprintf(' WHEN %d THEN %s', $f['id'], $f['costo']);
            }
            DB::update(sprintf(
                'UPDATE productos SET precio_compra = CASE id%s END WHERE id IN (%s)',
                $casos,
                implode(',', array_column($lote, 'id'))
            ));
        }

        $this->fijarStockMinimo();

        $this->resumen['productos_operativos'] = count($this->productos);
        $this->resumen['margen_medio'] = round(
            array_sum(array_column($this->productos, 'margen')) / max(1, count($this->productos)) * 100,
            1
        );
    }

    /**
     * Define que productos se gestionan por inventario.
     *
     * `stock_minimo = 0` significa "catalogo sin gestion de stock": el panel
     * de alertas lo ignora a proposito, porque un articulo que nunca recibio
     * mercaderia no es un quiebre de stock.
     *
     * Sin esto los ~590 productos que no entran en la simulacion quedaban en
     * cero con un minimo heredado y el dashboard avisaba de 697 quiebres
     * inventados, tapando los de verdad.
     */
    private function fijarStockMinimo(): void
    {
        $operativos = array_keys($this->productos);

        // Fuera de la operacion: sin gestion de inventario.
        DB::table('productos')->whereNotIn('id', $operativos)->update(['stock_minimo' => 0]);

        // Dentro: el minimo va con la rotacion, que es lo que marca cuando
        // conviene reponer.
        foreach ([PerfilComercial::ALTA => 25, PerfilComercial::MEDIA => 10, PerfilComercial::BAJA => 4] as $rotacion => $minimo) {
            $ids = array_keys(array_filter($this->productos, fn ($p) => $p['rotacion'] === $rotacion));

            if ($ids !== []) {
                DB::table('productos')->whereIn('id', $ids)->update(['stock_minimo' => $minimo]);
            }
        }
    }

    /** Borra solo lo que genera esta simulacion. El catalogo no se toca. */
    private function limpiar(): void
    {
        DB::table('nota_credito_detalles')->delete();
        DB::table('notas_credito')->delete();
        DB::table('venta_cuotas')->delete();
        DB::table('venta_detalles')->delete();
        DB::table('ventas')->delete();
        DB::table('compra_detalles')->delete();
        DB::table('compras')->delete();
        DB::table('gastos')->delete();
        DB::table('apertura_cajas')->delete();
        DB::table('producto_almacen')->update(['stock' => 0]);
        DB::table('series')->update(['correlativo' => 0]);
    }

    /** @return list<int> ids de proveedor por familia */
    private function proveedores(): array
    {
        $ids = [];

        foreach (ProveedoresDemo::CATALOGO as $p) {
            DB::table('proveedores')->updateOrInsert(
                ['numero_documento' => $p['ruc']],
                [
                    'tipo_documento' => 'RUC',
                    'nombre_razon_social' => $p['nombre'],
                    'direccion' => $p['direccion'],
                    'telefono' => $p['telefono'],
                    'departamento' => 'Lima', 'provincia' => 'Lima', 'distrito' => 'Lima',
                    'estado' => 1,
                ]
            );
            $ids[$p['ruc']] = DB::table('proveedores')->where('numero_documento', $p['ruc'])->value('id');
        }

        $this->resumen['proveedores'] = count($ids);

        return $ids;
    }

    /** @return list<int> */
    private function clientes(): array
    {
        foreach (ClientesDemo::CATALOGO as $c) {
            DB::table('clientes')->updateOrInsert(
                ['numero_documento' => $c['doc']],
                [
                    'tipo_documento' => $c['tipo'],
                    'nombre_razon_social' => $c['nombre'],
                    'direccion' => $c['direccion'],
                    'departamento' => 'Lima', 'provincia' => 'Lima', 'distrito' => 'Lima',
                    'estado' => 1,
                ]
            );
        }

        $this->resumen['clientes'] = count(ClientesDemo::CATALOGO);

        return DB::table('clientes')->where('estado', 1)->pluck('id')->all();
    }

    /* ==================== COMPRAS ==================== */

    /**
     * Compra de apertura: una factura por proveedor, con las unidades que
     * cada producto necesita para aguantar el periodo segun su rotacion.
     *
     * El stock NO se inserta a mano: entra por el detalle de compra, igual que
     * en la operacion real. Si no hubiera compra, no habria stock que vender.
     */
    private function comprasIniciales(array $proveedores, Carbon $inicio): void
    {
        $porProveedor = [];

        foreach ($this->productos as $p) {
            $ruc = ProveedoresDemo::paraCategoria($p['categoria']);
            $porProveedor[$ruc][] = $p;
        }

        $numero = 0;
        $invertido = 0.0;
        $unidades = 0;

        foreach ($porProveedor as $ruc => $items) {
            // Compra un dia antes del arranque: nada se vende sin stock previo.
            $fecha = $inicio->copy()->subDay();
            $lineas = [];

            foreach ($items as $p) {
                $cantidad = $this->compraInicialDe($p['rotacion']);
                $lineas[] = [
                    'producto_id' => $p['id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $p['costo'],
                    'total' => round($cantidad * $p['costo'], 2),
                ];
                $this->stock[$p['id']] = ($this->stock[$p['id']] ?? 0) + $cantidad;
                $unidades += $cantidad;
            }

            $invertido += $this->registrarCompra($proveedores[$ruc], $fecha, $lineas, ++$numero);
        }

        $this->guardarStock();

        $this->resumen['compras'] = $numero;
        $this->resumen['inversion_mercaderia'] = round($invertido, 2);
        $this->resumen['unidades_compradas'] = $unidades;
    }

    /** Unidades de una entrada de mercaderia segun rotacion. */
    private function compraInicialDe(string $rotacion): int
    {
        return PerfilComercial::unidadesDeCompra($rotacion);
    }

    /** Inserta la compra y su detalle. Devuelve el total con IGV. */
    private function registrarCompra(int $proveedorId, Carbon $fecha, array $lineas, int $numero): float
    {
        // El costo se guarda neto y el comprobante suma IGV encima, igual que
        // hace CompraController.
        $neto = round(array_sum(array_column($lineas, 'total')), 2);
        $importes = Monto::agregarIgv($neto);

        $compraId = DB::table('compras')->insertGetId([
            'tipo_comprobante' => 'FACTURA',
            'serie' => 'F001',
            'numero' => 1000 + $numero,
            'fecha_emision' => $fecha->toDateString(),
            'fecha_vencimiento' => $fecha->copy()->addDays(30)->toDateString(),
            'proveedor_id' => $proveedorId,
            'almacen_id' => $this->almacenId(),
            'tipo_cambio' => 1,
            'tipo_pago' => 'CREDITO',
            'subtotal' => $importes['gravado'],
            'igv' => $importes['igv'],
            'total' => $importes['total'],
            'estado' => EstadoDocumento::REGISTRADA,
            'usuario_id' => $this->usuarioId(),
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);

        DB::table('compra_detalles')->insert(array_map(
            fn (array $l) => $l + ['compra_id' => $compraId, 'created_at' => $fecha, 'updated_at' => $fecha],
            $lineas
        ));

        return $importes['total'];
    }

    /** Vuelca el stock acumulado en memoria a producto_almacen. */
    private function guardarStock(): void
    {
        $almacen = $this->almacenId();
        $ahora = now();
        $filas = [];

        foreach ($this->stock as $productoId => $unidades) {
            $filas[] = [
                'producto_id' => $productoId,
                'almacen_id' => $almacen,
                'stock' => max(0, $unidades),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        foreach (array_chunk($filas, 400) as $lote) {
            DB::table('producto_almacen')->upsert($lote, ['producto_id', 'almacen_id'], ['stock', 'updated_at']);
        }
    }

    /* ==================== OPERACION DIARIA ==================== */

    /**
     * Recorre el periodo dia a dia: abre caja, vende, gasta, repone y cierra.
     *
     * El orden importa y es el que impone la realidad: no se puede vender lo
     * que no se compro, ni cerrar una caja que no se abrio.
     */
    private function operar(Carbon $inicio, array $clientes, array $proveedores): void
    {
        $correlativo = ['BOLETA' => 0, 'FACTURA' => 0];
        $ventas = 0; $ingreso = 0.0; $unidades = 0;
        $pagos = []; $ventasParaNota = [];

        for ($d = 0; $d < $this->dias; $d++) {
            $dia = $inicio->copy()->addDays($d);

            // Al llegar a la mitad del periodo se repone lo que se agoto, que
            // es lo que haria cualquier tienda en vez de quedarse sin vender.
            if ($d > 0 && $d % 25 === 0) {
                $this->reponer($proveedores, $dia);
            }

            $apertura = $this->abrirCaja($dia);
            $efectivoDelDia = 0.0;

            foreach ($this->ticketsDelDia($dia) as $hora) {
                $venta = $this->venderTicket($dia, $hora, $clientes, $correlativo);

                if ($venta === null) {
                    continue;
                }

                $ventas++;
                $ingreso += $venta['total'];
                $unidades += $venta['unidades'];
                $pagos[$venta['forma_pago']] = ($pagos[$venta['forma_pago']] ?? 0) + 1;

                if ($venta['forma_pago'] === 'EFECTIVO') {
                    $efectivoDelDia += $venta['total'];
                }

                if ($venta['unidades'] > 1) {
                    $ventasParaNota[] = $venta['id'];
                }
            }

            $this->gastosDelDia($dia);
            $this->cerrarCaja($apertura, $dia, $efectivoDelDia);
        }

        $this->guardarStock();
        $this->alinearCorrelativos($correlativo);

        $this->resumen['ventas'] = $ventas;
        $this->resumen['ingresos'] = round($ingreso, 2);
        $this->resumen['unidades_vendidas'] = $unidades;
        $this->resumen['ticket_promedio'] = $ventas > 0 ? round($ingreso / $ventas, 2) : 0.0;
        $this->resumen['pagos'] = $pagos;

        $this->devoluciones($ventasParaNota);
    }

    /**
     * Horas de los tickets del dia. El volumen varia: un martes no es un
     * sabado, y un dashboard con el mismo numero de ventas cada dia no se
     * parece a ninguna tienda.
     */
    private function ticketsDelDia(Carbon $dia): array
    {
        $base = $dia->isWeekend() ? mt_rand(30, 46) : mt_rand(18, 32);

        // El dia en curso va a medias: son las X de la tarde, no las 22h.
        if ($dia->isToday()) {
            $base = (int) max(3, round($base * min(1, now()->hour / 21)));
        }

        $horas = [];
        for ($i = 0; $i < $base; $i++) {
            // Dos crestas: manana antes del trabajo y tarde al volver.
            $horas[] = mt_rand(0, 100) < 40 ? mt_rand(8, 12) : mt_rand(17, 21);
        }
        sort($horas);

        return $horas;
    }

    /**
     * Un ticket. Devuelve null si no quedaba stock de nada: preferible perder
     * la venta a inventar existencias.
     */
    private function venderTicket(Carbon $dia, int $hora, array $clientes, array &$correlativo): ?array
    {
        $cuantos = $this->tamanoDelTicket();
        $elegidos = [];

        for ($i = 0; $i < $cuantos; $i++) {
            $producto = $this->sortearProducto();

            if ($producto === null || isset($elegidos[$producto['id']])) {
                continue;
            }

            $disponible = $this->stock[$producto['id']] ?? 0;
            if ($disponible < 1) {
                continue;
            }

            $cantidad = max(1, min($disponible, PerfilComercial::unidadesPorLinea($producto['rotacion'])));

            $elegidos[$producto['id']] = ['producto' => $producto, 'cantidad' => $cantidad];
        }

        if ($elegidos === []) {
            return null;
        }

        $emision = $dia->copy()->setTime($hora, mt_rand(0, 59));
        $bruto = 0.0; $lineas = []; $unidades = 0;

        foreach ($elegidos as $e) {
            $p = $e['producto'];
            $importe = round($e['cantidad'] * $p['precio_venta'], 2);
            $bruto += $importe;
            $unidades += $e['cantidad'];

            $lineas[] = [
                'producto_id' => $p['id'],
                'cantidad' => $e['cantidad'],
                'precio_unitario' => $p['precio_venta'],
                'total' => $importe,
                'almacen_id' => $this->almacenId(),
            ];

            // El stock baja aqui, nunca por debajo de cero.
            $this->stock[$p['id']] = max(0, ($this->stock[$p['id']] ?? 0) - $e['cantidad']);
        }

        // Los ticket con RUC salen factura; el resto boleta, como en la calle.
        $tipo = mt_rand(1, 100) <= 8 ? 'FACTURA' : 'BOLETA';
        $correlativo[$tipo]++;

        // El precio de gondola ya trae IGV: se desagrega, no se suma.
        $importes = Monto::desagregarIgv(round($bruto, 2));
        $forma = $this->formaDePago();
        $pagado = $forma === 'EFECTIVO' ? ceil($importes['total'] / 10) * 10 : $importes['total'];

        $ventaId = DB::table('ventas')->insertGetId([
            'tipo_comprobante' => $tipo,
            'serie' => $tipo === 'BOLETA' ? 'B001' : 'F001',
            'numero' => $correlativo[$tipo],
            'fecha_emision' => $emision,
            'cliente_id' => $this->clienteDelTicket($clientes, $tipo),
            'tipo_venta' => 'CONTADO',
            'forma_pago' => $forma,
            'subtotal' => $importes['gravado'],
            'igv' => $importes['igv'],
            'total' => $importes['total'],
            'pagado' => $pagado,
            'cambio' => round($pagado - $importes['total'], 2),
            'detraccion' => 0,
            'caja_id' => $this->cajaId(),
            'usuario_id' => $this->usuarioId(),
            'estado' => EstadoVenta::APROBADA,
            'estado_pago' => EstadoPago::PAGADA,
            'estado_devolucion' => EstadoDevolucion::SIN_DEVOLUCION,
            // DEMO: nada se envia a SUNAT.
            'estado_sunat' => EstadoSunat::NO_ENVIADO,
            'created_at' => $emision,
            'updated_at' => $emision,
        ]);

        DB::table('venta_detalles')->insert(array_map(
            fn (array $l) => $l + ['venta_id' => $ventaId, 'created_at' => $emision, 'updated_at' => $emision],
            $lineas
        ));

        return [
            'id' => $ventaId,
            'total' => $importes['total'],
            'unidades' => $unidades,
            'forma_pago' => $forma,
        ];
    }

    /**
     * Cuantos productos distintos lleva un ticket.
     *
     * Un minimarket vive de la compra de impulso y del "me falta una cosa":
     * casi la mitad de los tickets son un solo articulo. Las compras grandes
     * existen pero son la excepcion, no la norma.
     */
    private function tamanoDelTicket(): int
    {
        $r = mt_rand(1, 100);

        return match (true) {
            $r <= 46 => 1,
            $r <= 74 => 2,
            $r <= 92 => mt_rand(3, 4),
            default => mt_rand(5, 8),
        };
    }

    /**
     * Sorteo ponderado: el 20% de alta rotacion se lleva el grueso.
     *
     * La ruleta es propiedad de la INSTANCIA, no una estatica del metodo. Con
     * `static` se conservaba entre simulaciones del mismo proceso: la segunda
     * corrida heredaba ids de la primera, no los encontraba en su catalogo y
     * no vendia absolutamente nada.
     */
    private function sortearProducto(): ?array
    {
        if ($this->ruleta === null) {
            $this->ruleta = [];
            foreach ($this->productos as $p) {
                $peso = PerfilComercial::PESO_ROTACION[$p['rotacion']];
                for ($i = 0; $i < $peso; $i++) {
                    $this->ruleta[] = $p['id'];
                }
            }
        }

        if ($this->ruleta === []) {
            return null;
        }

        return $this->productos[$this->ruleta[array_rand($this->ruleta)]] ?? null;
    }

    private function formaDePago(): string
    {
        $r = mt_rand(1, 100);

        return match (true) {
            $r <= 55 => 'EFECTIVO',
            $r <= 80 => 'YAPE',
            $r <= 94 => 'TARJETA',
            default => 'TRANSFERENCIA',
        };
    }

    /** Una factura necesita RUC; la boleta casi siempre va a CLIENTE VARIOS. */
    private function clienteDelTicket(array $clientes, string $tipo): int
    {
        // Mismo motivo que la ruleta: cacheado por instancia, no por proceso.
        $this->cacheClientes ??= [
            'generico' => DB::table('clientes')->where('numero_documento', ClientesDemo::DOC_GENERICO)->value('id'),
            'ruc' => DB::table('clientes')->where('tipo_documento', 'RUC')->pluck('id')->all(),
            'dni' => DB::table('clientes')
                ->where('tipo_documento', 'DNI')
                ->where('numero_documento', '!=', ClientesDemo::DOC_GENERICO)
                ->pluck('id')->all(),
        ];

        ['generico' => $generico, 'ruc' => $conRuc, 'dni' => $identificados] = $this->cacheClientes;

        if ($tipo === 'FACTURA' && $conRuc !== []) {
            return $conRuc[array_rand($conRuc)];
        }

        // 1 de cada 6 boletas va a nombre de alguien; el resto, publico.
        if ($identificados !== [] && mt_rand(1, 6) === 1) {
            return $identificados[array_rand($identificados)];
        }

        return $generico ?? $clientes[0];
    }

    /* ==================== CAJA, GASTOS, REPOSICION ==================== */

    private function abrirCaja(Carbon $dia): int
    {
        return DB::table('apertura_cajas')->insertGetId([
            'fecha_apertura' => $dia->toDateString(),
            'hora_apertura' => '08:00:00',
            'caja_id' => $this->cajaId(),
            'responsable_id' => $this->usuarioId(),
            'monto_inicial' => 200.00,
            'estado' => 'ABIERTA',
            'created_at' => $dia->copy()->setTime(8, 0),
            'updated_at' => $dia->copy()->setTime(8, 0),
        ]);
    }

    /**
     * Cierra cuadrado: lo declarado es exactamente el fondo inicial mas el
     * efectivo cobrado menos los gastos pagados en efectivo. Esta version no
     * simula descuadres; hacerlo seria otra decision, no un descuido.
     *
     * El dia de hoy queda ABIERTA, que es lo que el dashboard espera encontrar.
     */
    private function cerrarCaja(int $aperturaId, Carbon $dia, float $efectivo): void
    {
        if ($dia->isToday()) {
            return;
        }

        $gastos = (float) DB::table('gastos')->whereDate('fecha_emision', $dia->toDateString())->sum('monto');

        DB::table('apertura_cajas')->where('id', $aperturaId)->update([
            'estado' => 'CERRADA',
            'monto_cierre' => round(200.00 + $efectivo - $gastos, 2),
            'fecha_cierre' => $dia->toDateString(),
            'hora_cierre' => '22:00:00',
            'responsable_cierre_id' => $this->usuarioId(),
            'updated_at' => $dia->copy()->setTime(22, 0),
        ]);
    }

    /**
     * Gastos operativos. Los fijos caen el dia 1 de cada mes y los menudos
     * aparecen de vez en cuando, que es como se comportan de verdad.
     */
    private function gastosDelDia(Carbon $dia): void
    {
        $filas = [];

        if ($dia->day === 1) {
            foreach ([
                ['Alquiler del local', 'ALQUILER', 1800.00],
                ['Recibo de luz', 'SERVICIOS', 420.00],
                ['Recibo de agua', 'SERVICIOS', 145.00],
                ['Internet y telefonia', 'SERVICIOS', 189.00],
            ] as [$motivo, $cuenta, $monto]) {
                $filas[] = [$motivo, $cuenta, $monto];
            }
        }

        // Menudeo: uno de cada cuatro dias.
        if (mt_rand(1, 4) === 1) {
            $menudos = [
                ['Bolsas y empaques', 'INSUMOS', mt_rand(35, 90)],
                ['Utiles de limpieza', 'MANTENIMIENTO', mt_rand(25, 70)],
                ['Movilidad y reparto', 'LOGISTICA', mt_rand(30, 80)],
                ['Mantenimiento de equipos', 'MANTENIMIENTO', mt_rand(60, 180)],
            ];
            $filas[] = $menudos[array_rand($menudos)];
        }

        foreach ($filas as [$motivo, $cuenta, $monto]) {
            DB::table('gastos')->insert([
                'fecha_emision' => $dia->toDateString(),
                'motivo' => $motivo,
                'cuenta' => $cuenta,
                'monto' => $monto,
                'detalle' => 'Gasto operativo simulado (DEMO)',
                'usuario_id' => $this->usuarioId(),
                'created_at' => $dia,
                'updated_at' => $dia,
            ]);
        }
    }

    /**
     * Reposicion a mitad de periodo: se vuelve a comprar lo que bajo del
     * umbral. Sin esto los productos de alta rotacion se agotarian pronto y
     * la segunda mitad del periodo no tendria nada que vender.
     */
    private function reponer(array $proveedores, Carbon $dia): void
    {
        $porProveedor = [];

        foreach ($this->productos as $p) {
            $quedan = $this->stock[$p['id']] ?? 0;
            $umbral = $p['rotacion'] === PerfilComercial::ALTA ? 60 : 15;

            if ($quedan > $umbral) {
                continue;
            }

            // Uno de cada cuatro pedidos no llega: el proveedor no tenia, se
            // olvido, o no daba el dinero. Sin esto ningun producto llega
            // nunca a cero y el panel de agotados queda siempre vacio, que es
            // justo lo contrario de lo que pasa en una tienda.
            if (mt_rand(1, 4) === 1) {
                continue;
            }

            $ruc = ProveedoresDemo::paraCategoria($p['categoria']);
            $cantidad = $this->compraInicialDe($p['rotacion']);

            $porProveedor[$ruc][] = [
                'producto_id' => $p['id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $p['costo'],
                'total' => round($cantidad * $p['costo'], 2),
            ];
            $this->stock[$p['id']] = $quedan + $cantidad;
            $this->resumen['unidades_compradas'] = ($this->resumen['unidades_compradas'] ?? 0) + $cantidad;
        }

        foreach ($porProveedor as $ruc => $lineas) {
            $numero = ($this->resumen['compras'] ?? 0) + 1;
            $this->resumen['inversion_mercaderia'] = round(
                ($this->resumen['inversion_mercaderia'] ?? 0)
                + $this->registrarCompra($proveedores[$ruc], $dia, $lineas, $numero), 2
            );
            $this->resumen['compras'] = $numero;
        }

        $this->guardarStock();
    }

    /* ==================== DEVOLUCIONES ==================== */

    /**
     * Pocas devoluciones, siempre por el flujo formal de nota de credito:
     * se comprueba lo vendido, vuelve el stock y la venta refleja que tuvo
     * devolucion. Nunca se toca la venta a mano para fingirla.
     */
    private function devoluciones(array $candidatas): void
    {
        if ($candidatas === []) {
            return;
        }

        // ~1.5% de las operaciones elegibles.
        $cuantas = max(1, (int) round(count($candidatas) * 0.015));
        // Orden estable antes de barajar, para no depender de en que orden
        // fueron llegando las ventas.
        sort($candidatas);
        shuffle($candidatas);
        $elegidas = array_slice($candidatas, 0, $cuantas);

        $serie = DB::table('series')->where('tipo_comprobante', 'NOTA_CREDITO')->value('serie') ?? 'FC01';
        $numero = 0;
        $devueltas = 0;

        $motivos = [
            ['DEVOLUCION', 'Producto observado por el cliente'],
            ['DEVOLUCION', 'Producto proximo a vencer'],
            ['DESCUENTO', 'Descuento posterior acordado'],
        ];

        foreach ($elegidas as $ventaId) {
            $venta = DB::table('ventas')->where('id', $ventaId)->first();
            $detalles = DB::table('venta_detalles')->where('venta_id', $ventaId)->get();

            if (! $venta || $detalles->isEmpty()) {
                continue;
            }

            // Se devuelve UNA linea y nunca mas de lo que llevaba.
            //
            // Se elige con mt_rand y no con Collection::random(), que por
            // dentro usa random_int: eso no depende de la semilla y rompia la
            // reproducibilidad del escenario (mismas ventas, importes que
            // bailaban unos soles entre corridas).
            $linea = $detalles[mt_rand(0, $detalles->count() - 1)];
            $cantidad = max(1, (int) floor($linea->cantidad / 2));

            [$tipoNota, $motivo] = $motivos[array_rand($motivos)];
            $emision = Carbon::parse($venta->fecha_emision)->addDays(mt_rand(1, 4));

            if ($emision->isFuture()) {
                $emision = now()->subHours(2);
            }

            // El detalle de la nota guarda precio NETO y el comprobante suma
            // IGV encima: es al reves que la venta.
            $neto = round($cantidad * Monto::desagregarIgv((float) $linea->precio_unitario)['gravado'], 2);
            $importes = Monto::agregarIgv($neto);

            $notaId = DB::table('notas_credito')->insertGetId([
                'tipo_comprobante' => 'NOTA_CREDITO',
                'serie' => $serie,
                'numero' => ++$numero,
                'fecha_emision' => $emision,
                'cliente_id' => $venta->cliente_id,
                'venta_id' => $venta->id,
                'motivo' => $motivo,
                'tipo_nota' => $tipoNota,
                'subtotal' => $importes['gravado'],
                'igv' => $importes['igv'],
                'total' => $importes['total'],
                'detraccion' => 0,
                'observaciones' => 'Devolucion simulada (DEMO)',
                'caja_id' => $this->cajaId(),
                'usuario_id' => $this->usuarioId(),
                'estado' => EstadoDocumento::REGISTRADA,
                'estado_sunat' => EstadoSunat::NO_ENVIADO,
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            DB::table('nota_credito_detalles')->insert([
                'nota_credito_id' => $notaId,
                'producto_id' => $linea->producto_id,
                'cantidad' => $cantidad,
                'precio_unitario' => Monto::desagregarIgv((float) $linea->precio_unitario)['gravado'],
                'total' => $neto,
                'almacen_id' => $linea->almacen_id,
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            // La mercaderia vuelve al almacen.
            DB::table('producto_almacen')
                ->where('producto_id', $linea->producto_id)
                ->where('almacen_id', $linea->almacen_id)
                ->increment('stock', $cantidad);

            $vendidas = (int) DB::table('venta_detalles')->where('venta_id', $ventaId)->sum('cantidad');
            DB::table('ventas')->where('id', $ventaId)->update([
                'estado_devolucion' => EstadoDevolucion::desdeUnidades($vendidas, $cantidad),
            ]);

            $devueltas++;
        }

        $this->resumen['notas_credito'] = $devueltas;
    }

    /** Deja las series en el ultimo numero emitido. */
    private function alinearCorrelativos(array $correlativo): void
    {
        foreach ($correlativo as $tipo => $ultimo) {
            DB::table('series')->where('tipo_comprobante', $tipo)->update(['correlativo' => $ultimo]);
        }
        DB::table('series')->where('tipo_comprobante', 'NOTA_CREDITO')
            ->update(['correlativo' => $this->resumen['notas_credito'] ?? 0]);
    }

    private function almacenId(): int
    {
        return (int) DB::table('almacenes')->orderBy('id')->value('id');
    }

    private function cajaId(): int
    {
        return (int) DB::table('cajas')->orderBy('id')->value('id');
    }

    private function usuarioId(): int
    {
        return (int) DB::table('users')->orderBy('id')->value('id');
    }
}
