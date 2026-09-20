<?php

namespace App\Demo;

/**
 * Clientes DEMO para poder enseñar busqueda, historial y ventas identificadas.
 *
 * Nombres y documentos INVENTADOS. Los DNI usan el bloque 70000xxx y los RUC
 * el 20777001xx, que no corresponden a ninguna persona ni empresa real.
 *
 * La mayoria de tickets de un minimarket van a CLIENTE VARIOS: eso es lo
 * normal y la simulacion lo respeta, estos son la minoria identificada.
 */
class ClientesDemo
{
    public const DOC_GENERICO = '00000000';

    public const CATALOGO = [
        ['tipo' => 'DNI', 'doc' => self::DOC_GENERICO, 'nombre' => 'CLIENTE VARIOS', 'direccion' => 'Venta al publico'],
        ['tipo' => 'DNI', 'doc' => '70000101', 'nombre' => 'Rosa Quispe Mamani', 'direccion' => 'Jr. Puno 456'],
        ['tipo' => 'DNI', 'doc' => '70000102', 'nombre' => 'Carlos Huaman Rojas', 'direccion' => 'Av. Javier Prado 890'],
        ['tipo' => 'DNI', 'doc' => '70000103', 'nombre' => 'Maria Elena Torres Vega', 'direccion' => 'Calle Los Cedros 122'],
        ['tipo' => 'DNI', 'doc' => '70000104', 'nombre' => 'Jorge Antonio Salazar Diaz', 'direccion' => 'Av. La Fontana 780'],
        ['tipo' => 'DNI', 'doc' => '70000105', 'nombre' => 'Ana Lucia Ramirez Soto', 'direccion' => 'Jr. Las Magnolias 45'],
        ['tipo' => 'RUC', 'doc' => '20777001010', 'nombre' => 'BODEGA LA ESPERANZA DEMO E.I.R.L.', 'direccion' => 'Av. Industrial 455, Ate'],
        ['tipo' => 'RUC', 'doc' => '20777001020', 'nombre' => 'RESTAURANTE EL BUEN SABOR DEMO S.A.C.', 'direccion' => 'Jr. Union 234, Cercado'],
    ];
}
