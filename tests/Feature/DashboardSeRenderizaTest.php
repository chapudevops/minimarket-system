<?php

namespace Tests\Feature;

use App\Catalogo\Imagenes\EstadoFoto;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreaEscenarioDeVenta;
use Tests\TestCase;

/**
 * El dashboard tiene que ABRIR.
 *
 * Este archivo existe por un error concreto: la vista de productos mas vendidos
 * se cambio para usar foto_url() y tieneFoto(), pero la consulta devolvia filas
 * de DB::table(), o sea stdClass, y el panel reventaba con
 * "Call to undefined method stdClass::tieneFoto()".
 *
 * El test que habia comprobaba el modelo por separado y por eso no lo vio.
 * Comprobar el metodo sin renderizar la vista deja pasar justo esta clase de
 * error, que es la que el usuario ve.
 */
class DashboardSeRenderizaTest extends TestCase
{
    use CreaEscenarioDeVenta, DatabaseTransactions;

    private function administrador(): User
    {
        $usuario = User::create([
            'name' => 'Admin dashboard',
            'email' => 'admin.dash.'.uniqid().'@prueba.test',
            'password' => Hash::make('secret'),
            'caja_id' => $this->caja->id,
            'almacen_id' => $this->almacen->id,
            'estado' => 1,
        ]);

        $rol = Role::firstOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total']);
        DB::table('user_roles')->insert(['user_id' => $usuario->id, 'role_id' => $rol->id]);

        return $usuario;
    }

    /** Una venta real, para que el producto entre en "mas vendidos". */
    private function venderDeVerdad(Producto $producto, int $cantidad = 3): void
    {
        $venta = Venta::create([
            'tipo_comprobante' => 'BOLETA',
            'serie' => $this->serieBoleta->serie,
            'numero' => random_int(90000, 99999),
            'fecha_emision' => now(),
            'cliente_id' => $this->cliente->id,
            'tipo_venta' => 'CONTADO',
            'forma_pago' => 'EFECTIVO',
            'subtotal' => 10, 'igv' => 1.8, 'total' => 11.8, 'pagado' => 11.8, 'cambio' => 0,
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->vendedor->id,
            'estado' => 'COMPLETADA',
        ]);

        VentaDetalle::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => 10.00,
            'total' => $cantidad * 10.00,
            'almacen_id' => $this->almacen->id,
        ]);
    }

    #[Test]
    public function el_dashboard_abre_con_un_producto_mas_vendido_sin_foto(): void
    {
        $producto = $this->montarEscenario(20);
        $this->venderDeVerdad($producto);

        $respuesta = $this->actingAs($this->administrador())->get('/');

        $respuesta->assertOk();
        // Sin foto se pinta el placeholder o el icono, nunca una imagen rota.
        $respuesta->assertSee('Productos Más Vendidos', false);
    }

    #[Test]
    public function el_dashboard_abre_con_un_producto_que_tiene_foto(): void
    {
        $producto = $this->montarEscenario(20);
        $producto->forceFill([
            'foto' => 'productos/'.$producto->id.'.webp',
            'foto_estado' => EstadoFoto::VERIFICADA,
            'foto_fuente' => 'OPENFOODFACTS',
            'foto_licencia' => 'CC BY-SA 3.0',
            'foto_atribucion' => 'Imagen: Open Food Facts (CC BY-SA 3.0)',
        ])->save();

        $this->venderDeVerdad($producto);

        $respuesta = $this->actingAs($this->administrador())->get('/');

        $respuesta->assertOk();
        // La URL sale del modelo y no duplica el segmento de carpeta.
        $respuesta->assertSee('storage/productos/'.$producto->id.'.webp', false);
        $respuesta->assertDontSee('productos/productos/', false);
    }

    #[Test]
    public function los_mas_vendidos_llegan_a_la_vista_como_modelos(): void
    {
        $producto = $this->montarEscenario(20);
        $this->venderDeVerdad($producto);

        $top = app(\App\Services\DashboardService::class)->getProductosMasVendidos();

        $this->assertNotEmpty($top);

        // Es la causa exacta del fallo: si esto vuelve a ser stdClass, la vista
        // no puede pedirle foto_url ni tieneFoto y el dashboard no abre.
        $this->assertInstanceOf(Producto::class, $top->first());
        $this->assertIsString($top->first()->foto_url);
        $this->assertIsBool($top->first()->tieneFoto());

        // Y los agregados que la vista necesita siguen estando.
        $this->assertNotNull($top->first()->total_vendido);
        $this->assertNotNull($top->first()->total_monto);
    }
}
