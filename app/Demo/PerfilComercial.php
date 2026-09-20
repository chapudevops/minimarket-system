<?php

namespace App\Demo;

/**
 * Reglas comerciales del escenario DEMO: cuanto margen deja cada familia y
 * con que frecuencia rota.
 *
 * CIFRAS SIMULADAS. Son rangos plausibles para un minimarket peruano, no
 * precios ni margenes observados de ninguna tienda concreta. No sirven como
 * referencia comercial.
 *
 * El margen se expresa SOBRE LA VENTA NETA, no como markup sobre el costo:
 *
 *     margen = (venta_neta - costo) / venta_neta
 *
 * Y la venta neta sale de desagregar el precio de gondola, porque el sistema
 * guarda el precio de venta con IGV incluido y el de compra sin el. Comparar
 * uno con otro sin desagregar infla el margen 18 puntos.
 */
class PerfilComercial
{
    /**
     * Margen neto por categoria: [minimo, maximo].
     *
     * La logica detras de los rangos:
     *   - lo que el cliente compara de memoria (arroz, aceite, gaseosa) deja
     *     poco: el precio lo pone el mercado;
     *   - lo pequeño y de impulso (golosinas, snacks) deja mas;
     *   - higiene y limpieza quedan en medio;
     *   - los licores rotan poco pero aguantan margen.
     */
    private const MARGENES = [
        'ABARROTES'        => [0.18, 0.24],
        'BEBIDAS'          => [0.19, 0.25],
        'LACTEOS'          => [0.18, 0.23],
        'LICORES'          => [0.22, 0.30],
        'GALLETAS Y DULCES' => [0.28, 0.34],
        'SNACKS'           => [0.28, 0.34],
        'LIMPIEZA'         => [0.24, 0.30],
        'HIGIENE PERSONAL' => [0.25, 0.32],
        'FRESCOS'          => [0.16, 0.22],
    ];

    private const MARGEN_DEFECTO = [0.22, 0.28];

    /** Subcategorias muy competidas: el margen se va al suelo del rango. */
    private const MUY_COMPETIDAS = [
        'ACEITES', 'AZUCAR', 'ARROZ', 'FIDEOS', 'LECHE', 'GASEOSAS', 'AGUA',
    ];

    /**
     * Rotacion. Un minimarket vive de pocos productos: la regla de Pareto no
     * es un adorno, es lo que hace que el panel de "mas vendidos" signifique
     * algo y que el stock bajo aparezca donde tiene que aparecer.
     */
    public const ALTA = 'ALTA_ROTACION';
    public const MEDIA = 'MEDIA_ROTACION';
    public const BAJA = 'BAJA_ROTACION';

    /** Reparto de productos por nivel de rotacion. */
    public const REPARTO_ROTACION = [
        self::ALTA => 0.20,
        self::MEDIA => 0.35,
        self::BAJA => 0.45,
    ];

    /**
     * Peso relativo al sortear que producto entra en un ticket. El 20% de
     * alta rotacion se lleva la mayor parte del movimiento.
     */
    public const PESO_ROTACION = [
        self::ALTA => 60,
        self::MEDIA => 8,
        self::BAJA => 2,
    ];

    /**
     * Margen neto para un producto concreto.
     *
     * Es DETERMINISTA: el mismo producto da siempre el mismo margen, porque
     * se deriva de su id y no de un random. Reejecutar la simulacion con la
     * misma semilla reproduce los mismos precios.
     */
    public static function margen(int $productoId, ?string $categoria, ?string $subcategoria): float
    {
        [$min, $max] = self::MARGENES[$categoria] ?? self::MARGEN_DEFECTO;

        if (in_array($subcategoria, self::MUY_COMPETIDAS, true)) {
            // Se queda en el tercio bajo del rango de su familia.
            $max = $min + ($max - $min) * 0.33;
        }

        // Posicion estable dentro del rango, distinta para cada producto.
        $posicion = (crc32('margen:'.$productoId) % 1000) / 1000;

        return round($min + ($max - $min) * $posicion, 4);
    }

    /**
     * Unidades de UN producto dentro de UN ticket.
     *
     * En un minimarket casi siempre se lleva una unidad de cada cosa: no es
     * un mayorista. Se permite algo mas en lo que se compra de a varios
     * (gaseosas, galletas, yogures), pero el grueso es 1.
     *
     * El volumen del dia no sale de aqui sino del numero de tickets: mezclar
     * las dos cosas daba tickets de once unidades y S/ 100, que no se parece
     * a nada.
     */
    public static function unidadesPorLinea(string $rotacion): int
    {
        $r = mt_rand(1, 100);

        if ($rotacion === self::ALTA) {
            return match (true) {
                $r <= 55 => 1,
                $r <= 85 => 2,
                $r <= 96 => 3,
                default => mt_rand(4, 6),
            };
        }

        return match (true) {
            $r <= 78 => 1,
            $r <= 95 => 2,
            default => 3,
        };
    }

    /** Unidades a comprar por producto en una entrada de mercaderia. */
    public static function unidadesDeCompra(string $rotacion): int
    {
        return match ($rotacion) {
            self::ALTA => mt_rand(55, 95),
            self::MEDIA => mt_rand(18, 38),
            default => mt_rand(6, 14),
        };
    }
}
