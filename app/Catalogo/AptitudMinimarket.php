<?php

namespace App\Catalogo;

/**
 * Decide si una fila del RAW pertenece al surtido de un minimarket.
 *
 * Los supermercados de los que sale el RAW tambien venden secadoras de pelo,
 * televisores y muebles. Esos productos entran por la misma gondola que el
 * shampoo y no tienen nada que hacer en un minimarket.
 *
 * La regla central NO es buscar palabras en la descripcion. Eso da falsos
 * positivos inmediatos: "Vino Cono Sur Bicicleta Reserva" no es una bicicleta,
 * es una marca de vino. Lo que decide es la palabra JUNTO CON la gondola:
 *
 *   - En una categoria de alimentos y bebidas, una palabra de electrodomestico
 *     casi siempre es parte de un nombre comercial -> APTO.
 *   - En una categoria de no alimentos, es el producto de verdad -> FUERA.
 *
 * Ante la duda se devuelve REVISAR y decide una persona. Nunca se descarta en
 * silencio: el reporte lista todo lo que quedo afuera y por que.
 */
class AptitudMinimarket
{
    public const APTO = 'APTO_MINIMARKET';
    public const REVISAR = 'REVISAR';
    public const FUERA = 'FUERA_DE_ALCANCE';

    /**
     * Categorias donde lo que se vende se come o se bebe.
     *
     * En estas, una palabra como "bicicleta" o "monitor" es marca o linea
     * comercial, no un articulo de bazar.
     */
    private const CATEGORIAS_COMESTIBLES = [
        'BEBIDAS', 'LICORES', 'ABARROTES', 'LACTEOS', 'GALLETAS Y DULCES',
        'SNACKS', 'PANADERIA', 'FRESCOS',
    ];

    /** Articulos que un minimarket no vende, en gondolas de no alimentos. */
    private const ELECTRODOMESTICOS = [
        'televisor', 'smart tv', 'laptop', 'notebook', 'celular', 'smartphone',
        'refrigerador', 'refrigeradora', 'congeladora', 'lavadora', 'secadora',
        'alisadora', 'planchita', 'licuadora', 'batidora', 'aspiradora',
        'microondas', 'cafetera electrica', 'hervidor', 'freidora',
        'impresora', 'monitor', 'tablet', 'audifonos', 'parlante', 'consola',
        'taladro', 'soldadora', 'bicicleta', 'patineta',
        'colchon', 'sofa', 'mueble', 'ropero',
    ];

    /** Formatos que son de mayoreo, no de gondola. */
    private const MAYOREO = ['bulto', 'saco x', 'fardo', 'display x'];

    /**
     * Senales de que el producto es PARA el electrodomestico, no el aparato.
     *
     * "Quitamanchas de Colchones" y "Limpia Lavadoras" son productos de
     * limpieza perfectamente normales en un minimarket. Cuando aparece una de
     * estas palabras la fila va a REVISAR y decide una persona, en vez de
     * descartarse sola.
     */
    private const ES_PARA_EL_APARATO = [
        'limpia', 'limpiador', 'quitamanchas', 'desinfectante', 'abrillantador',
        'repuesto', 'accesorio', 'filtro', 'bolsa para', 'funda',
    ];

    private string $motivo = '';

    /** @param array<string,mixed> $fila fila RAW */
    public function evaluar(array $fila, ?Taxonomia $taxonomia = null): string
    {
        $this->motivo = '';

        $categoria = strtoupper(trim((string) ($fila['categoria'] ?? '')));
        $subcategoria = strtoupper(trim((string) ($fila['subcategoria'] ?? '')));

        // 1. Una subcategoria que no esta en nuestra taxonomia no es parte del
        //    surtido: no hay donde clasificarla ni como codificarla.
        if ($taxonomia !== null && $taxonomia->prefijo($categoria, $subcategoria) === null) {
            $this->motivo = "subcategoría no declarada en la taxonomía ({$categoria} / {$subcategoria})";

            return self::FUERA;
        }

        $texto = Texto::plano(($fila['descripcion'] ?? '').' '.($fila['presentacion'] ?? ''));

        // 2. Electrodomestico en gondola de no alimentos.
        $comestible = in_array($categoria, self::CATEGORIAS_COMESTIBLES, true);

        foreach (self::ELECTRODOMESTICOS as $articulo) {
            // Palabra completa, admitiendo solo el plural: sin esto "tabletas"
            // cazaba por "tablet" y un medicamento quedaba clasificado como
            // electronica, con un motivo que ademas era falso.
            if (! preg_match('/\b'.preg_quote($articulo, '/').'(?:es|s)?\b/u', $texto)) {
                continue;
            }

            if ($comestible) {
                // En alimentos la palabra es marca: "Cono Sur Bicicleta" no
                // necesita revision humana.
                break;
            }

            foreach (self::ES_PARA_EL_APARATO as $senal) {
                if (str_contains($texto, $senal)) {
                    $this->motivo = "menciona un electrodoméstico ({$articulo}) pero parece un producto para él";

                    return self::REVISAR;
                }
            }

            $this->motivo = "artículo de bazar o electrodoméstico ({$articulo})";

            return self::FUERA;
        }

        // 3. Formato de mayoreo: puede servir o no, lo mira una persona.
        foreach (self::MAYOREO as $formato) {
            if (str_contains($texto, $formato)) {
                $this->motivo = "formato de mayoreo ({$formato})";

                return self::REVISAR;
            }
        }

        return self::APTO;
    }

    /** Por que quedo fuera o a revisar. Vacio cuando es APTO. */
    public function motivo(): string
    {
        return $this->motivo;
    }
}
