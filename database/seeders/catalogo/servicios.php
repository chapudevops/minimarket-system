<?php

/**
 * Servicios que presta la tienda.
 *
 * tipo_producto = SERVICIO: no llevan stock, y por eso su stock mínimo va en
 * cero. La unidad no es UNIDAD sino la que corresponde a cada servicio.
 */
return [
    'codigo' => 'SRV',
    'tipo' => 'SERVICIO',
    'margen' => [0.30, 0.50],
    'rotacion' => 'baja',
    'vida' => 'ninguna',

    'familias' => [
        ['sub' => 'DEL', 'marca' => 'Genérico', 'linea' => 'Servicio de Delivery Zona Cercana', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'Por pedido' => 4.00,
        ]],
        ['sub' => 'DEL', 'marca' => 'Genérico', 'linea' => 'Servicio de Delivery Zona Lejana', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'Por pedido' => 7.00,
        ]],
        ['sub' => 'DEL', 'marca' => 'Genérico', 'linea' => 'Servicio de Reparto Programado', 'unidad' => 'HORA', 'presentaciones' => [
            'Por hora' => 15.00,
        ]],
        ['sub' => 'REC', 'marca' => 'Genérico', 'linea' => 'Recarga Celular Movistar', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'S/ 10' => 9.50, 'S/ 20' => 19.00, 'S/ 30' => 28.50,
        ]],
        ['sub' => 'REC', 'marca' => 'Genérico', 'linea' => 'Recarga Celular Claro', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'S/ 10' => 9.50, 'S/ 20' => 19.00, 'S/ 30' => 28.50,
        ]],
        ['sub' => 'REC', 'marca' => 'Genérico', 'linea' => 'Recarga Celular Entel', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'S/ 10' => 9.50, 'S/ 20' => 19.00,
        ]],
        ['sub' => 'OTR', 'marca' => 'Genérico', 'linea' => 'Servicio de Fotocopias', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'Por copia' => 0.05,
        ]],
        ['sub' => 'OTR', 'marca' => 'Genérico', 'linea' => 'Servicio de Impresión', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'Por hoja' => 0.20,
        ]],
        ['sub' => 'OTR', 'marca' => 'Genérico', 'linea' => 'Alquiler de Espacio Publicitario', 'unidad' => 'MES', 'presentaciones' => [
            'Por mes' => 60.00,
        ]],
    ],
];
