<?php

namespace Tests\Support;

use App\Models\Almacen;
use App\Models\AperturaCaja;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\ProductoAlmacen;
use App\Models\Role;
use App\Models\Serie;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Arma el minimo necesario para vender: empresa, caja, almacen, serie, usuario
 * con rol, cliente y producto con stock.
 *
 * Cada test crea lo suyo en vez de depender de los seeders, para que no importe
 * en que estado quedo la base.
 */
trait CreaEscenarioDeVenta
{
    protected Caja $caja;
    protected Almacen $almacen;
    protected User $vendedor;
    protected Cliente $cliente;
    protected Serie $serieBoleta;

    protected function montarEscenario(int $stockInicial = 100): Producto
    {
        Empresa::firstOrCreate(
            ['ruc' => '20512345678'],
            [
                'razon_social' => 'MINIMARKET DE PRUEBA S.A.C.',
                'direccion' => 'Av. Prueba 123', 'pais' => 'Perú',
                'departamento' => 'Lima', 'provincia' => 'Lima', 'distrito' => 'Lima',
                'estado' => 1,
            ]
        );

        $this->caja = Caja::create(['descripcion' => 'Caja de prueba ' . uniqid()]);
        $this->almacen = Almacen::create([
            'descripcion' => 'Almacen de prueba ' . uniqid(),
            'establecimiento' => 'Oficina Principal',
        ]);

        $rol = Role::firstOrCreate(['nombre' => 'Vendedor'], ['descripcion' => 'Ventas']);

        $this->vendedor = User::create([
            'name' => 'Vendedor de prueba',
            'email' => 'vendedor.' . uniqid() . '@prueba.test',
            'password' => Hash::make('secret'),
            'caja_id' => $this->caja->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 1,
        ]);
        DB::table('user_roles')->insert(['user_id' => $this->vendedor->id, 'role_id' => $rol->id]);

        $this->serieBoleta = Serie::create([
            'serie' => 'B' . random_int(100, 999),
            'correlativo' => 0,
            'tipo_comprobante' => 'BOLETA',
            'caja_id' => $this->caja->id,
        ]);

        $this->cliente = Cliente::create([
            'tipo_documento' => 'DNI',
            'numero_documento' => (string) random_int(10000000, 99999999),
            'nombre_razon_social' => 'Cliente de prueba',
            'estado' => 1,
        ]);

        AperturaCaja::create([
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura' => now(),
            'caja_id' => $this->caja->id,
            'responsable_id' => $this->vendedor->id,
            'monto_inicial' => 100,
            'estado' => 'ABIERTA',
        ]);

        return $this->crearProducto($stockInicial);
    }

    protected function crearProducto(int $stock, string $operacion = 'GRAVADO', float $precio = 10.00): Producto
    {
        $producto = Producto::create([
            'codigo_interno' => 'TEST' . uniqid(),
            'descripcion' => 'Producto de prueba',
            'unidad' => 'UNIDAD',
            'operacion' => $operacion,
            'tipo_producto' => 'PRODUCTO',
            'precio_compra' => $precio / 2,
            'precio_venta' => $precio,
            'stock_minimo' => 5,
            'detraccion' => 0,
            'estado' => 1,
        ]);

        ProductoAlmacen::create([
            'producto_id' => $producto->id,
            'almacen_id' => $this->almacen->id,
            'stock' => $stock,
        ]);

        return $producto;
    }

    /** Dispara una venta por el mismo endpoint que usa el terminal. */
    protected function vender(array $items, float $total, array $extra = [])
    {
        return $this->actingAs($this->vendedor)->postJson('/terminal/procesar-pago', array_merge([
            'tipo_comprobante' => 'BOLETA',
            'cliente_id' => $this->cliente->id,
            'tipo_venta' => 'CONTADO',
            'forma_pago' => 'EFECTIVO',
            'total' => $total,
            'pagado' => $total,
            'productos_json' => json_encode($items),
        ], $extra));
    }

    protected function stockDe(Producto $producto): int
    {
        return (int) ProductoAlmacen::where('producto_id', $producto->id)
            ->where('almacen_id', $this->almacen->id)
            ->value('stock');
    }
}
