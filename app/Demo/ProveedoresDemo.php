<?php

namespace App\Demo;

/**
 * Proveedores DEMO, uno por familia de producto.
 *
 * Los nombres son INVENTADOS y no corresponden a ninguna empresa real. Los RUC
 * empiezan por 20 (persona juridica, como exige la estructura peruana) pero
 * siguen con 7770000xx, un bloque que no se asigna: son identificadores de
 * prueba y no deben atribuirse a ningun contribuyente.
 */
class ProveedoresDemo
{
    /** Categorias que surte cada proveedor. */
    public const CATALOGO = [
        [
            'ruc' => '20777000011', 'nombre' => 'DISTRIBUIDORA ANDES BEBIDAS DEMO S.A.C.',
            'direccion' => 'Av. Los Frutales 220, Ate', 'telefono' => '013000011',
            'familias' => ['BEBIDAS'],
        ],
        [
            'ruc' => '20777000022', 'nombre' => 'ABARROTES DEL VALLE DEMO E.I.R.L.',
            'direccion' => 'Mercado Mayorista, Santa Anita', 'telefono' => '013000022',
            'familias' => ['ABARROTES'],
        ],
        [
            'ruc' => '20777000033', 'nombre' => 'LACTEOS Y FRESCOS DEMO S.A.C.',
            'direccion' => 'Av. Separadora Industrial 1200, Ate', 'telefono' => '013000033',
            'familias' => ['LACTEOS', 'FRESCOS'],
        ],
        [
            'ruc' => '20777000044', 'nombre' => 'LIMPIEZA TOTAL DEMO S.R.L.',
            'direccion' => 'Jr. Industrial 450, Los Olivos', 'telefono' => '013000044',
            'familias' => ['LIMPIEZA'],
        ],
        [
            'ruc' => '20777000055', 'nombre' => 'CUIDADO PERSONAL DEMO S.A.C.',
            'direccion' => 'Av. Universitaria 3300, San Miguel', 'telefono' => '013000055',
            'familias' => ['HIGIENE PERSONAL'],
        ],
        [
            'ruc' => '20777000066', 'nombre' => 'GOLOSINAS Y SNACKS DEMO E.I.R.L.',
            'direccion' => 'Av. Colonial 1820, Callao', 'telefono' => '013000066',
            'familias' => ['GALLETAS Y DULCES', 'SNACKS'],
        ],
        [
            'ruc' => '20777000077', 'nombre' => 'LICORES SELECTOS DEMO S.A.C.',
            'direccion' => 'Av. Nicolas Ayllon 2900, Ate', 'telefono' => '013000077',
            'familias' => ['LICORES'],
        ],
    ];

    /** Proveedor que surte una categoria; el de abarrotes hace de comodin. */
    public static function paraCategoria(string $categoria): string
    {
        foreach (self::CATALOGO as $p) {
            if (in_array($categoria, $p['familias'], true)) {
                return $p['ruc'];
            }
        }

        return '20777000022';
    }
}
