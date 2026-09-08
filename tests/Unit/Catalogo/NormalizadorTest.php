<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\ClaveProducto;
use App\Catalogo\EsquemaMaestro;
use App\Catalogo\EsquemaRaw;
use App\Catalogo\GeneradorCodigoInterno;
use App\Catalogo\Normalizador;
use App\Catalogo\NormalizadorMarca;
use App\Catalogo\NormalizadorPresentacion;
use App\Catalogo\ReglasTributarias;
use App\Catalogo\Taxonomia;
use App\Sunat\Tributos;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El pipeline completo: fila RAW -> fila del catalogo maestro.
 *
 * Buena parte de estos tests verifica lo que el normalizador NO hace. Es lo
 * mas importante del modulo: un precio de compra inventado a partir del precio
 * de gondola, un EAN inventado o una afectacion de IGV supuesta envenenan el
 * inventario y la facturacion.
 */
class NormalizadorTest extends TestCase
{
    private function normalizador(?GeneradorCodigoInterno $codigos = null): Normalizador
    {
        $marcas = new NormalizadorMarca(['cocacola' => 'Coca-Cola', 'costeno' => 'Costeño']);
        $presentaciones = new NormalizadorPresentacion();

        $taxonomia = new Taxonomia([
            ['categoria' => 'BEBIDAS', 'subcategoria' => 'GASEOSAS', 'codigo_categoria' => 'BEB',
                'codigo_subcategoria' => 'GAS', 'unidad_sugerida' => 'UNIDAD'],
            ['categoria' => 'LICORES', 'subcategoria' => 'CERVEZA', 'codigo_categoria' => 'LIC',
                'codigo_subcategoria' => 'CER', 'unidad_sugerida' => 'UNIDAD'],
            ['categoria' => 'ABARROTES', 'subcategoria' => 'ARROZ', 'codigo_categoria' => 'ABA',
                'codigo_subcategoria' => 'ARR', 'unidad_sugerida' => 'UNIDAD'],
            ['categoria' => 'LACTEOS', 'subcategoria' => 'LECHE', 'codigo_categoria' => 'LAC',
                'codigo_subcategoria' => 'LEC', 'unidad_sugerida' => 'UNIDAD'],
            ['categoria' => 'FRESCOS', 'subcategoria' => 'VERDURAS', 'codigo_categoria' => 'FRE',
                'codigo_subcategoria' => 'VER', 'unidad_sugerida' => 'KG'],
        ]);

        $tributos = new ReglasTributarias([
            ['categoria' => 'BEBIDAS', 'subcategoria' => 'GASEOSAS', 'producto_tipo' => '*',
                'igv' => 'GRAVADO', 'isc' => 'REVISAR', 'ivap' => 'NO', 'requiere_revision' => 'false',
                'fuente_normativa' => 'TUO Ley IGV art. 1', 'observacion' => 'ISC segun azucar.'],
            ['categoria' => 'LICORES', 'subcategoria' => 'CERVEZA', 'producto_tipo' => '*',
                'igv' => 'GRAVADO', 'isc' => 'SI', 'ivap' => 'NO', 'requiere_revision' => 'false',
                'fuente_normativa' => 'Apendice IV', 'observacion' => 'Bebida alcoholica.'],
            ['categoria' => 'ABARROTES', 'subcategoria' => 'ARROZ', 'producto_tipo' => '*',
                'igv' => 'PENDIENTE', 'isc' => 'NO', 'ivap' => 'REVISAR', 'requiere_revision' => 'true',
                'fuente_normativa' => 'Ley 28211', 'observacion' => 'No todo arroz es IVAP.'],
            ['categoria' => 'ABARROTES', 'subcategoria' => 'ARROZ', 'producto_tipo' => 'ARROZ_PILADO',
                'igv' => 'PENDIENTE', 'isc' => 'NO', 'ivap' => 'SI', 'requiere_revision' => 'true',
                'fuente_normativa' => 'Ley 28211', 'observacion' => 'Ambito IVAP; la operacion la define el contador.'],
            ['categoria' => 'LACTEOS', 'subcategoria' => 'LECHE', 'producto_tipo' => '*',
                'igv' => 'PENDIENTE', 'isc' => 'NO', 'ivap' => 'NO', 'requiere_revision' => 'true',
                'fuente_normativa' => 'Apendice I', 'observacion' => 'Clasificar por producto_tipo.'],
            ['categoria' => 'LACTEOS', 'subcategoria' => 'LECHE', 'producto_tipo' => 'LECHE_CRUDA_ENTERA',
                'igv' => 'EXONERADO', 'isc' => 'NO', 'ivap' => 'NO', 'requiere_revision' => 'false',
                'fuente_normativa' => 'Apendice I', 'observacion' => 'Nominada en el Apendice I.'],
            ['categoria' => 'LACTEOS', 'subcategoria' => 'LECHE', 'producto_tipo' => 'LECHE_EVAPORADA',
                'igv' => 'PENDIENTE', 'isc' => 'NO', 'ivap' => 'NO', 'requiere_revision' => 'true',
                'fuente_normativa' => 'Apendice I', 'observacion' => 'Industrializada: lo confirma el contador.'],
            ['categoria' => 'FRESCOS', 'subcategoria' => 'VERDURAS', 'producto_tipo' => '*',
                'igv' => 'EXONERADO', 'isc' => 'NO', 'ivap' => 'NO', 'requiere_revision' => 'false',
                'fuente_normativa' => 'Apendice I', 'observacion' => 'Hortalizas frescas.'],
        ]);

        return new Normalizador(
            $marcas,
            $presentaciones,
            $taxonomia,
            $tributos,
            new ClaveProducto($marcas, $presentaciones),
            $codigos ?? new GeneradorCodigoInterno(),
        );
    }

