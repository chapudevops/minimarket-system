<?php

/**
 * Carnes, pollo y pescado. Casi todo se vende por peso, así que la unidad es
 * KG salvo las presentaciones ya empacadas.
 */
return [
    'codigo' => 'CAR',
    'margen' => [0.12, 0.25],
    'rotacion' => 'alta',
    'vida' => 'corta',

    'familias' => [
        // ---------------- POLLO ----------------
        ['sub' => 'POL', 'marca' => 'San Fernando', 'linea' => 'Pollo Entero San Fernando', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 9.20,
        ]],
        ['sub' => 'POL', 'marca' => 'San Fernando', 'linea' => 'Pechuga de Pollo San Fernando', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 14.50,
        ]],
        ['sub' => 'POL', 'marca' => 'San Fernando', 'linea' => 'Pierna de Pollo San Fernando', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 10.80,
        ]],
        ['sub' => 'POL', 'marca' => 'San Fernando', 'linea' => 'Alas de Pollo San Fernando', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 11.00,
        ]],
        ['sub' => 'POL', 'marca' => 'Redondos', 'linea' => 'Pollo Entero Redondos', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 9.00,
        ]],
        ['sub' => 'POL', 'marca' => 'Genérico', 'linea' => 'Menudencia de Pollo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 6.40,
        ]],

        // ---------------- CARNE DE RES ----------------
        ['sub' => 'RES', 'marca' => 'Genérico', 'linea' => 'Carne Molida de Res', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 19.00,
        ]],
        ['sub' => 'RES', 'marca' => 'Genérico', 'linea' => 'Bistec de Res', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 24.00,
        ]],
        ['sub' => 'RES', 'marca' => 'Genérico', 'linea' => 'Lomo Fino de Res', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 38.00,
        ]],
        ['sub' => 'RES', 'marca' => 'Genérico', 'linea' => 'Asado de Res', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 22.00,
        ]],
        ['sub' => 'RES', 'marca' => 'Genérico', 'linea' => 'Hueso de Res para Caldo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 7.00,
        ]],

        // ---------------- CERDO ----------------
        ['sub' => 'CER', 'marca' => 'Genérico', 'linea' => 'Chuleta de Cerdo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 16.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Genérico', 'linea' => 'Panceta de Cerdo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 15.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Genérico', 'linea' => 'Pierna de Cerdo', 'unidad' => 'KG', 'rotacion' => 'baja', 'presentaciones' => [
            'Por kilogramo' => 17.00,
        ]],

        // ---------------- PESCADO ----------------
        ['sub' => 'PES', 'marca' => 'Genérico', 'linea' => 'Filete de Merluza', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 16.00,
        ]],
        ['sub' => 'PES', 'marca' => 'Genérico', 'linea' => 'Bonito Fresco', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 13.00,
        ]],
        ['sub' => 'PES', 'marca' => 'Genérico', 'linea' => 'Jurel Fresco', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 10.00,
        ]],
        ['sub' => 'PES', 'marca' => 'Genérico', 'linea' => 'Trucha Entera', 'unidad' => 'KG', 'rotacion' => 'baja', 'presentaciones' => [
            'Por kilogramo' => 21.00,
        ]],
    ],
];
