<?php

/**
 * Congelados, helados, frutas y verduras, y productos a granel.
 *
 * Frutas, verduras y granel van con unidad KG: el sistema los vende por peso.
 */
return [
    'codigo' => 'FRE',
    'margen' => [0.18, 0.35],
    'rotacion' => 'alta',
    'vida' => 'corta',

    'familias' => [
        // ---------------- HELADOS ----------------
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Sublime Cono", 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 2.60,
        ]],
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Donofrito Paleta", 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 1.80,
        ]],
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Frío Rico Paleta", 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 1.60,
        ]],
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Sandwich", 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 2.80,
        ]],
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Vasito Vainilla", 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 2.40,
        ]],
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Peziduri Pote", 'vida' => 'media', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 16.00,
        ]],
        ['sub' => 'HEL', 'marca' => 'Artika', 'linea' => 'Helado Artika Paleta de Fresa', 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 1.40,
        ]],
        ['sub' => 'HEL', 'marca' => 'Artika', 'linea' => 'Helado Artika Pote Vainilla', 'vida' => 'media', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 12.00, '1.8 L' => 21.00,
        ]],
        ['sub' => 'HEL', 'marca' => 'Yámboly', 'linea' => 'Helado Yámboly Paleta Chocolate', 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 1.30,
        ]],
        ['sub' => 'HEL', 'marca' => 'Yámboly', 'linea' => 'Helado Yámboly Pote Surtido', 'vida' => 'media', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 11.00,
        ]],

        // ---------------- CONGELADOS ----------------
        ['sub' => 'CGL', 'marca' => 'San Fernando', 'linea' => 'Nuggets San Fernando de Pollo', 'vida' => 'media', 'presentaciones' => [
            '300 g' => 11.00, '600 g' => 20.00,
        ]],
        ['sub' => 'CGL', 'marca' => 'San Fernando', 'linea' => 'Hamburguesa San Fernando de Pollo', 'vida' => 'media', 'presentaciones' => [
            'Paquete x4' => 10.50,
        ]],
        ['sub' => 'CGL', 'marca' => 'Otto Kunz', 'linea' => 'Hamburguesa Otto Kunz de Carne', 'vida' => 'media', 'presentaciones' => [
            'Paquete x4' => 12.00,
        ]],
        ['sub' => 'CGL', 'marca' => 'McCain', 'linea' => 'Papas Prefritas McCain Bastón', 'vida' => 'media', 'rotacion' => 'media', 'presentaciones' => [
            '750 g' => 12.50, '1.5 kg' => 22.00,
        ]],
        ['sub' => 'CGL', 'marca' => 'Frigorífico', 'linea' => 'Papas Prefritas Frigorífico', 'vida' => 'media', 'rotacion' => 'media', 'presentaciones' => [
            '1 kg' => 10.00,
        ]],
        ['sub' => 'CGL', 'marca' => 'La Preferida', 'linea' => 'Verduras Congeladas La Preferida Mixtas', 'vida' => 'media', 'rotacion' => 'baja', 'presentaciones' => [
            '500 g' => 8.40,
        ]],

        // ---------------- FRUTAS (por kilo) ----------------
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Plátano de Seda', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 2.20,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Manzana Israel', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 4.60,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Manzana Delicia', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 5.20,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Naranja de Jugo', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 2.60,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Mandarina Satsuma', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 3.40,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Palta Fuerte', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 7.80,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Papaya', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 3.20,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Piña Golden', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 3.60,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Uva Red Globe', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 6.40,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Sandía', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 2.40,
        ]],

        // ---------------- VERDURAS (por kilo) ----------------
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Papa Blanca', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 2.10,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Papa Amarilla', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 3.40,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Cebolla Roja', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 2.40,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Tomate Italiano', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 2.80,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Limón Sutil', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 4.20,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Zanahoria', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 2.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Ajo Pelado', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 12.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Zapallo Macre', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 2.60,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Choclo Serrano', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 3.80,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Camote Amarillo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 2.30,
        ]],

        // ---------------- GRANEL (por kilo) ----------------
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Arroz Extra a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'presentaciones' => [
            'Por kilogramo' => 3.90,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Azúcar Rubia a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'presentaciones' => [
            'Por kilogramo' => 3.30,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Lenteja a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'presentaciones' => [
            'Por kilogramo' => 6.40,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Frejol Canario a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'presentaciones' => [
            'Por kilogramo' => 7.60,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Avena a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'presentaciones' => [
            'Por kilogramo' => 4.20,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Maíz Mote a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 4.80,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Cancha Serrana a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'presentaciones' => [
            'Por kilogramo' => 5.20,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Maní Tostado a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 11.00,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Quinua a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'rotacion' => 'baja', 'presentaciones' => [
            'Por kilogramo' => 9.60,
        ]],
        ['sub' => 'GRA', 'marca' => 'Genérico', 'linea' => 'Trigo Pelado a Granel', 'unidad' => 'KG', 'vida' => 'larga', 'rotacion' => 'baja', 'presentaciones' => [
            'Por kilogramo' => 4.40,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Fresa Fresca', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 7.20,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Mango Kent', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 4.40,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Pera de Agua', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 5.60,
        ]],
        ['sub' => 'FRU', 'marca' => 'Genérico', 'linea' => 'Melón', 'unidad' => 'KG', 'rotacion' => 'baja', 'presentaciones' => [
            'Por kilogramo' => 3.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Pepinillo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 2.60,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Pimiento Rojo', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 5.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Brócoli', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 4.20,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Culantro', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 6.00,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Apio', 'unidad' => 'KG', 'rotacion' => 'baja', 'presentaciones' => [
            'Por kilogramo' => 3.20,
        ]],
        ['sub' => 'VER', 'marca' => 'Genérico', 'linea' => 'Lechuga Americana', 'unidad' => 'KG', 'rotacion' => 'media', 'presentaciones' => [
            'Por kilogramo' => 3.80,
        ]],
        ['sub' => 'HEL', 'marca' => "D'Onofrio", 'linea' => "Helado D'Onofrio Casquito", 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 2.20,
        ]],
        ['sub' => 'HEL', 'marca' => 'Artika', 'linea' => 'Helado Artika Sandwich', 'vida' => 'media', 'presentaciones' => [
            'Unidad' => 2.20,
        ]],
        ['sub' => 'CGL', 'marca' => 'San Fernando', 'linea' => 'Salchicha San Fernando Congelada', 'vida' => 'media', 'presentaciones' => [
            '500 g' => 12.00,
        ]],
    ],
];
