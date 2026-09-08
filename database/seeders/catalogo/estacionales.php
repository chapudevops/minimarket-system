<?php

/**
 * Productos estacionales: campaña navideña, escolar, verano e invierno.
 * Rotación baja fuera de temporada, por eso el stock mínimo es chico.
 */
return [
    'codigo' => 'EST',
    'margen' => [0.20, 0.35],
    'rotacion' => 'baja',
    'vida' => 'media',

    'familias' => [
        // ---------------- NAVIDAD ----------------
        ['sub' => 'NAV', 'marca' => "D'Onofrio", 'linea' => "Panetón D'Onofrio Tradicional", 'presentaciones' => [
            '500 g' => 12.00, '900 g' => 19.00,
        ]],
        ['sub' => 'NAV', 'marca' => 'Todinno', 'linea' => 'Panetón Todinno Tradicional', 'presentaciones' => [
            '900 g' => 17.00,
        ]],
        ['sub' => 'NAV', 'marca' => 'Gloria', 'linea' => 'Panetón Gloria', 'presentaciones' => [
            '900 g' => 16.00,
        ]],
        ['sub' => 'NAV', 'marca' => 'Bimbo', 'linea' => 'Panetón Bimbo', 'presentaciones' => [
            '900 g' => 16.50,
        ]],
        ['sub' => 'NAV', 'marca' => 'Winter', 'linea' => 'Chocolate para Taza Winter', 'presentaciones' => [
            '90 g' => 3.80, '400 g' => 14.00,
        ]],
        ['sub' => 'NAV', 'marca' => 'Sol del Cusco', 'linea' => 'Chocolate para Taza Sol del Cusco', 'presentaciones' => [
            '90 g' => 3.40,
        ]],

        // ---------------- VERANO ----------------
        ['sub' => 'VER', 'marca' => 'Nivea', 'linea' => 'Bloqueador Nivea Sun FPS 50', 'vida' => 'larga', 'presentaciones' => [
            '125 ml' => 32.00, '200 ml' => 44.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Umbrella', 'linea' => 'Bloqueador Umbrella FPS 50', 'vida' => 'larga', 'presentaciones' => [
            '120 ml' => 24.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Repelente de Insectos en Spray', 'vida' => 'larga', 'presentaciones' => [
            '120 ml' => 14.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Hielo en Bolsa', 'vida' => 'corta', 'rotacion' => 'media', 'presentaciones' => [
            'Bolsa 2 kg' => 3.00,
        ]],
        ['sub' => 'ESC', 'marca' => 'Standford', 'linea' => 'Cuaderno Standford Deluxe A4', 'vida' => 'ninguna', 'presentaciones' => [
            '100 hojas' => 7.20,
        ]],
        ['sub' => 'ESC', 'marca' => 'Artesco', 'linea' => 'Plumones Artesco x12 Colores', 'vida' => 'ninguna', 'presentaciones' => [
            'Estuche x12' => 9.40,
        ]],
        ['sub' => 'ESC', 'marca' => 'Faber-Castell', 'linea' => 'Colores Faber-Castell x12', 'vida' => 'ninguna', 'presentaciones' => [
            'Estuche x12' => 12.00, 'Estuche x24' => 21.00,
        ]],
        ['sub' => 'ESC', 'marca' => 'Genérico', 'linea' => 'Forro Plástico para Cuaderno', 'vida' => 'ninguna', 'presentaciones' => [
            'Unidad' => 1.20,
        ]],
        ['sub' => 'ESC', 'marca' => 'Artesco', 'linea' => 'Regla Artesco 30 cm', 'vida' => 'ninguna', 'presentaciones' => [
            'Unidad' => 2.40,
        ]],
        ['sub' => 'ESC', 'marca' => 'Genérico', 'linea' => 'Mochila Escolar Básica', 'vida' => 'ninguna', 'presentaciones' => [
            'Unidad' => 38.00,
        ]],
        ['sub' => 'NAV', 'marca' => 'Genérico', 'linea' => 'Canasta Navideña Básica', 'vida' => 'ninguna', 'presentaciones' => [
            'Unidad' => 85.00,
        ]],
        ['sub' => 'INV', 'marca' => 'Ambrosoli', 'linea' => 'Caramelo Ambrosoli Miel y Limón', 'presentaciones' => [
            'Bolsa 100 unidades' => 6.20,
        ]],
        ['sub' => 'INV', 'marca' => 'Genérico', 'linea' => 'Emoliente Instantáneo en Polvo', 'presentaciones' => [
            'Sobre 20 g' => 1.00, 'Caja x10 sobres' => 8.50,
        ]],
    ],
];
