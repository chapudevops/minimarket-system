<?php

/** Cereales, avenas, café, té e infusiones, y untables de desayuno. */
return [
    'codigo' => 'DES',
    'margen' => [0.15, 0.30],
    'rotacion' => 'media',
    'vida' => 'larga',

    'familias' => [
        // ---------------- CEREALES ----------------
        ['sub' => 'CER', 'marca' => "Kellogg's", 'linea' => "Cereal Kellogg's Corn Flakes", 'presentaciones' => [
            '200 g' => 8.20, '380 g' => 14.00,
        ]],
        ['sub' => 'CER', 'marca' => "Kellogg's", 'linea' => "Cereal Kellogg's Zucaritas", 'rotacion' => 'alta', 'presentaciones' => [
            '200 g' => 8.60, '380 g' => 14.80,
        ]],
        ['sub' => 'CER', 'marca' => "Kellogg's", 'linea' => "Cereal Kellogg's Choco Krispis", 'rotacion' => 'alta', 'presentaciones' => [
            '200 g' => 8.60,
        ]],
        ['sub' => 'CER', 'marca' => 'Ángel', 'linea' => 'Cereal Ángel Zuck', 'rotacion' => 'alta', 'presentaciones' => [
            '150 g' => 4.20, '380 g' => 9.20,
        ]],
        ['sub' => 'CER', 'marca' => 'Ángel', 'linea' => 'Cereal Ángel Flakes', 'presentaciones' => [
            '150 g' => 4.00, '380 g' => 8.80,
        ]],
        ['sub' => 'CER', 'marca' => 'Ángel', 'linea' => 'Cereal Ángel Mel', 'presentaciones' => [
            '150 g' => 4.20,
        ]],
        ['sub' => 'CER', 'marca' => 'Nestlé', 'linea' => 'Cereal Nestlé Nesquik', 'presentaciones' => [
            '230 g' => 9.40,
        ]],
        ['sub' => 'CER', 'marca' => 'Nestlé', 'linea' => 'Cereal Nestlé Trix', 'rotacion' => 'baja', 'presentaciones' => [
            '230 g' => 9.60,
        ]],
        ['sub' => 'CER', 'marca' => 'Quaker', 'linea' => 'Avena Quaker Tradicional', 'rotacion' => 'alta', 'presentaciones' => [
            '170 g' => 2.10, '340 g' => 3.90, '600 g' => 6.60,
        ]],
        ['sub' => 'CER', 'marca' => 'Quaker', 'linea' => 'Avena Quaker Instantánea', 'rotacion' => 'alta', 'presentaciones' => [
            '170 g' => 2.30, '340 g' => 4.20,
        ]],
        ['sub' => 'CER', 'marca' => '3 Ositos', 'linea' => 'Avena 3 Ositos Tradicional', 'rotacion' => 'alta', 'presentaciones' => [
            '160 g' => 1.90, '380 g' => 4.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Santa Catalina', 'linea' => 'Avena Santa Catalina', 'presentaciones' => [
            '160 g' => 1.80,
        ]],
        ['sub' => 'CER', 'marca' => 'Granola Andina', 'linea' => 'Granola Andina con Miel', 'rotacion' => 'baja', 'presentaciones' => [
            '250 g' => 7.20, '500 g' => 13.00,
        ]],

        // ---------------- CAFÉ ----------------
        ['sub' => 'CAF', 'marca' => 'Nescafé', 'linea' => 'Café Nescafé Tradición', 'rotacion' => 'alta', 'presentaciones' => [
            'Sobre 2 g' => 0.40, '50 g' => 9.80, '100 g' => 17.50, '170 g' => 27.00,
        ]],
        ['sub' => 'CAF', 'marca' => 'Nescafé', 'linea' => 'Café Nescafé Kirma', 'rotacion' => 'alta', 'presentaciones' => [
            'Sobre 2 g' => 0.40, '50 g' => 9.20, '100 g' => 16.50,
        ]],
        ['sub' => 'CAF', 'marca' => 'Altomayo', 'linea' => 'Café Altomayo Instantáneo', 'rotacion' => 'alta', 'presentaciones' => [
            'Sobre 2 g' => 0.35, '50 g' => 8.60, '95 g' => 15.00, '190 g' => 27.50,
        ]],
        ['sub' => 'CAF', 'marca' => 'Altomayo', 'linea' => 'Café Altomayo Gourmet Molido', 'rotacion' => 'baja', 'presentaciones' => [
            '250 g' => 18.00,
        ]],
        ['sub' => 'CAF', 'marca' => 'Cafetal', 'linea' => 'Café Cafetal Instantáneo', 'presentaciones' => [
            '50 g' => 7.80, '100 g' => 14.00,
        ]],
        ['sub' => 'CAF', 'marca' => 'Cafetal', 'linea' => 'Café Cafetal Molido', 'rotacion' => 'baja', 'presentaciones' => [
            '250 g' => 14.50,
        ]],
        ['sub' => 'CAF', 'marca' => 'Ecco', 'linea' => 'Bebida Instantánea Ecco', 'presentaciones' => [
            '75 g' => 6.40, '150 g' => 11.50,
        ]],

        // ---------------- TÉ E INFUSIONES ----------------
        ['sub' => 'INF', 'marca' => 'McColin\'s', 'linea' => 'Té McColin\'s Puro', 'rotacion' => 'alta', 'presentaciones' => [
            'Caja 25 sobres' => 3.60, 'Caja 100 sobres' => 12.50,
        ]],
        ['sub' => 'INF', 'marca' => 'McColin\'s', 'linea' => 'Anís McColin\'s', 'presentaciones' => [
            'Caja 25 sobres' => 3.80, 'Caja 100 sobres' => 13.00,
        ]],
        ['sub' => 'INF', 'marca' => 'McColin\'s', 'linea' => 'Manzanilla McColin\'s', 'presentaciones' => [
            'Caja 25 sobres' => 3.80, 'Caja 100 sobres' => 13.00,
        ]],
        ['sub' => 'INF', 'marca' => 'Herbi', 'linea' => 'Té Herbi Puro', 'presentaciones' => [
            'Caja 25 sobres' => 3.20, 'Caja 100 sobres' => 11.00,
        ]],
        ['sub' => 'INF', 'marca' => 'Herbi', 'linea' => 'Manzanilla Herbi', 'presentaciones' => [
            'Caja 25 sobres' => 3.30,
        ]],
        ['sub' => 'INF', 'marca' => 'Hornimans', 'linea' => 'Té Hornimans Puro', 'presentaciones' => [
            'Caja 25 sobres' => 4.60, 'Caja 100 sobres' => 15.00,
        ]],
        ['sub' => 'INF', 'marca' => 'Hornimans', 'linea' => 'Anís Hornimans', 'presentaciones' => [
            'Caja 25 sobres' => 4.80,
        ]],
        ['sub' => 'INF', 'marca' => 'Hornimans', 'linea' => 'Manzanilla Hornimans', 'presentaciones' => [
            'Caja 25 sobres' => 4.80,
        ]],
        ['sub' => 'INF', 'marca' => 'Zurit', 'linea' => 'Infusión Zurit Hierba Luisa', 'rotacion' => 'baja', 'presentaciones' => [
            'Caja 25 sobres' => 3.40,
        ]],
        ['sub' => 'INF', 'marca' => 'Wawasana', 'linea' => 'Infusión Wawasana Digestión', 'rotacion' => 'baja', 'presentaciones' => [
            'Caja 20 sobres' => 5.20,
        ]],
        ['sub' => 'INF', 'marca' => 'Wawasana', 'linea' => 'Infusión Wawasana Relax', 'rotacion' => 'baja', 'presentaciones' => [
            'Caja 20 sobres' => 5.20,
        ]],

        // ---------------- UNTABLES Y COMPLEMENTOS ----------------
        ['sub' => 'UNT', 'marca' => 'Nutella', 'linea' => 'Crema de Avellanas Nutella', 'rotacion' => 'media', 'presentaciones' => [
            '140 g' => 9.80, '350 g' => 21.00,
        ]],
        ['sub' => 'UNT', 'marca' => 'Fanny', 'linea' => 'Mermelada Fanny Fresa', 'presentaciones' => [
            '260 g' => 4.60, '480 g' => 7.80,
        ]],
        ['sub' => 'UNT', 'marca' => 'Fanny', 'linea' => 'Mermelada Fanny Durazno', 'presentaciones' => [
            '260 g' => 4.60,
        ]],
        ['sub' => 'UNT', 'marca' => 'Gloria', 'linea' => 'Manjar Blanco Gloria', 'rotacion' => 'media', 'presentaciones' => [
            '400 g' => 7.40,
        ]],
        ['sub' => 'UNT', 'marca' => 'Nestlé', 'linea' => 'Leche Condensada Nestlé', 'rotacion' => 'media', 'presentaciones' => [
            '393 g' => 5.60,
        ]],
        ['sub' => 'UNT', 'marca' => 'Milo', 'linea' => 'Bebida Milo en Polvo', 'presentaciones' => [
            '200 g' => 8.40, '400 g' => 15.00,
        ]],
        ['sub' => 'UNT', 'marca' => 'Nesquik', 'linea' => 'Cocoa Nesquik en Polvo', 'presentaciones' => [
            '180 g' => 7.20, '360 g' => 13.00,
        ]],
        ['sub' => 'UNT', 'marca' => 'Sol del Cusco', 'linea' => 'Miel de Abeja Sol del Cusco', 'rotacion' => 'baja', 'presentaciones' => [
            '250 g' => 9.00, '500 g' => 16.00,
        ]],
    ],
];
