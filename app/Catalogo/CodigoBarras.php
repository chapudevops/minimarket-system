<?php

namespace App\Catalogo;

/**
 * Valida codigos de barras GTIN.
 *
 * La regla del proyecto es que un EAN inventado es peor que ningun EAN: si el
 * codigo esta mal, el escaner del mostrador trae el producto equivocado y la
 * venta sale con el articulo que no era. Por eso "verificable" aca no
 * significa "vino de una web", significa que el digito verificador cierra.
 *
 * GTIN-8, GTIN-12 (UPC-A), GTIN-13 (EAN-13) y GTIN-14. El digito verificador
 * es el estandar de GS1: se suman los digitos alternando pesos 3 y 1 de
 * derecha a izquierda, y el ultimo digito completa la decena.
 *
 * Lo que NO hace: generar codigos. No existe metodo para eso a proposito.
 */
class CodigoBarras
{
    /** Largos que GS1 define. Cualquier otro largo no es un GTIN. */
    public const LARGOS_VALIDOS = [8, 12, 13, 14];

    /**
     * Largos que se aceptan para el catalogo comercial.
     *
     * GTIN-8 queda fuera aunque el digito verificador cierre. En los catalogos
     * de supermercado ese largo casi siempre es un codigo interno del retailer
     * —rostizados, pesables, combos armados en tienda— y no el codigo impreso
     * por el fabricante. Guardarlo como codigo_barras haria que el escaner de
     * OTRA tienda no encuentre nada, o peor, encuentre otra cosa.
     *
     * Se prefiere NULL: el producto se busca por SKU o por descripcion.
     */
    public const LARGOS_COMERCIALES = [12, 13, 14];

    /**
     * Prefijos GS1 de distribucion restringida.
     *
     * El rango 02 y 20-29 lo reserva GS1 para codigos internos de tienda:
     * pesables, articulos armados en gondola, etiquetas impresas en el local.
     * Validan el digito verificador pero solo significan algo dentro de ese
     * supermercado, asi que como codigo_barras de nuestro catalogo servirian
     * para que el escaner no encuentre nada o encuentre otra cosa.
     *
     * Aparecen en las fuentes: 2200202353593 en un arroz, 2050044003671 en una
     * crema de avellanas. Son etiquetas de Plaza Vea, no del fabricante.
     */
    private const PREFIJOS_INTERNOS = ['02', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29'];

    /**
     * Codigos que las fuentes publican como relleno. No son productos.
     *
     * Aparecen cuando el retailer carga un articulo propio (rostizados, pesables,
     * combos armados en tienda) y el campo EAN queda con un placeholder.
     */
    private const BASURA = ['0', '00000000', '000000000000', '0000000000000', '00000000000000'];

    /**
     * Normaliza y valida. Devuelve el GTIN limpio o null.
     *
     * null no es un error: es la respuesta correcta cuando no hay codigo
     * confiable, y el resto del pipeline la trata como "este producto se
     * vende sin escanear".
     */
    public static function normalizar(mixed $valor): ?string
    {
        $codigo = preg_replace('/\D/', '', (string) $valor) ?? '';

        if ($codigo === '' || in_array($codigo, self::BASURA, true)) {
            return null;
        }

        // Un EAN-13 con ceros a la izquierda es un UPC-A: se conserva tal cual
        // llego, porque el escaner lee lo que esta impreso, no la forma corta.
        if (! in_array(strlen($codigo), self::LARGOS_VALIDOS, true)) {
            return null;
        }

        return self::digitoVerificadorCorrecto($codigo) ? $codigo : null;
    }

    public static function esValido(mixed $valor): bool
    {
        return self::normalizar($valor) !== null;
    }

    /**
     * El GTIN que se guarda en productos.codigo_barras, o null.
     *
     * Mas estricto que normalizar(): descarta ademas los GTIN-8, que en estas
     * fuentes son codigos internos de tienda.
     */
    public static function paraCatalogo(mixed $valor): ?string
    {
        $gtin = self::normalizar($valor);

        if ($gtin === null || ! in_array(strlen($gtin), self::LARGOS_COMERCIALES, true)) {
            return null;
        }

        if (in_array(substr(str_pad($gtin, 13, '0', STR_PAD_LEFT), 0, 2), self::PREFIJOS_INTERNOS, true)) {
            return null;
        }

        return $gtin;
    }

    /**
     * Pais de origen segun el prefijo GS1, solo para el reporte.
     *
     * Es informativo y no decide nada: un producto importado con prefijo
     * chileno se vende igual en Lima.
     */
    public static function origen(?string $gtin): ?string
    {
        if ($gtin === null || strlen($gtin) < 3) {
            return null;
        }

        $prefijo = (int) substr(str_pad($gtin, 13, '0', STR_PAD_LEFT), 0, 3);

        return match (true) {
            $prefijo === 775                       => 'Perú',
            $prefijo >= 780 && $prefijo <= 780     => 'Chile',
            $prefijo >= 770 && $prefijo <= 771     => 'Colombia',
            $prefijo === 779                       => 'Argentina',
            $prefijo === 786                       => 'Ecuador',
            $prefijo === 750                       => 'México',
            $prefijo === 789 || $prefijo === 790   => 'Brasil',
            $prefijo === 740                       => 'Uruguay',
            $prefijo >= 0 && $prefijo <= 139       => 'EE.UU. / Canadá',
            $prefijo >= 400 && $prefijo <= 440     => 'Alemania',
            $prefijo >= 690 && $prefijo <= 699     => 'China',
            default                                => 'otro',
        };
    }

    /** Suma alternada de GS1: pesos 3 y 1 desde la derecha. */
    private static function digitoVerificadorCorrecto(string $codigo): bool
    {
        $digitos = array_map('intval', str_split($codigo));
        $verificador = array_pop($digitos);

        $suma = 0;
        $peso = 3;

        foreach (array_reverse($digitos) as $digito) {
            $suma += $digito * $peso;
            $peso = $peso === 3 ? 1 : 3;
        }

        return (10 - ($suma % 10)) % 10 === $verificador;
    }
}
