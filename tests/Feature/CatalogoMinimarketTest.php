<?php

namespace Tests\Feature;

use App\Models\Producto;
use Database\Seeders\CatalogoMinimarket;
use Database\Seeders\CatalogoMinimarketSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Calidad del catálogo maestro.
 *
 * Esta clase NO usa DatabaseTransactions a proposito: el catalogo se siembra
 * una sola vez y queda en la base de test. Envolverlo en una transaccion lo
 * revertiria al terminar cada test, y sembrarlo en cada uno multiplicaria por
 * quince el tiempo sin aportar nada.
 *
 * Que el catalogo persista es justamente lo que hace real la prueba de
 * idempotencia.
 */
class CatalogoMinimarketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El seeder es idempotente, asi que basta con sembrar si falta.
        if (Producto::whereRaw('codigo_interno REGEXP "^[A-Z]{3}-[A-Z]{3}-[0-9]{6}$"')->doesntExist()) {
            $this->seed(CatalogoMinimarketSeeder::class);
        }
    }

    /** Sólo los productos del catálogo maestro, no los de demo anteriores. */
    private function delCatalogo()
    {
        return Producto::whereRaw('codigo_interno REGEXP "^[A-Z]{3}-[A-Z]{3}-[0-9]{6}$"');
    }

    #[Test]
    public function el_catalogo_supera_las_mil_referencias(): void
    {
        $this->assertGreaterThanOrEqual(1000, $this->delCatalogo()->count());
    }

    #[Test]
    public function no_hay_codigos_internos_repetidos(): void
    {
        $repetidos = DB::table('productos')
            ->select('codigo_interno')
            ->groupBy('codigo_interno')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->assertSame(0, $repetidos);
    }

    #[Test]
    public function los_codigos_de_barras_no_se_inventan(): void
    {
        // Sin fuente verificable el EAN va NULL: un código falso es peor que
        // ninguno, porque el lector lo daría por bueno.
        $this->assertSame(0, $this->delCatalogo()->whereNotNull('codigo_barras')->count());
    }

    #[Test]
    public function un_codigo_de_barras_presente_no_se_repite(): void
    {
        $repetidos = DB::table('productos')
            ->whereNotNull('codigo_barras')
            ->select('codigo_barras')
            ->groupBy('codigo_barras')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->assertSame(0, $repetidos);
    }

    #[Test]
    public function todos_los_precios_son_validos(): void
    {
        $this->assertSame(0, $this->delCatalogo()->where('precio_compra', '<=', 0)->count(),
            'hay productos con precio de compra en cero o negativo');

        $this->assertSame(0, $this->delCatalogo()->whereColumn('precio_venta', '<=', 'precio_compra')->count(),
            'hay productos que se venderían a pérdida');
    }

    #[Test]
    public function los_margenes_no_son_todos_iguales(): void
    {
        $margenes = $this->delCatalogo()->limit(300)->get()
            ->map(fn ($p) => round(($p->precio_venta - $p->precio_compra) / $p->precio_compra, 2))
            ->unique();

        // Un margen único en todo el catálogo delataría datos generados a la
        // ligera; deben variar por categoría y por producto.
        $this->assertGreaterThan(10, $margenes->count());
    }

    #[Test]
    public function las_unidades_son_las_que_acepta_el_formulario(): void
    {
        $invalidas = $this->delCatalogo()
            ->whereNotIn('unidad', CatalogoMinimarket::UNIDADES)
            ->pluck('unidad')
            ->unique();

        $this->assertEmpty($invalidas->all(), 'unidades fuera de catálogo: '.$invalidas->implode(', '));
    }

    #[Test]
    public function las_operaciones_y_tipos_pasan_la_validacion_del_controller(): void
    {
        $this->assertSame(0, $this->delCatalogo()->whereNotIn('operacion', ['GRAVADO', 'EXONERADO', 'INAFECTO'])->count());
        $this->assertSame(0, $this->delCatalogo()->whereNotIn('tipo_producto', ['PRODUCTO', 'SERVICIO'])->count());
    }

    #[Test]
    public function el_stock_minimo_nunca_es_negativo(): void
    {
        $this->assertSame(0, $this->delCatalogo()->where('stock_minimo', '<', 0)->count());
    }

    #[Test]
    public function no_hay_productos_duplicados_por_marca_descripcion_y_presentacion(): void
    {
        $duplicados = DB::table('productos')
            ->whereRaw('codigo_interno REGEXP "^[A-Z]{3}-[A-Z]{3}-[0-9]{6}$"')
            ->select('marca', 'descripcion', 'presentacion')
            ->groupBy('marca', 'descripcion', 'presentacion')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->assertSame(0, $duplicados);
    }

    #[Test]
    public function los_productos_por_peso_usan_kilogramo(): void
    {
        // Frutas, verduras, carnes y granel se venden por peso.
        $porPeso = $this->delCatalogo()
            ->whereRaw('codigo_interno REGEXP "^(FRE-(FRU|VER|GRA)|CAR-)"')
            ->get();

        $this->assertNotEmpty($porPeso, 'debería haber productos vendidos por peso');

        foreach ($porPeso as $producto) {
            $this->assertSame('KG', $producto->unidad, "{$producto->codigo_interno} debería venderse por kilo");
        }
    }

    #[Test]
    public function los_servicios_no_llevan_stock(): void
    {
        $servicios = $this->delCatalogo()->where('tipo_producto', 'SERVICIO')->get();

        $this->assertNotEmpty($servicios);

        foreach ($servicios as $servicio) {
            $this->assertSame(0, $servicio->stocks()->count(),
                "{$servicio->codigo_interno} es un servicio y no debe tener stock por almacén");
        }
    }

    #[Test]
    public function ningun_producto_de_minimarket_activa_detraccion(): void
    {
        $this->assertSame(0, $this->delCatalogo()->where('detraccion', 1)->count());
    }

    #[Test]
    public function las_descripciones_son_comerciales_y_no_genericas(): void
    {
        $genericas = $this->delCatalogo()
            ->where(function ($q) {
                $q->where('descripcion', 'REGEXP', '^Producto [0-9]+$')
                    ->orWhere('descripcion', 'REGEXP', '^Item [0-9]+$')
                    ->orWhere('descripcion', 'REGEXP', '^Test');
            })
            ->count();

        $this->assertSame(0, $genericas);
    }

    #[Test]
    public function volver_a_sembrar_no_duplica_nada(): void
    {
        $antes = Producto::count();

        $this->seed(CatalogoMinimarketSeeder::class);

        $this->assertSame($antes, Producto::count(), 'el seeder no es idempotente');
    }

    #[Test]
    public function el_catalogo_cubre_todas_las_familias_declaradas(): void
    {
        $familias = $this->delCatalogo()
            ->get()
            ->map(fn ($p) => substr($p->codigo_interno, 0, 3))
            ->unique()
            ->sort()
            ->values();

        $esperadas = ['ABA', 'BEB', 'CAR', 'CUI', 'DES', 'EST', 'FRE', 'HOG', 'LAC', 'LIC', 'LIM', 'SNK', 'SRV'];

        $this->assertSame($esperadas, $familias->all());
    }
}