    private function raw(array $campos = []): array
    {
        return array_merge([
            'fuente'            => 'TOTTUS',
            'categoria'         => 'BEBIDAS',
            'subcategoria'      => 'GASEOSAS',
            'producto_tipo'     => '',
            'marca'             => 'COCA COLA',
            'descripcion'       => 'Gaseosa Coca Cola Original',
            'presentacion'      => '500ML',
            'precio_referencia' => '2.90',
            'url_fuente'        => 'https://www.tottus.com.pe/tottus-pe/producto/ejemplo',
            'fecha_consulta'    => '2026-09-07',
        ], $campos);
    }

    #[Test]
    public function una_fila_raw_se_convierte_en_una_fila_del_maestro(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw());

        $this->assertSame(EsquemaMaestro::COLUMNAS, array_keys($fila));
        $this->assertSame('BEB-GAS-000001', $fila['codigo_interno']);
        $this->assertSame('Coca-Cola', $fila['marca']);
        $this->assertSame('500 ml', $fila['presentacion']);
        $this->assertSame('UNIDAD', $fila['unidad']);
        $this->assertSame('PRODUCTO', $fila['tipo_producto']);
        $this->assertSame('0', $fila['detraccion']);
        $this->assertSame('TOTTUS', $fila['fuente']);
        $this->assertStringContainsString('tottus.com.pe', $fila['url_fuente']);
    }

    #[Test]
    public function no_inventa_codigo_de_barras_ni_foto(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw());

        // Las fuentes publicas no publican EAN verificable: va vacio, y vacio
        // se lee como NULL al importar.
        $this->assertSame('', $fila['codigo_barras']);
        $this->assertSame('', $fila['foto']);
    }

    #[Test]
    public function el_precio_de_gondola_no_se_convierte_en_precio_de_compra(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw(['precio_referencia' => '2.90']));

        // 2.90 es lo que cobra el supermercado al publico, no lo que le cuesta
        // al minimarket. Derivar uno del otro seria un costo inventado.
        $this->assertSame('', $fila['precio_compra']);
        $this->assertSame('', $fila['precio_venta']);
        $this->assertNotContains('precio_referencia', array_keys($fila));
    }

    #[Test]
    public function no_inventa_vencimiento_ni_stock(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw());

        $this->assertSame('', $fila['fecha_vencimiento']);
        $this->assertSame('', $fila['stock_minimo']);
    }

    /* --- Los tres ejes tributarios, separados -------------------------- */

    #[Test]
    public function un_producto_gravado_puede_ademas_estar_afecto_al_isc(): void
    {
        // Es el caso que el modelo anterior no sabia representar: la cerveza es
        // GRAVADA de IGV *y ademas* esta en el ambito del ISC. Son dos hechos
        // distintos y ahora viven en dos columnas distintas.
        $fila = $this->normalizador()->normalizar($this->raw([
            'categoria' => 'LICORES', 'subcategoria' => 'CERVEZA',
            'marca' => '', 'descripcion' => 'Cerveza Rubia', 'presentacion' => '650 ml',
        ]));

        $this->assertSame('GRAVADO', $fila['operacion']);
        $this->assertSame('1', $fila['afecto_isc']);
        $this->assertSame('0', $fila['afecto_ivap']);
    }

    #[Test]
    public function el_ivap_no_se_confunde_con_la_afectacion_del_igv(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw([
            'categoria' => 'ABARROTES', 'subcategoria' => 'ARROZ',
            'producto_tipo' => 'ARROZ_PILADO',
            'marca' => 'COSTEÑO', 'descripcion' => 'Arroz Extra', 'presentacion' => '5 kg',
        ]));

        // El IVAP se marca en su propia columna. La afectacion de IGV queda
        // PENDIENTE porque depende de que operacion haga el negocio, no del
        // producto: eso lo decide el contador, no el normalizador.
        $this->assertSame('1', $fila['afecto_ivap']);
        $this->assertSame(Tributos::PENDIENTE, $fila['operacion']);
        $this->assertSame('0', $fila['afecto_isc']);
    }

    #[Test]
    public function no_todo_arroz_es_ivap(): void
    {
        // Sin producto_tipo no se sabe si es arroz pilado: no se marca IVAP
        // solo porque la descripcion diga "arroz".
        $fila = $this->normalizador()->normalizar($this->raw([
            'categoria' => 'ABARROTES', 'subcategoria' => 'ARROZ',
            'marca' => 'COSTEÑO', 'descripcion' => 'Arroz Integral Organico', 'presentacion' => '1 kg',
        ]));

        $this->assertSame('0', $fila['afecto_ivap']);
        $this->assertSame(Tributos::PENDIENTE, $fila['operacion']);
    }

    #[Test]
    public function dos_leches_de_la_misma_subcategoria_no_tributan_igual(): void
    {
        $normalizador = $this->normalizador();

        $cruda = $normalizador->normalizar($this->raw([
            'categoria' => 'LACTEOS', 'subcategoria' => 'LECHE', 'producto_tipo' => 'LECHE_CRUDA_ENTERA',
            'marca' => '', 'descripcion' => 'Leche Cruda Entera', 'presentacion' => '1 L',
        ]));

        $evaporada = $normalizador->normalizar($this->raw([
            'categoria' => 'LACTEOS', 'subcategoria' => 'LECHE', 'producto_tipo' => 'LECHE_EVAPORADA',
            'marca' => '', 'descripcion' => 'Leche Evaporada Entera', 'presentacion' => '400 g',
        ]));

        // Nominada en el Apendice I contra producto industrializado: la
        // subcategoria "LECHE" no alcanza para decidir, y por eso existe
        // producto_tipo.
        $this->assertSame('EXONERADO', $cruda['operacion']);
        $this->assertSame(Tributos::PENDIENTE, $evaporada['operacion']);
    }

    #[Test]
    public function sin_producto_tipo_una_familia_ambigua_queda_pendiente(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw([
            'categoria' => 'LACTEOS', 'subcategoria' => 'LECHE',
            'marca' => '', 'descripcion' => 'Leche', 'presentacion' => '400 g',
        ]));

        $this->assertSame(Tributos::PENDIENTE, $fila['operacion']);
        $this->assertSame('1', $fila['requiere_revision_tributaria']);
    }

    #[Test]
    public function una_duda_de_isc_no_bloquea_una_afectacion_de_igv_segura(): void
    {
        // Una gaseosa es GRAVADA sin discusion; lo que depende del azucar es
        // su ISC. Bloquear la importacion por eso dejaria fuera medio catalogo.
        $fila = $this->normalizador()->normalizar($this->raw());

        $this->assertSame('GRAVADO', $fila['operacion']);
        $this->assertSame('1', $fila['requiere_revision_tributaria']);
        $this->assertNotContains('operacion', EsquemaMaestro::faltantesParaImportar($fila));
    }

    #[Test]
    public function pendiente_bloquea_la_importacion_al_catalogo_definitivo(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw([
            'categoria' => 'ABARROTES', 'subcategoria' => 'ARROZ',
            'marca' => 'COSTEÑO', 'descripcion' => 'Arroz Extra', 'presentacion' => '5 kg',
        ]));

        $this->assertSame(Tributos::PENDIENTE, $fila['operacion']);
        $this->assertContains('operacion', EsquemaMaestro::faltantesParaImportar($fila));
        $this->assertFalse(Tributos::esVendible($fila['operacion']));
    }

    #[Test]
    public function la_unidad_sale_de_la_taxonomia_y_no_de_la_regla_tributaria(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw([
            'categoria' => 'FRESCOS', 'subcategoria' => 'VERDURAS',
            'marca' => '', 'descripcion' => 'Zanahoria', 'presentacion' => '1 KG',
        ]));

        $this->assertSame('KG', $fila['unidad']);
        $this->assertSame('EXONERADO', $fila['operacion']);
    }

    /* --- Clasificacion y esquema --------------------------------------- */

    #[Test]
    public function una_subcategoria_no_declarada_no_se_normaliza(): void
    {
        // Sin prefijo no hay codigo interno: la fila se rechaza en lugar de
        // recibir una categoria adivinada.
        $this->assertNull($this->normalizador()->normalizar($this->raw(['subcategoria' => 'INVENTADA'])));
    }

    #[Test]
    public function la_descripcion_completa_lo_que_la_fuente_omite(): void
    {
        $normalizador = $this->normalizador();

        $completa = $normalizador->normalizar($this->raw(['descripcion' => 'Coca Cola Original 500 ml']));
        $this->assertSame('Coca Cola Original 500 ml', $completa['descripcion']);

        $escueta = $normalizador->normalizar($this->raw(['descripcion' => 'Original']));
        $this->assertSame('Coca-Cola Original 500 ml', $escueta['descripcion']);
    }

    #[Test]
    public function el_mismo_producto_de_dos_fuentes_recibe_un_solo_codigo(): void
    {
        $normalizador = $this->normalizador();

        $tottus = $normalizador->normalizar($this->raw([
            'descripcion' => 'Coca Cola Original', 'presentacion' => '500ML',
        ]));

        $metro = $normalizador->normalizar($this->raw([
            'fuente' => 'METRO', 'marca' => 'Coca-Cola',
            'descripcion' => 'COCA-COLA ORIGINAL 500 ML', 'presentacion' => '500 ml',
            'url_fuente' => 'https://www.metro.pe/ejemplo',
        ]));

        $this->assertSame($tottus['codigo_interno'], $metro['codigo_interno']);
    }

    #[Test]
    public function el_esquema_raw_rechaza_lo_que_no_es_verificable(): void
    {
        $taxonomia = $this->normalizador()->taxonomia();

        $this->assertSame([], EsquemaRaw::validar($this->raw(), $taxonomia));

        $this->assertContains('falta url_fuente', EsquemaRaw::validar($this->raw(['url_fuente' => '']), $taxonomia));
        $this->assertContains('url_fuente debe empezar con http:// o https://', EsquemaRaw::validar($this->raw(['url_fuente' => 'tottus.com.pe']), $taxonomia));
        $this->assertContains('fecha_consulta debe ser AAAA-MM-DD', EsquemaRaw::validar($this->raw(['fecha_consulta' => '07/09/2026']), $taxonomia));
        $this->assertContains('precio_referencia no es numerico', EsquemaRaw::validar($this->raw(['precio_referencia' => 'S/ 2.90']), $taxonomia));
    }

    /**
     * Los precios dejaron de bloquear la importacion.
     *
     * Exigirlos aca, junto con la regla de que no se inventan —precio_compra
     * sale de la lista del proveedor y precio_venta lo decide el comercio—,
     * dejaba el catalogo imposible de cargar. Ahora el producto entra sin
     * precio, igual que entra sin stock, y lo que impide cobrarlo es
     * Producto::estaListoParaVender().
     */
    #[Test]
    public function una_fila_del_maestro_sin_precios_igual_se_puede_importar(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw());

        $faltantes = EsquemaMaestro::faltantesParaImportar($fila);

        $this->assertNotContains('precio_compra', $faltantes);
        $this->assertNotContains('precio_venta', $faltantes);
        $this->assertNotContains('codigo_interno', $faltantes);
        $this->assertNotContains('operacion', $faltantes);
        $this->assertSame([], $faltantes);
    }

    #[Test]
    public function el_maestro_sigue_frenando_lo_que_de_verdad_falta(): void
    {
        $fila = $this->normalizador()->normalizar($this->raw());

        // Una afectacion sin resolver si bloquea: un comprobante con el IGV que
        // nadie decidio es peor que un producto que no se puede vender todavia.
        $this->assertContains('operacion', EsquemaMaestro::faltantesParaImportar(
            array_replace($fila, ['operacion' => 'PENDIENTE'])
        ));

        $this->assertContains('descripcion', EsquemaMaestro::faltantesParaImportar(
            array_replace($fila, ['descripcion' => ''])
        ));
    }
}
