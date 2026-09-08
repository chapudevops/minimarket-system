<?php

namespace App\Catalogo;

use Illuminate\Support\Str;

/**
 * Lleva la presentacion publicada por la fuente a una forma unica.
 *
 *   "500ML", "500 ml", "500 Ml", "500 mililitros"  ->  "500 ml"
 *   "1,5L", "1.5 LT", "1.50 litros"                ->  "1.5 L"
 *   "PACK X 6", "6 PACK", "pack de 6"              ->  "pack x6"
 *
 * Sin esto la deteccion de duplicados no sirve: cada supermercado escribe el
 * mismo envase de una manera distinta.
 */
class NormalizadorPresentacion
{
    /**
     * Abreviaturas aceptadas -> simbolo canonico.
     *
     * El litro va en "L" mayuscula (SI lo permite justamente para no
     * confundirlo con el digito 1) y el resto en minuscula, que es como ya
     * estan escritas las presentaciones del catalogo existente.
     */
    public const UNIDADES = [
        'ml' => 'ml', 'mls' => 'ml', 'mililitro' => 'ml', 'mililitros' => 'ml', 'cc' => 'ml',
        'l' => 'L', 'lt' => 'L', 'lts' => 'L', 'ltr' => 'L', 'litro' => 'L', 'litros' => 'L',
        'g' => 'g', 'gr' => 'g', 'grs' => 'g', 'grm' => 'g', 'gramo' => 'g', 'gramos' => 'g',
        'kg' => 'kg', 'kgs' => 'kg', 'kilo' => 'kg', 'kilos' => 'kg',
        'kilogramo' => 'kg', 'kilogramos' => 'kg',
        'mg' => 'mg',
        'un' => 'un', 'und' => 'un', 'unds' => 'un', 'unid' => 'un', 'unids' => 'un',
        'unidad' => 'un', 'unidades' => 'un', 'u' => 'un',
        'rollo' => 'rollos', 'rollos' => 'rollos',
        'sobre' => 'sobres', 'sobres' => 'sobres',
        'hoja' => 'hojas', 'hojas' => 'hojas',
        'capsula' => 'capsulas', 'capsulas' => 'capsulas',
    ];

    /** Palabras de envase que se conservan tal cual, en minuscula. */
    public const ENVASES = [
        'botella', 'lata', 'bolsa', 'caja', 'pack', 'sachet', 'frasco', 'doypack',
        'tetrapak', 'vaso', 'pote', 'barra', 'tubo', 'bandeja', 'paquete', 'display',
        'bidon', 'galonera', 'estuche', 'blister', 'six', 'twelve', 'jaba', 'plancha',
        'retornable', 'descartable', 'vidrio', 'plastico',
    ];

    /** Ruido habitual de las fichas de supermercado. */
    private const RUIDO = ['aprox', 'aproximadamente', 'contenido', 'neto', 'presentacion', 'formato'];

    /**
     * @return string|null null cuando la fuente no publica presentacion:
     *                     preferimos el vacio antes que inventar un envase.
     */
    public function normalizar(?string $presentacion): ?string
    {
        $valor = Str::lower(Str::ascii((string) $presentacion));

        if (trim($valor) === '') {
            return null;
        }

        // Coma decimal peruana y simbolo de multiplicacion tipografico.
        $valor = preg_replace('/(\d),(\d)/', '$1.$2', $valor);
        $valor = str_replace(['x', '×'], ['x', 'x'], $valor);

        // Punto que no separa decimales ("kg." , "lt.") -> espacio.
        $valor = preg_replace('/\.(?!\d)/', ' ', $valor);
        $valor = preg_replace('/[^a-z0-9.]+/', ' ', $valor);

        // "6 pack" y "pack de 6" convergen en "pack x6".
        $envases = implode('|', self::ENVASES);
        $valor = preg_replace('/\b(\d+)\s*(' . $envases . ')\b/', '$2 x$1', $valor);
        $valor = preg_replace('/\b(' . $envases . ')\s+de\s+(\d+)\b/', '$1 x$2', $valor);

        // "6x355 ml" -> "x6 355 ml": el multiplicador siempre va delante.
        // El lookbehind evita partir un decimal ("1.5 x 2").
        $valor = preg_replace('/(?<![\d.])(\d+)\s*x(?=\s*\d)/', 'x$1 ', $valor);

        // Multiplicador pegado al numero: "x 6" -> "x6".
        $valor = preg_replace('/\bx\s*(\d)/', 'x$1', $valor);

        $valor = $this->unirNumeroConUnidad($valor);

        $palabras = array_filter(
            preg_split('/\s+/', trim($valor), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn (string $palabra) => ! in_array($palabra, self::RUIDO, true) && $palabra !== 'de'
        );

        $limpio = trim(implode(' ', $palabras));

        return $limpio === '' ? null : $limpio;
    }

    /**
     * Aplica solo la regla de numero + unidad sobre un texto cualquiera.
     *
     * Lo usa la descripcion del producto: "Coca Cola 500 Ml" tiene que salir
     * "Coca Cola 500 ml" igual que la columna presentacion, o el catalogo
     * escribe la misma unidad de dos formas.
     */
    public function canonizarUnidades(string $texto): string
    {
        return $this->unirNumeroConUnidad($texto);
    }

    /** Clave de comparacion de la presentacion ya normalizada. */
    public function clave(?string $presentacion): string
    {
        return Texto::clave($this->normalizar($presentacion));
    }

    /**
     * "500ml" -> "500 ml"; ademas recorta ceros sobrantes ("1.50" -> "1.5").
     * Lo que no reconoce lo deja intacto: preferimos no tocar antes que
     * adivinar una unidad.
     */
    private function unirNumeroConUnidad(string $valor): string
    {
        return preg_replace_callback(
            '/(\d+(?:\.\d+)?)\s*([A-Za-z]+)/',
            function (array $m): string {
                $unidad = self::UNIDADES[strtolower($m[2])] ?? null;

                if ($unidad === null) {
                    return $m[0];
                }

                return $this->numero($m[1]) . ' ' . $unidad;
            },
            $valor
        );
    }

    private function numero(string $numero): string
    {
        if (! str_contains($numero, '.')) {
            return $numero;
        }

        $numero = rtrim(rtrim($numero, '0'), '.');

        return $numero === '' ? '0' : $numero;
    }
}
