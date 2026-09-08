<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovimientoSeeder extends Seeder
{
    private const IGV = 0.18;

    public function run(): void
    {
        // Sin la transaccion cada insert hace su propio commit con fsync, lo que
        // vuelve el seeder lentisimo (del orden de un insert por segundo).
        DB::transaction(function () {
            $this->limpiar();
            $this->compras();
            $this->ventas();
            $this->gastos();
            $this->aperturaCaja();
        });
    }

    private function limpiar(): void
    {
        // Es data de demo con fechas relativas a hoy: re-sembrar duplicaria
        // movimientos en vez de actualizarlos, asi que se limpia primero.
        DB::table('venta_detalles')->delete();
        DB::table('ventas')->delete();
        DB::table('compra_detalles')->delete();
        DB::table('compras')->delete();
        DB::table('gastos')->delete();
        DB::table('apertura_cajas')->delete();
    }

    private function compras(): void
    {
        $proveedores = DB::table('proveedores')->pluck('id')->all();
        $almacenId = DB::table('almacenes')->orderBy('id')->value('id');
        $usuarioId = DB::table('users')->orderBy('id')->value('id');
        $productos = DB::table('productos')->get(['id', 'precio_compra'])->all();

        // 8 compras en los ultimos 90 dias, con las 3 primeras dentro de los
        // ultimos 10 para que el dashboard (que filtra por mes en curso) no
        // muestre las tarjetas de compras en cero.
        for ($i = 1; $i <= 8; $i++) {
            $dias = $i <= 3 ? random_int(0, 10) : random_int(11, 90);
            $fecha = now()->subDays($dias)->startOfDay();
            $items = collect($productos)->random(random_int(3, 7));

            $subtotal = 0.0;
            $lineas = [];

            foreach ($items as $producto) {
                $cantidad = random_int(10, 60);
                $total = round($cantidad * $producto->precio_compra, 2);
                $subtotal += $total;

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_compra,
                    'total' => $total,
                ];
            }

            $subtotal = round($subtotal, 2);
            $igv = round($subtotal * self::IGV, 2);

            $compraId = DB::table('compras')->insertGetId([
                'tipo_comprobante' => 'FACTURA',
                'serie' => 'F00'.random_int(1, 3),
                'numero' => 1000 + $i,
                'fecha_emision' => $fecha->toDateString(),
                'fecha_vencimiento' => $fecha->copy()->addDays(30)->toDateString(),
                'proveedor_id' => $proveedores[array_rand($proveedores)],
                'almacen_id' => $almacenId,
                'tipo_cambio' => 1,
                'tipo_pago' => 'EFECTIVO',
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => round($subtotal + $igv, 2),
                'estado' => 'REGISTRADA',
                'usuario_id' => $usuarioId,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);

            DB::table('compra_detalles')->insert(array_map(
                fn (array $linea) => $linea + [
                    'compra_id' => $compraId,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ],
                $lineas
            ));
        }
    }

    private function ventas(): void
    {
        $clientes = DB::table('clientes')->pluck('id')->all();
        $almacenId = DB::table('almacenes')->orderBy('id')->value('id');
        $cajaId = DB::table('cajas')->orderBy('id')->value('id');
        $usuarios = DB::table('users')->pluck('id')->all();
        $productos = DB::table('productos')->get(['id', 'precio_venta'])->all();

        $formasPago = ['EFECTIVO', 'EFECTIVO', 'EFECTIVO', 'YAPE', 'TARJETA', 'TRANSFERENCIA'];
        $correlativo = ['BOLETA' => 0, 'FACTURA' => 0];

        // 60 dias de ventas, con varias operaciones hoy para que las tarjetas
        // de "ventas de hoy" del dashboard no salgan en cero.
        for ($dia = 59; $dia >= 0; $dia--) {
            $fecha = now()->subDays($dia);
            $porDia = $dia === 0 ? random_int(4, 7) : random_int(1, 5);

            for ($v = 0; $v < $porDia; $v++) {
                $tipo = random_int(1, 100) <= 80 ? 'BOLETA' : 'FACTURA';
                $correlativo[$tipo]++;

                $emision = $fecha->copy()->setTime(random_int(8, 21), random_int(0, 59));
                $items = collect($productos)->random(random_int(1, 6));

                $subtotal = 0.0;
                $lineas = [];

                foreach ($items as $producto) {
                    $cantidad = random_int(1, 5);
                    $total = round($cantidad * $producto->precio_venta, 2);
                    $subtotal += $total;

                    $lineas[] = [
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $producto->precio_venta,
                        'total' => $total,
                        'almacen_id' => $almacenId,
                    ];
                }

                // El precio de venta ya incluye IGV, asi que se desagrega.
                $total = round($subtotal, 2);
                $gravado = round($total / (1 + self::IGV), 2);
                $igv = round($total - $gravado, 2);
                $pagado = ceil($total / 10) * 10;

                $ventaId = DB::table('ventas')->insertGetId([
                    'tipo_comprobante' => $tipo,
                    'serie' => $tipo === 'BOLETA' ? 'B001' : 'F001',
                    'numero' => $correlativo[$tipo],
                    'fecha_emision' => $emision,
                    'cliente_id' => $clientes[array_rand($clientes)],
                    'tipo_venta' => 'CONTADO',
                    'forma_pago' => $formasPago[array_rand($formasPago)],
                    'subtotal' => $gravado,
                    'igv' => $igv,
                    'total' => $total,
                    'pagado' => $pagado,
                    'cambio' => round($pagado - $total, 2),
                    'detraccion' => 0,
                    'caja_id' => $cajaId,
                    'usuario_id' => $usuarios[array_rand($usuarios)],
                    'estado' => 'COMPLETADA',
                    'created_at' => $emision,
                    'updated_at' => $emision,
                ]);

                DB::table('venta_detalles')->insert(array_map(
                    fn (array $linea) => $linea + [
                        'venta_id' => $ventaId,
                        'created_at' => $emision,
                        'updated_at' => $emision,
                    ],
                    $lineas
                ));
            }
        }

        // Dejar los correlativos de series alineados con lo ya emitido.
        foreach ($correlativo as $tipo => $ultimo) {
            DB::table('series')->where('tipo_comprobante', $tipo)->update(['correlativo' => $ultimo]);
        }
    }

    private function gastos(): void
    {
        $usuarioId = DB::table('users')->orderBy('id')->value('id');

        $motivos = [
            ['Pago de luz',              'SERVICIOS',   320.50],
            ['Pago de agua',             'SERVICIOS',   145.00],
            ['Internet y telefonia',     'SERVICIOS',   189.90],
            ['Alquiler del local',       'ALQUILER',   2500.00],
            ['Movilidad y reparto',      'LOGISTICA',    80.00],
            ['Utiles de limpieza',       'MANTENIMIENTO', 65.40],
            ['Mantenimiento de vitrina', 'MANTENIMIENTO', 230.00],
            ['Bolsas y empaques',        'INSUMOS',     120.00],
        ];

        $filas = [];

        // Mismo criterio que las compras: dentro de los ultimos 25 dias para
        // que caigan en el rango por defecto del dashboard.
        foreach ($motivos as [$motivo, $cuenta, $monto]) {
            $fecha = now()->subDays(random_int(0, 25));

            $filas[] = [
                'fecha_emision' => $fecha->toDateString(),
                'motivo' => $motivo,
                'cuenta' => $cuenta,
                'monto' => $monto,
                'detalle' => 'Gasto operativo de demostracion',
                'usuario_id' => $usuarioId,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ];
        }

        DB::table('gastos')->insert($filas);
    }

    private function aperturaCaja(): void
    {
        $usuarioId = DB::table('users')->orderBy('id')->value('id');

        // Una caja abierta hoy a nombre del admin: el dashboard busca
        // AperturaCaja ABIERTA del usuario logueado.
        DB::table('apertura_cajas')->insert([
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura' => '08:00:00',
            'responsable_id' => $usuarioId,
            'monto_inicial' => 200.00,
            'estado' => 'ABIERTA',
            'created_at' => now()->startOfDay()->addHours(8),
            'updated_at' => now()->startOfDay()->addHours(8),
        ]);
    }
}
