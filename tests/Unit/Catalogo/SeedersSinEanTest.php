<?php

namespace Tests\Unit\Catalogo;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ningun seeder, factory ni dataset genera codigos de barras.
 *
 * Este test lee el codigo fuente a proposito. Los otros comprueban el estado de
 * la base, y eso llega tarde: para cuando el dato esta insertado, el EAN falso
 * ya existe. Aca se corta en el origen, que es donde alguien, con la mejor
 * intencion, agrega "un EAN de ejemplo" para que la demo se vea mas real.
 */
class SeedersSinEanTest extends TestCase
{
    /** @return array<int,string> */
    private function archivosDeDatos(): array
    {
        $rutas = array_merge(
            glob(base_path('database/seeders/*.php')) ?: [],
            glob(base_path('database/seeders/catalogo/*.php')) ?: [],
            glob(base_path('database/factories/*.php')) ?: [],
        );

        $this->assertNotEmpty($rutas, 'no se encontraron seeders que revisar');

        return $rutas;
    }

    #[Test]
    public function ningun_seeder_asigna_un_codigo_de_barras(): void
    {
        // El control preciso: cualquier asignacion a codigo_barras dentro de un
        // seeder tiene que ser null. Da igual como se escriba el valor —literal,
        // variable o funcion—: si no es null, alguien esta inventando un EAN.
        foreach ($this->archivosDeDatos() as $ruta) {
            preg_match_all(
                '/[\'"]codigo_barras[\'"]\s*=>\s*([^,\n]+)/',
                file_get_contents($ruta),
                $asignaciones
            );

            foreach ($asignaciones[1] as $valor) {
                $this->assertSame(
                    'null',
                    trim($valor),
                    basename($ruta)." asigna codigo_barras = {$valor}; sin fuente verificable debe ser null"
                );
            }
        }
    }

    #[Test]
    public function ningun_seeder_lleva_un_numero_con_forma_de_ean(): void
    {
        // Doce a catorce digitos es la forma de un UPC-A, un EAN-13 o un
        // GTIN-14. Ningun dato legitimo del seeder tiene esa longitud: el DNI
        // son 8, el telefono 9 y el RUC 11. Si aparece uno, es un EAN.
        foreach ($this->archivosDeDatos() as $ruta) {
            preg_match_all('/[\'"](\d{12,14})[\'"]/', file_get_contents($ruta), $encontrados);

            $this->assertEmpty(
                $encontrados[1],
                basename($ruta).' tiene numeros con forma de codigo de barras: '.implode(', ', $encontrados[1])
            );
        }
    }

    #[Test]
    public function ningun_seeder_calcula_un_digito_verificador(): void
    {
        // La otra forma de inventar un EAN: generarlo. Un EAN valido lleva
        // digito verificador, asi que quien lo calcula lo esta fabricando.
        foreach ($this->archivosDeDatos() as $ruta) {
            $contenido = strtolower(file_get_contents($ruta));

            foreach (['digito_verificador', 'checkdigit', 'check_digit', 'generarean', 'generar_ean'] as $senal) {
                $this->assertStringNotContainsString(
                    $senal,
                    $contenido,
                    basename($ruta).": parece generar codigos de barras ({$senal})"
                );
            }
        }
    }

    #[Test]
    public function el_catalogo_maestro_declara_el_codigo_de_barras_nulo(): void
    {
        // El motor del catalogo (1.000+ referencias) tiene que seguir poniendo
        // NULL explicito, no una cadena vacia ni un valor generado.
        $motor = file_get_contents(base_path('database/seeders/CatalogoMinimarket.php'));

        $this->assertMatchesRegularExpression(
            "/'codigo_barras'\s*=>\s*null/",
            $motor,
            'CatalogoMinimarket dejo de poner codigo_barras en NULL'
        );
    }

    #[Test]
    public function el_seeder_de_demo_ya_no_tiene_columna_de_codigo_de_barras(): void
    {
        $seeder = file_get_contents(base_path('database/seeders/CatalogoSeeder.php'));

        // Los 33 productos de demo perdieron la columna entera: no alcanza con
        // vaciar los valores, hay que sacar el hueco donde volverian a caer.
        $this->assertStringNotContainsString('$barras', $seeder);
        $this->assertMatchesRegularExpression("/'codigo_barras'\s*=>\s*null/", $seeder);
    }
}
