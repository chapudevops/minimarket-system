<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Llena los modulos que MovimientoSeeder no toca (cotizaciones, notas de venta,
 * notas de credito/debito, guias de remision, traslados, combos, flota, ventas
 * al credito y cierres de caja) para poder demostrar el sistema completo.
 *
 * La regla de este seeder es que nada sea data suelta: cada nota de credito
 * apunta a una venta que existe y repite su cliente, su producto y su precio;
 * cada guia sale de una venta real; cada traslado mueve productos que de verdad
 * tienen stock en el almacen de origen. Si se generara al azar, cualquier
 * pantalla de detalle mostraria incoherencias apenas se abre.
 *
 * Es re-ejecutable: limpia lo suyo antes de sembrar.
 */
class DemoOperativoSeeder extends Seeder
{
    private const IGV = 0.18;

    /** Ubigeos reales de Lima usados en las guias. */
    private const UBIGEO_TIENDA = '150114';   // La Molina
    private const UBIGEO_DESTINO = [
        '150103' => 'Av. Nicolas Ayllon 3100, Ate',
        '150132' => 'Av. Tupac Amaru 1450, San Martin de Porres',
        '150122' => 'Av. Separadora Industrial 2200, San Juan de Miraflores',
        '150140' => 'Av. Aviacion 1500, San Borja',
        '150101' => 'Jr. Union 234, Cercado de Lima',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $ctx = $this->contexto();

            if ($ctx === null) {
                return;
            }

            $this->limpiar();
            $this->flota();
            $this->ventasDelMes($ctx);
            $this->combos($ctx);
            $this->cotizaciones($ctx);
            $this->notasVenta($ctx);
            $this->notasCredito($ctx);
            $this->notasDebito($ctx);
            $this->guiasRemision($ctx);
            $this->traslados($ctx);
            $this->ventasCredito($ctx);
            $this->cierresCaja($ctx);
            $this->correlativos();
            $this->auditoria();
        });
    }

    /**
     * Ids que necesita todo el seeder. Si falta alguno no tiene sentido seguir:
     * mejor avisar que sembrar filas huerfanas.
     *
     * @return array{almacenes:list<int>,cajaId:int,admin:int,vendedor:int,almacenero:int,clientes:list<int>,productos:Collection}|null
     */
    private function contexto(): ?array
    {
        $almacenes = DB::table('almacenes')->orderBy('id')->pluck('id')->all();
        $cajaId = DB::table('cajas')->orderBy('id')->value('id');
        $clientes = DB::table('clientes')->where('estado', 1)->pluck('id')->all();

        // Productos con precio Y stock: son los unicos que pueden aparecer en
        // un documento sin que el detalle salga en cero.
        $productos = DB::table('productos as p')
            ->join('producto_almacen as pa', 'pa.producto_id', '=', 'p.id')
            ->where('p.estado', 1)
            ->where('p.precio_venta', '>', 0)
            ->where('pa.stock', '>', 10)
            ->where('pa.almacen_id', $almacenes[0] ?? 0)
            ->inRandomOrder()
            ->limit(300)
            ->get(['p.id', 'p.descripcion', 'p.unidad', 'p.precio_venta', 'p.precio_compra']);

        if ($almacenes === [] || $cajaId === null || $clientes === [] || $productos->isEmpty()) {
            $this->command?->warn('  Falta catalogo, clientes, cajas o almacenes: corre DemoCompletoSeeder.');

            return null;
        }

        $rol = fn (string $nombre) => DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.nombre', $nombre)
            ->value('u.id');

        $admin = $rol('Administrador') ?? DB::table('users')->orderBy('id')->value('id');

        return [
            'almacenes' => $almacenes,
            'cajaId' => (int) $cajaId,
            'admin' => (int) $admin,
            'vendedor' => (int) ($rol('Vendedor') ?? $admin),
            'almacenero' => (int) ($rol('Almacenero') ?? $admin),
            'clientes' => $clientes,
            'productos' => $productos,
        ];
    }


    /**
     * MovimientoSeeder siembra 1 a 5 ventas por dia. Con eso el dashboard, que
     * compara el mes en curso contra si mismo, muestra beneficio negativo: el
     * reabastecimiento del mes pesa mas que nueve dias de ventas flojas.
     *
     * Un minimarket real despacha decenas de tickets al dia, asi que aca se
     * completa el mes hasta un volumen creible. Es idempotente: cuenta lo que
     * ya existe y solo agrega la diferencia, asi que re-ejecutar no infla nada.
     *
     * @param  array{clientes:list<int>,productos:Collection,cajaId:int,admin:int,vendedor:int,almacenes:list<int>}  $ctx
     */
    private function ventasDelMes(array $ctx): void
    {
        $formasPago = ['EFECTIVO', 'EFECTIVO', 'EFECTIVO', 'EFECTIVO', 'YAPE', 'YAPE', 'TARJETA', 'PLIN', 'TRANSFERENCIA'];
        $usuarios = [$ctx['admin'], $ctx['vendedor'], $ctx['vendedor']];

        $correlativo = [
            'BOLETA' => (int) DB::table('ventas')->where('tipo_comprobante', 'BOLETA')->max('numero'),
            'FACTURA' => (int) DB::table('ventas')->where('tipo_comprobante', 'FACTURA')->max('numero'),
        ];

        $dia = now()->startOfMonth();
        $hoy = now();
        $creadas = 0;

        while ($dia->lte($hoy)) {
            // El fin de semana mueve mas caja que un martes.
            //
            // El objetivo sale de la fecha, no de random_int: si se re-sortea
            // en cada corrida, volver a ejecutar el seeder encuentra un
            // objetivo mas alto que lo ya sembrado y sigue agregando ventas.
            $variacion = crc32($dia->toDateString()) % 11;
            $objetivo = $dia->isWeekend() ? 22 + $variacion : 14 + $variacion;

            // El dia en curso va a medias: son las X de la tarde, no las 22h.
            if ($dia->isSameDay($hoy)) {
                $objetivo = (int) max(3, round($objetivo * min(1, $hoy->hour / 21)));
            }

            $existentes = DB::table('ventas')->whereDate('fecha_emision', $dia->toDateString())->count();
            $faltan = $objetivo - $existentes;

            for ($v = 0; $v < $faltan; $v++) {
                $tipo = random_int(1, 100) <= 85 ? 'BOLETA' : 'FACTURA';
                $correlativo[$tipo]++;

                $topeHora = $dia->isSameDay($hoy) ? max(9, $hoy->hour) : 21;
                $emision = $dia->copy()->setTime(random_int(8, $topeHora), random_int(0, 59));

                $items = $ctx['productos']->random(random_int(1, 7));
                $bruto = 0.0;
                $lineas = [];

                foreach ($items as $producto) {
                    $cantidad = random_int(1, 4);
                    $importe = round($cantidad * (float) $producto->precio_venta, 2);
                    $bruto += $importe;

                    $lineas[] = [
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $producto->precio_venta,
                        'total' => $importe,
                        'almacen_id' => $ctx['almacenes'][0],
                    ];
                }

                [$gravado, $igv, $total] = $this->desagregar($bruto);
                $pagado = ceil($total / 10) * 10;

                $ventaId = DB::table('ventas')->insertGetId([
                    'tipo_comprobante' => $tipo,
                    'serie' => $tipo === 'BOLETA' ? 'B001' : 'F001',
                    'numero' => $correlativo[$tipo],
                    'fecha_emision' => $emision,
                    'cliente_id' => $ctx['clientes'][array_rand($ctx['clientes'])],
                    'tipo_venta' => 'CONTADO',
                    'forma_pago' => $formasPago[array_rand($formasPago)],
                    'subtotal' => $gravado,
                    'igv' => $igv,
                    'total' => $total,
                    'pagado' => $pagado,
                    'cambio' => round($pagado - $total, 2),
                    'detraccion' => 0,
                    'caja_id' => $ctx['cajaId'],
                    'usuario_id' => $usuarios[array_rand($usuarios)],
                    'estado' => 'COMPLETADA',
                    'created_at' => $emision,
                    'updated_at' => $emision,
                ]);

                DB::table('venta_detalles')->insert(array_map(
                    fn (array $l) => $l + ['venta_id' => $ventaId, 'created_at' => $emision, 'updated_at' => $emision],
                    $lineas
                ));

                $creadas++;
            }

            $dia->addDay();
        }

        if ($creadas > 0) {
            $this->command?->info(sprintf('  %d ventas adicionales para el mes en curso.', $creadas));
        }
    }

    private function limpiar(): void
    {
        DB::table('cotizacion_detalles')->delete();
        DB::table('cotizaciones')->delete();
        DB::table('nota_venta_detalles')->delete();
        DB::table('notas_venta')->delete();
        DB::table('nota_credito_detalles')->delete();
        DB::table('notas_credito')->delete();
        DB::table('nota_debito_detalles')->delete();
        DB::table('notas_debito')->delete();
        DB::table('guia_remision_detalles')->delete();
        DB::table('guias_remision')->delete();
        DB::table('orden_traslado_detalle')->delete();
        DB::table('ordenes_traslado')->delete();
        DB::table('combo_detalles')->delete();
        DB::table('combos')->delete();
        DB::table('venta_cuotas')->delete();
        DB::table('apertura_cajas')->delete();

        // Las ventas las siembra MovimientoSeeder; aca solo se deshace la
        // marca de credito para poder volver a aplicarla sin duplicar cuotas.
        DB::table('ventas')->where('tipo_venta', 'CREDITO')->update([
            'tipo_venta' => 'CONTADO',
            'estado' => 'COMPLETADA',
        ]);
    }

    private function flota(): void
    {
        $conductores = [
            ['Julio Cesar Paredes Quispe', 'Q45678912', '43219876', '987112233'],
            ['Ricardo Manuel Flores Tello', 'Q11223344', '09887766', '954667788'],
            ['Elena Sofia Chavez Ruiz',     'Q99887711', '72119988', '941556677'],
        ];

        foreach ($conductores as [$nombre, $licencia, $documento, $telefono]) {
            DB::table('conductores')->updateOrInsert(
                ['documento' => $documento],
                ['nombre' => $nombre, 'licencia' => $licencia, 'telefono' => $telefono]
            );
        }

        $vehiculos = [
            ['A4C-812', 'Hyundai', 'H100', 'Blanco'],
            ['B7T-334', 'Toyota',  'Hiace', 'Plata'],
            ['C2M-907', 'JAC',     'X200', 'Azul'],
        ];

        foreach ($vehiculos as [$placa, $marca, $modelo, $color]) {
            DB::table('vehiculos')->updateOrInsert(
                ['placa' => $placa],
                ['marca' => $marca, 'modelo' => $modelo, 'color' => $color]
            );
        }
    }

    /**
     * Combos armados por rubro: el precio del combo es el regular menos un
     * descuento, que es justo lo que la vista compara.
     *
     * @param  array{productos:Collection}  $ctx
     */
    private function combos(array $ctx): void
    {
        $armados = [
            ['Combo Desayuno Familiar', 'Leche, pan y mermelada para arrancar la semana.', 0.12],
            ['Combo Limpieza Total',    'Detergente, lejia y utiles para la limpieza del hogar.', 0.15],
            ['Combo Parrillero',        'Bebidas y snacks para la reunion del fin de semana.', 0.10],
            ['Combo Escolar',           'Snacks y jugos para la lonchera.', 0.08],
        ];

        $disponibles = $ctx['productos']->shuffle();
        $cursor = 0;

        foreach ($armados as [$nombre, $descripcion, $descuento]) {
            $items = $disponibles->slice($cursor, 3);
            $cursor += 3;

            if ($items->count() < 3) {
                break;
            }

            $regular = 0.0;
            $lineas = [];

            foreach ($items as $producto) {
                $cantidad = random_int(1, 2);
                $regular += $cantidad * (float) $producto->precio_venta;
                $lineas[] = ['producto_id' => $producto->id, 'cantidad' => $cantidad];
            }

            $regular = round($regular, 2);

            $comboId = DB::table('combos')->insertGetId([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio_regular' => $regular,
                'precio_combo' => round($regular * (1 - $descuento), 2),
                'estado' => 1,
                'created_at' => now()->subDays(random_int(5, 40)),
                'updated_at' => now(),
            ]);

            DB::table('combo_detalles')->insert(array_map(
                fn (array $l) => $l + ['combo_id' => $comboId, 'created_at' => now(), 'updated_at' => now()],
                $lineas
            ));
        }
    }

    /**
     * @param  array{clientes:list<int>,productos:Collection,cajaId:int,vendedor:int,almacenes:list<int>}  $ctx
     */
    private function cotizaciones(array $ctx): void
    {
        // Mezcla de estados a proposito: la bandeja de cotizaciones sin
        // aprobadas ni rechazadas no muestra para que sirve el modulo.
        $estados = ['APROBADA', 'APROBADA', 'PENDIENTE', 'PENDIENTE', 'PENDIENTE', 'RECHAZADA', 'APROBADA', 'PENDIENTE'];
        $numero = 0;

        foreach ($estados as $estado) {
            $emision = now()->subDays(random_int(1, 45))->setTime(random_int(9, 18), random_int(0, 59));
            $items = $ctx['productos']->random(random_int(2, 6));

            $total = 0.0;
            $lineas = [];

            foreach ($items as $producto) {
                $cantidad = random_int(2, 12);
                $importe = round($cantidad * (float) $producto->precio_venta, 2);
                $total += $importe;

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'total' => $importe,
                    'descuento' => 0,
                    'almacen_id' => $ctx['almacenes'][0],
                ];
            }

            [$gravado, $igv, $total] = $this->desagregar($total);

            $cotizacionId = DB::table('cotizaciones')->insertGetId([
                'serie' => 'C001',
                'numero' => ++$numero,
                'fecha_emision' => $emision,
                'fecha_validez' => $emision->copy()->addDays(15)->toDateString(),
                'cliente_id' => $ctx['clientes'][array_rand($ctx['clientes'])],
                'tipo_moneda' => 'PEN',
                'tipo_cambio' => 1,
                'subtotal' => $gravado,
                'igv' => $igv,
                'total' => $total,
                'descuento' => 0,
                'observaciones' => 'Cotizacion valida por 15 dias. Precios incluyen IGV.',
                'estado' => $estado,
                'caja_id' => $ctx['cajaId'],
                'usuario_id' => $ctx['vendedor'],
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            DB::table('cotizacion_detalles')->insert(array_map(
                fn (array $l) => $l + ['cotizacion_id' => $cotizacionId, 'created_at' => $emision, 'updated_at' => $emision],
                $lineas
            ));
        }
    }

    /**
     * @param  array{clientes:list<int>,productos:Collection,cajaId:int,vendedor:int,almacenes:list<int>}  $ctx
     */
    private function notasVenta(array $ctx): void
    {
        $numero = 0;

        for ($i = 0; $i < 6; $i++) {
            $emision = now()->subDays(random_int(0, 30))->setTime(random_int(9, 20), random_int(0, 59));
            $items = $ctx['productos']->random(random_int(1, 4));

            $total = 0.0;
            $lineas = [];

            foreach ($items as $producto) {
                $cantidad = random_int(1, 6);
                $importe = round($cantidad * (float) $producto->precio_venta, 2);
                $total += $importe;

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta,
                    'total' => $importe,
                    'almacen_id' => $ctx['almacenes'][0],
                ];
            }

            [$gravado, $igv, $total] = $this->desagregar($total);

            $notaId = DB::table('notas_venta')->insertGetId([
                'tipo_comprobante' => 'NOTA_VENTA',
                'serie' => 'NV01',
                'numero' => ++$numero,
                'fecha_emision' => $emision,
                'cliente_id' => $ctx['clientes'][array_rand($ctx['clientes'])],
                'tipo_nota' => 'OTRO',
                'subtotal' => $gravado,
                'igv' => $igv,
                'total' => $total,
                'detraccion' => 0,
                'observaciones' => 'Documento interno, no valido como comprobante de pago.',
                'caja_id' => $ctx['cajaId'],
                'usuario_id' => $ctx['vendedor'],
                'estado' => 'REGISTRADA',
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            DB::table('nota_venta_detalles')->insert(array_map(
                fn (array $l) => $l + ['nota_venta_id' => $notaId, 'created_at' => $emision, 'updated_at' => $emision],
                $lineas
            ));
        }
    }

    /**
     * Cada nota de credito copia el cliente, los productos y los precios de una
     * venta real. Devolver algo que nunca se vendio deja el modulo sin sentido.
     *
     * @param  array{cajaId:int,vendedor:int,almacenes:list<int>}  $ctx
     */
    private function notasCredito(array $ctx): void
    {
        $motivos = [
            ['DEVOLUCION', 'Devolucion parcial: producto observado por el cliente.'],
            ['ANULACION',  'Anulacion de la operacion a solicitud del cliente.'],
            ['DESCUENTO',  'Descuento posterior por acuerdo comercial.'],
            ['DEVOLUCION', 'Devolucion por producto proximo a vencer.'],
            ['ANULACION',  'Error en los datos del comprobante emitido.'],
        ];

        $ventas = $this->ventasConDetalle(count($motivos));
        $numero = 0;

        foreach ($ventas as $i => $venta) {
            [$tipoNota, $motivo] = $motivos[$i];

            $emision = $this->fechaPosterior($venta->fecha_emision);
            $detalles = DB::table('venta_detalles')->where('venta_id', $venta->id)->get();

            // Una devolucion o descuento afecta parte de la venta; una
            // anulacion, todo. Nunca mas que el total original.
            $lineas = $tipoNota === 'ANULACION'
                ? $detalles
                : $detalles->take(max(1, (int) ceil($detalles->count() / 2)));

            $total = 0.0;
            $filas = [];

            foreach ($lineas as $detalle) {
                $cantidad = $tipoNota === 'ANULACION'
                    ? (int) $detalle->cantidad
                    : max(1, (int) floor($detalle->cantidad / 2));

                $importe = round($cantidad * (float) $detalle->precio_unitario, 2);
                $total += $importe;

                $filas[] = [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'total' => $importe,
                    'almacen_id' => $detalle->almacen_id ?? $ctx['almacenes'][0],
                ];
            }

            [$gravado, $igv, $total] = $this->desagregar($total);

            $notaId = DB::table('notas_credito')->insertGetId([
                'tipo_comprobante' => 'NOTA_CREDITO',
                'serie' => 'FC01',
                'numero' => ++$numero,
                'fecha_emision' => $emision,
                'cliente_id' => $venta->cliente_id,
                'venta_id' => $venta->id,
                'motivo' => $motivo,
                'tipo_nota' => $tipoNota,
                'subtotal' => $gravado,
                'igv' => $igv,
                'total' => $total,
                'detraccion' => 0,
                'observaciones' => sprintf('Referencia: %s-%08d', $venta->serie, $venta->numero),
                'caja_id' => $ctx['cajaId'],
                'usuario_id' => $ctx['vendedor'],
                'estado' => 'REGISTRADA',
                'estado_sunat' => $i < 3 ? 'ACEPTADO' : 'PENDIENTE',
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            DB::table('nota_credito_detalles')->insert(array_map(
                fn (array $l) => $l + ['nota_credito_id' => $notaId, 'created_at' => $emision, 'updated_at' => $emision],
                $filas
            ));

            // Una nota de credito por anulacion deja la venta anulada: si la
            // venta siguiera COMPLETADA, los reportes contarian ambas.
            if ($tipoNota === 'ANULACION') {
                DB::table('ventas')->where('id', $venta->id)->update(['estado' => 'ANULADA']);
            }
        }
    }

    /**
     * @param  array{cajaId:int,vendedor:int}  $ctx
     */
    private function notasDebito(array $ctx): void
    {
        $conceptos = [
            ['INTERESES', 'Intereses por pago fuera de plazo',          'Interes moratorio sobre saldo pendiente', 25.00],
            ['GASTOS',    'Gastos de reparto a domicilio',              'Flete por entrega en distrito alejado',   35.00],
            ['INTERESES', 'Intereses por financiamiento en cuotas',     'Interes de financiamiento',               48.50],
            ['GASTOS',    'Gastos administrativos por reemision',       'Reemision de comprobante',                18.00],
        ];

        $ventas = $this->ventasConDetalle(count($conceptos));
        $numero = 0;

        foreach ($ventas as $i => $venta) {
            [$tipoNota, $motivo, $concepto, $monto] = $conceptos[$i];

            $emision = $this->fechaPosterior($venta->fecha_emision);
            [$gravado, $igv, $total] = $this->desagregar($monto);

            $notaId = DB::table('notas_debito')->insertGetId([
                'tipo_comprobante' => 'NOTA_DEBITO',
                'serie' => 'FD01',
                'numero' => ++$numero,
                'fecha_emision' => $emision,
                'cliente_id' => $venta->cliente_id,
                'venta_id' => $venta->id,
                'motivo' => $motivo,
                'tipo_nota' => $tipoNota,
                'subtotal' => $gravado,
                'igv' => $igv,
                'total' => $total,
                'detraccion' => 0,
                'observaciones' => sprintf('Referencia: %s-%08d', $venta->serie, $venta->numero),
                'caja_id' => $ctx['cajaId'],
                'usuario_id' => $ctx['vendedor'],
                'estado' => 'REGISTRADA',
                'estado_sunat' => $i < 2 ? 'ACEPTADO' : 'PENDIENTE',
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            DB::table('nota_debito_detalles')->insert([
                'nota_debito_id' => $notaId,
                'concepto' => $concepto,
                'cantidad' => 1,
                'precio_unitario' => $total,
                'total' => $total,
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);
        }
    }

    /**
     * @param  array{cajaId:int,almacenero:int,almacenes:list<int>}  $ctx
     */
    private function guiasRemision(array $ctx): void
    {
        $conductores = DB::table('conductores')->pluck('id')->all();
        $vehiculos = DB::table('vehiculos')->pluck('id')->all();
        $destinos = self::UBIGEO_DESTINO;

        // 01 venta, 02 compra, 04 traslado entre establecimientos.
        $motivos = ['01', '01', '01', '04', '02', '01'];
        $ventas = $this->ventasConDetalle(count($motivos));
        $numero = 0;

        foreach ($ventas as $i => $venta) {
            $motivo = $motivos[$i];
            $emision = $this->fechaPosterior($venta->fecha_emision);
            $detalles = DB::table('venta_detalles')->where('venta_id', $venta->id)->get();

            $ubigeo = array_rand($destinos);
            $peso = 0.0;
            $filas = [];

            foreach ($detalles as $detalle) {
                $unidad = DB::table('productos')->where('id', $detalle->producto_id)->value('unidad') ?? 'UNIDAD';
                $peso += $detalle->cantidad * 0.75;

                $filas[] = [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'unidad_medida' => $unidad,
                ];
            }

            $guiaId = DB::table('guias_remision')->insertGetId([
                'serie' => 'T001',
                'numero' => ++$numero,
                'fecha_emision' => $emision,
                'fecha_traslado' => $emision->copy()->addDay()->toDateString(),
                'motivo_traslado' => $motivo,
                'cliente_id' => $venta->cliente_id,
                'peso_bruto_total' => round(max(1, $peso), 3),
                'unidad_peso' => 'KGM',
                // 01 transporte publico, 02 transporte privado.
                'modalidad_traslado' => $i % 3 === 0 ? '01' : '02',
                'ubigeo_partida' => self::UBIGEO_TIENDA,
                'direccion_partida' => 'Av. La Molina 1234, La Molina - Lima',
                'ubigeo_llegada' => $ubigeo,
                'direccion_llegada' => $destinos[$ubigeo],
                'conductor_id' => $conductores[array_rand($conductores)],
                'vehiculo_id' => $vehiculos[array_rand($vehiculos)],
                'observaciones' => sprintf('Traslado de la venta %s-%08d', $venta->serie, $venta->numero),
                'estado_sunat' => $i < 3 ? 'ACEPTADO' : 'PENDIENTE',
                'caja_id' => $ctx['cajaId'],
                'usuario_id' => $ctx['almacenero'],
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            DB::table('guia_remision_detalles')->insert(array_map(
                fn (array $l) => $l + ['guia_remision_id' => $guiaId, 'created_at' => $emision, 'updated_at' => $emision],
                $filas
            ));
        }
    }

    /**
     * Traslados entre el almacen principal y la tienda. Los aprobados mueven el
     * stock de verdad: si solo cambiara el estado, el kardex mentiria.
     *
     * @param  array{almacenes:list<int>,admin:int,almacenero:int}  $ctx
     */
    private function traslados(array $ctx): void
    {
        if (! isset($ctx['almacenes'][1])) {
            return;
        }

        [$origen, $destino] = [$ctx['almacenes'][0], $ctx['almacenes'][1]];
        $estados = ['APROBADO', 'APROBADO', 'PENDIENTE', 'PENDIENTE', 'ANULADO'];
        $numero = 0;

        foreach ($estados as $estado) {
            $emision = now()->subDays(random_int(1, 35));

            // Solo productos con stock suficiente en origen: un traslado que
            // deja el almacen en negativo no lo aprobaria nadie.
            $items = DB::table('productos as p')
                ->join('producto_almacen as pa', 'pa.producto_id', '=', 'p.id')
                ->where('pa.almacen_id', $origen)
                ->where('pa.stock', '>', 30)
                ->where('p.precio_venta', '>', 0)
                ->inRandomOrder()
                ->limit(random_int(2, 5))
                ->get(['p.id', 'p.precio_compra', 'pa.stock']);

            if ($items->isEmpty()) {
                continue;
            }

            $ordenId = DB::table('ordenes_traslado')->insertGetId([
                'serie' => 'OT01',
                'numero' => ++$numero,
                'fecha_emision' => $emision->toDateString(),
                'fecha_vencimiento' => $emision->copy()->addDays(7)->toDateString(),
                'almacen_origen_id' => $origen,
                'almacen_destino_id' => $destino,
                'observaciones' => 'Reposicion de mercaderia hacia la tienda.',
                'estado' => $estado,
                'creado_por' => $ctx['almacenero'],
                'aprobado_por' => $estado === 'APROBADO' ? $ctx['admin'] : null,
                'anulado_por' => $estado === 'ANULADO' ? $ctx['admin'] : null,
                'fecha_aprobacion' => $estado === 'APROBADO' ? $emision->copy()->addHours(6) : null,
                'fecha_anulacion' => $estado === 'ANULADO' ? $emision->copy()->addHours(3) : null,
                'motivo_anulacion' => $estado === 'ANULADO' ? 'Stock no disponible al momento del despacho.' : null,
                'created_at' => $emision,
                'updated_at' => $emision,
            ]);

            $filas = [];

            foreach ($items as $item) {
                $cantidad = random_int(5, min(25, (int) $item->stock - 5));

                $filas[] = [
                    'orden_traslado_id' => $ordenId,
                    'producto_id' => $item->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $item->precio_compra,
                    'created_at' => $emision,
                    'updated_at' => $emision,
                ];

                if ($estado === 'APROBADO') {
                    DB::table('producto_almacen')
                        ->where('producto_id', $item->id)->where('almacen_id', $origen)
                        ->decrement('stock', $cantidad);

                    DB::table('producto_almacen')->upsert(
                        [[
                            'producto_id' => $item->id,
                            'almacen_id' => $destino,
                            'stock' => $cantidad,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]],
                        ['producto_id', 'almacen_id'],
                        ['updated_at']
                    );

                    DB::table('producto_almacen')
                        ->where('producto_id', $item->id)->where('almacen_id', $destino)
                        ->increment('stock', $cantidad);
                }
            }

            DB::table('orden_traslado_detalle')->insert($filas);
        }
    }

    /**
     * Convierte algunas ventas a credito y les arma el cronograma de cuotas.
     * El modulo de cuotas sin ninguna venta al credito no se puede mostrar.
     *
     * @param  array{}  $ctx
     */
    private function ventasCredito(array $ctx): void
    {
        $ventas = DB::table('ventas')
            ->where('estado', 'COMPLETADA')
            ->where('total', '>', 60)
            ->where('fecha_emision', '<', now()->subDays(3)->startOfDay())
            ->orderByDesc('fecha_emision')
            ->limit(6)
            ->get(['id', 'total', 'fecha_emision']);

        foreach ($ventas as $venta) {
            $cuotas = random_int(2, 4);
            $emision = \Illuminate\Support\Carbon::parse($venta->fecha_emision);
            $inicial = round((float) $venta->total * 0.3, 2);
            $saldo = round((float) $venta->total - $inicial, 2);
            $montoCuota = round($saldo / $cuotas, 2);

            DB::table('ventas')->where('id', $venta->id)->update([
                'tipo_venta' => 'CREDITO',
                'estado' => 'PENDIENTE',
                'pagado' => $inicial,
                'cambio' => 0,
            ]);

            $filas = [];

            for ($i = 1; $i <= $cuotas; $i++) {
                $vence = $emision->copy()->addMonths($i);

                // La ultima cuota absorbe el redondeo para que las cuotas sumen
                // exactamente el saldo.
                $monto = $i === $cuotas
                    ? round($saldo - $montoCuota * ($cuotas - 1), 2)
                    : $montoCuota;

                $filas[] = [
                    'venta_id' => $venta->id,
                    'numero_cuota' => $i,
                    'fecha_vencimiento' => $vence->toDateString(),
                    'monto' => $monto,
                    'estado' => $vence->isPast() ? 'PAGADA' : 'PENDIENTE',
                    'fecha_pago' => $vence->isPast() ? $vence->toDateString() : null,
                    'created_at' => $emision,
                    'updated_at' => $emision,
                ];
            }

            DB::table('venta_cuotas')->insert($filas);
        }
    }

    /**
     * Historial de caja: turnos cerrados con su monto real y uno abierto hoy,
     * que es el que el dashboard busca para el usuario logueado.
     *
     * @param  array{cajaId:int,admin:int,vendedor:int}  $ctx
     */
    private function cierresCaja(array $ctx): void
    {
        $cajas = DB::table('cajas')->orderBy('id')->pluck('id')->all();
        $responsables = [$ctx['admin'], $ctx['vendedor']];

        for ($dia = 12; $dia >= 1; $dia--) {
            $fecha = now()->subDays($dia);
            $inicial = 200.00;

            // El cierre cuadra con lo que realmente se vendio en efectivo ese
            // dia: un monto al azar haria que el arqueo no calce nunca.
            $efectivo = (float) DB::table('ventas')
                ->whereDate('fecha_emision', $fecha->toDateString())
                ->where('estado', 'COMPLETADA')
                ->where('forma_pago', 'EFECTIVO')
                ->sum('total');

            $gastos = (float) DB::table('gastos')
                ->whereDate('fecha_emision', $fecha->toDateString())
                ->sum('monto');

            DB::table('apertura_cajas')->insert([
                'fecha_apertura' => $fecha->toDateString(),
                'hora_apertura' => '08:00:00',
                'caja_id' => $cajas[$dia % count($cajas)],
                'responsable_id' => $responsables[$dia % 2],
                'monto_inicial' => $inicial,
                'estado' => 'CERRADA',
                'monto_cierre' => round($inicial + $efectivo - $gastos, 2),
                'fecha_cierre' => $fecha->toDateString(),
                'hora_cierre' => '22:00:00',
                'responsable_cierre_id' => $responsables[$dia % 2],
                'created_at' => $fecha->copy()->startOfDay()->addHours(8),
                'updated_at' => $fecha->copy()->startOfDay()->addHours(22),
            ]);
        }

        // Los turnos de hoy, abiertos. Hace falta uno por responsable: el POS
        // busca una caja ABIERTA del usuario logueado y sin ella redirige a
        // apertura, asi que con una sola el Vendedor no podria entrar al
        // terminal. Cada uno en su caja, como en una tienda con dos cajeros.
        $turnos = [
            [$ctx['admin'], $cajas[0], 200.00, '08:00:00'],
            [$ctx['vendedor'], $cajas[1] ?? $cajas[0], 150.00, '08:30:00'],
        ];

        foreach ($turnos as [$responsable, $caja, $inicial, $hora]) {
            DB::table('apertura_cajas')->insert([
                'fecha_apertura' => now()->toDateString(),
                'hora_apertura' => $hora,
                'caja_id' => $caja,
                'responsable_id' => $responsable,
                'monto_inicial' => $inicial,
                'estado' => 'ABIERTA',
                'created_at' => now()->startOfDay()->addHours(8),
                'updated_at' => now()->startOfDay()->addHours(8),
            ]);
        }
    }

    /**
     * El correlativo de cada serie debe quedar en el ultimo numero emitido, o
     * el proximo documento que se registre desde la UI chocaria con uno ya
     * existente.
     */
    private function correlativos(): void
    {
        $ultimos = [
            'BOLETA' => DB::table('ventas')->where('tipo_comprobante', 'BOLETA')->max('numero'),
            'FACTURA' => DB::table('ventas')->where('tipo_comprobante', 'FACTURA')->max('numero'),
            'NOTA_VENTA' => DB::table('notas_venta')->max('numero'),
            'COTIZACION' => DB::table('cotizaciones')->max('numero'),
            'NOTA_CREDITO' => DB::table('notas_credito')->max('numero'),
            'NOTA_DEBITO' => DB::table('notas_debito')->max('numero'),
            'GUIA_REMISION' => DB::table('guias_remision')->max('numero'),
        ];

        foreach ($ultimos as $tipo => $ultimo) {
            DB::table('series')
                ->where('tipo_comprobante', $tipo)
                ->update(['correlativo' => (int) ($ultimo ?? 0)]);
        }
    }

    private function auditoria(): void
    {
        DB::table('auditorias')->insert([
            'usuario_id' => null,
            'usuario_nombre' => 'Sistema',
            'accion' => 'SEMBRO',
            'entidad' => 'Sistema',
            'entidad_id' => null,
            'descripcion' => 'Sembro datos de demostracion en todos los modulos operativos',
            'cambios' => json_encode([
                'ventas' => DB::table('ventas')->count(),
                'compras' => DB::table('compras')->count(),
                'cotizaciones' => DB::table('cotizaciones')->count(),
                'notas_venta' => DB::table('notas_venta')->count(),
                'notas_credito' => DB::table('notas_credito')->count(),
                'notas_debito' => DB::table('notas_debito')->count(),
                'guias_remision' => DB::table('guias_remision')->count(),
                'ordenes_traslado' => DB::table('ordenes_traslado')->count(),
                'combos' => DB::table('combos')->count(),
            ]),
            'ip' => '127.0.0.1',
            'navegador' => 'Seeder',
            'created_at' => now(),
        ]);
    }

    /**
     * Ventas COMPLETADAS con detalle, para colgarles notas y guias. Se piden
     * las mas recientes para que los documentos derivados queden dentro del
     * rango que muestran los listados por defecto.
     */
    private function ventasConDetalle(int $cuantas): Collection
    {
        return DB::table('ventas')
            ->whereIn('id', fn ($q) => $q->select('venta_id')->from('venta_detalles'))
            ->where('estado', 'COMPLETADA')
            // Las ventas de los ultimos dias se dejan intactas: si una de
            // ellas termina anulada por una nota de credito, las tarjetas de
            // "hoy" del dashboard se vacian justo en la pantalla principal.
            ->where('fecha_emision', '<', now()->subDays(3)->startOfDay())
            ->orderByDesc('fecha_emision')
            ->limit($cuantas * 4)
            ->get(['id', 'cliente_id', 'serie', 'numero', 'total', 'fecha_emision'])
            ->shuffle()
            ->take($cuantas)
            ->values();
    }

    /** Un documento derivado nunca puede ser anterior a la venta que corrige. */
    private function fechaPosterior(string $fechaVenta): \Illuminate\Support\Carbon
    {
        $base = \Illuminate\Support\Carbon::parse($fechaVenta)->addDays(random_int(1, 5));

        return $base->isFuture() ? now()->subHours(random_int(1, 12)) : $base;
    }

    /**
     * Los precios de venta ya incluyen IGV, asi que el gravado se desagrega
     * hacia atras en vez de sumarle el impuesto al total.
     *
     * @return array{float,float,float} [gravado, igv, total]
     */
    private function desagregar(float $total): array
    {
        $total = round($total, 2);
        $gravado = round($total / (1 + self::IGV), 2);

        return [$gravado, round($total - $gravado, 2), $total];
    }
}
