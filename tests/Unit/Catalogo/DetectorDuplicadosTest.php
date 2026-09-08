<?php

namespace Tests\Unit\Catalogo;

use App\Catalogo\ClaveProducto;
use App\Catalogo\DetectorDuplicados;
use App\Catalogo\NormalizadorMarca;
use App\Catalogo\NormalizadorPresentacion;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El mismo producto llega desde tres supermercados con tres redacciones. Si el
 * detector no lo agrupa, el minimarket termina con tres codigos internos para
 * una sola botella.
 */
class DetectorDuplicadosTest extends TestCase
{
    private function detector(): DetectorDuplicados
    {
        $marcas = new NormalizadorMarca(['cocacola' => 'Coca-Cola', 'incakola' => 'Inca Kola']);

        return new DetectorDuplicados(new ClaveProducto($marcas, new NormalizadorPresentacion()));
    }

    private function fila(string $fuente, ?string $marca, string $descripcion, ?string $presentacion): array
    {
        return compact('fuente', 'marca', 'descripcion', 'presentacion');
    }

    #[Test]
    public function agrupa_el_mismo_producto_escrito_por_tres_fuentes(): void
    {
        $detector = $this->detector();

        $detector->agregarTodas([
            $this->fila('TOTTUS',   'Coca Cola', 'Coca Cola Original', '500ML'),
            $this->fila('METRO',    'Coca-Cola', 'COCA-COLA ORIGINAL', '500 ml'),
            $this->fila('PLAZAVEA', 'COCA COLA', 'coca cola original', '500 Ml'),
        ]);

        $this->assertSame(3, $detector->totalFilas());
        $this->assertSame(1, $detector->totalUnicos());
        $this->assertSame(2, $detector->totalRepetidas());

        // La que se conserva es la primera que llego.
        $this->assertSame('TOTTUS', $detector->unicos()[0]['fuente']);
    }

    #[Test]
    public function la_presentacion_distingue_productos_distintos(): void
    {
        $detector = $this->detector();

        $detector->agregarTodas([
            $this->fila('METRO', 'Coca-Cola', 'Coca-Cola Original', '500 ml'),
            $this->fila('METRO', 'Coca-Cola', 'Coca-Cola Original', '1.5 L'),
            $this->fila('METRO', 'Coca-Cola', 'Coca-Cola Original', '3 L'),
        ]);

        $this->assertSame(3, $detector->totalUnicos());
        $this->assertSame(0, $detector->totalRepetidas());
        $this->assertSame([], $detector->repetidos());
    }

    #[Test]
    public function la_marca_distingue_productos_distintos(): void
    {
        $detector = $this->detector();

        $detector->agregarTodas([
            $this->fila('METRO', 'Coca-Cola', 'Gaseosa 500 ml', '500 ml'),
            $this->fila('METRO', 'Inca Kola', 'Gaseosa 500 ml', '500 ml'),
        ]);

        $this->assertSame(2, $detector->totalUnicos());
    }

    #[Test]
    public function ignora_que_una_fuente_repita_marca_y_envase_en_la_descripcion(): void
    {
        $detector = $this->detector();

        // Metro publica el nombre completo; Tottus separa las columnas. Es el
        // duplicado mas habitual entre catalogos y hay que cazarlo.
        $detector->agregarTodas([
            $this->fila('METRO',  'Coca-Cola', 'Coca-Cola Original 500 ml', '500 ml'),
            $this->fila('TOTTUS', 'Coca-Cola', 'Original',                  '500ML'),
        ]);

        $this->assertSame(1, $detector->totalUnicos());
    }

    #[Test]
    public function el_reporte_dice_cual_se_conserva_y_cual_se_repite(): void
    {
        $detector = $this->detector();

        $detector->agregarTodas([
            $this->fila('TOTTUS', 'Coca-Cola', 'Coca-Cola Original', '500 ml'),
            $this->fila('METRO',  'COCA COLA', 'coca cola original', '500ML'),
        ]);

        $repetidos = $detector->repetidos();

        $this->assertCount(1, $repetidos);

        $grupo = array_values($repetidos)[0];

        $this->assertCount(2, $grupo);
        $this->assertSame('TOTTUS', $grupo[0]['fuente']);
        $this->assertSame('METRO', $grupo[1]['fuente']);
    }

    #[Test]
    public function marca_como_sospechosas_las_descripciones_que_no_colapsan_solas(): void
    {
        $detector = $this->detector();

        // "Gaseosa" de mas hace que las claves no coincidan. Adivinar que esa
        // palabra sobra seria inventar; se reportan para revision manual.
        $detector->agregarTodas([
            $this->fila('TOTTUS', 'Coca-Cola', 'Gaseosa Coca Cola Original', '500 ml'),
            $this->fila('METRO',  'COCA COLA', 'Coca-Cola Original',         '500ML'),
        ]);

        $this->assertSame(2, $detector->totalUnicos());
        $this->assertSame(0, $detector->totalRepetidas());

        $sospechosos = $detector->sospechosos();

        $this->assertCount(1, $sospechosos);
        $this->assertCount(2, array_values($sospechosos)[0]);
    }

    #[Test]
    public function no_sospecha_de_productos_de_distinta_presentacion(): void
    {
        $detector = $this->detector();

        $detector->agregarTodas([
            $this->fila('METRO', 'Coca-Cola', 'Coca-Cola Original', '500 ml'),
            $this->fila('METRO', 'Coca-Cola', 'Coca-Cola Original', '1.5 L'),
        ]);

        $this->assertSame([], $detector->sospechosos());
    }

    #[Test]
    public function sin_marca_no_se_levantan_sospechas(): void
    {
        $detector = $this->detector();

        // Los productos a granel no traen marca: agrupar por presentacion sola
        // juntaria medio catalogo y el reporte dejaria de servir.
        $detector->agregarTodas([
            $this->fila('METRO', null, 'Zanahoria', '1 kg'),
            $this->fila('METRO', null, 'Papa Blanca', '1 kg'),
        ]);

        $this->assertSame([], $detector->sospechosos());
    }

    #[Test]
    public function reconoce_una_fila_ya_vista(): void
    {
        $detector = $this->detector();
        $fila = $this->fila('TOTTUS', 'Coca-Cola', 'Coca-Cola Original', '500 ml');

        $this->assertFalse($detector->esRepeticion($fila));

        $detector->agregar($fila);

        $this->assertTrue($detector->esRepeticion(
            $this->fila('METRO', 'COCA COLA', 'COCA COLA ORIGINAL', '500ML')
        ));
    }
}
