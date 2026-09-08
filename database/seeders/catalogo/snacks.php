<?php

/** Galletas, snacks salados, chocolates y golosinas. */
return [
    'codigo' => 'SNK',
    'margen' => [0.20, 0.40],
    'rotacion' => 'alta',
    'vida' => 'media',

    'familias' => [
        // ---------------- GALLETAS ----------------
        ['sub' => 'GAL', 'marca' => 'Oreo', 'linea' => 'Galleta Oreo Original', 'presentaciones' => [
            '36 g' => 0.80, '108 g' => 2.20, 'Paquete x6' => 4.20,
        ]],
        ['sub' => 'GAL', 'marca' => 'Oreo', 'linea' => 'Galleta Oreo Chocolate', 'presentaciones' => [
            '36 g' => 0.85, '108 g' => 2.30,
        ]],
        ['sub' => 'GAL', 'marca' => 'Field', 'linea' => 'Galleta Soda Field', 'presentaciones' => [
            '34 g' => 0.55, 'Paquete x6' => 2.90, 'Paquete x8' => 3.80,
        ]],
        ['sub' => 'GAL', 'marca' => 'Field', 'linea' => 'Galleta Soda Field Integral', 'presentaciones' => [
            '34 g' => 0.60, 'Paquete x6' => 3.10,
        ]],
        ['sub' => 'GAL', 'marca' => 'Ritz', 'linea' => 'Galleta Ritz Original', 'presentaciones' => [
            '34 g' => 0.75, 'Paquete x6' => 4.00,
        ]],
        ['sub' => 'GAL', 'marca' => 'Casino', 'linea' => 'Galleta Casino Vainilla', 'presentaciones' => [
            '43 g' => 0.65, 'Paquete x6' => 3.40,
        ]],
        ['sub' => 'GAL', 'marca' => 'Casino', 'linea' => 'Galleta Casino Chocolate', 'presentaciones' => [
            '43 g' => 0.65, 'Paquete x6' => 3.40,
        ]],
        ['sub' => 'GAL', 'marca' => 'Casino', 'linea' => 'Galleta Casino Fresa', 'presentaciones' => [
            '43 g' => 0.65, 'Paquete x6' => 3.40,
        ]],
        ['sub' => 'GAL', 'marca' => 'Charada', 'linea' => 'Galleta Charada Vainilla', 'presentaciones' => [
            '35 g' => 0.50, 'Paquete x6' => 2.70,
        ]],
        ['sub' => 'GAL', 'marca' => 'Charada', 'linea' => 'Galleta Charada Chocolate', 'presentaciones' => [
            '35 g' => 0.50, 'Paquete x6' => 2.70,
        ]],
        ['sub' => 'GAL', 'marca' => 'Chomp', 'linea' => 'Galleta Chomp Chocolate', 'presentaciones' => [
            '30 g' => 0.45, 'Paquete x6' => 2.40,
        ]],
        ['sub' => 'GAL', 'marca' => 'Morochas', 'linea' => 'Galleta Morochas Clásica', 'presentaciones' => [
            '38 g' => 0.60, 'Paquete x6' => 3.20,
        ]],
        ['sub' => 'GAL', 'marca' => 'Picaras', 'linea' => 'Galleta Picaras Chocolate', 'presentaciones' => [
            '38 g' => 0.65, 'Paquete x6' => 3.40,
        ]],
        ['sub' => 'GAL', 'marca' => 'Picaras', 'linea' => 'Galleta Picaras Limón', 'presentaciones' => [
            '38 g' => 0.65, 'Paquete x6' => 3.40,
        ]],
        ['sub' => 'GAL', 'marca' => 'Tentación', 'linea' => 'Galleta Tentación Chocolate', 'presentaciones' => [
            '38 g' => 0.60, 'Paquete x6' => 3.20,
        ]],
        ['sub' => 'GAL', 'marca' => 'GN', 'linea' => 'Galleta GN Vainilla', 'presentaciones' => [
            '35 g' => 0.50, 'Paquete x6' => 2.70,
        ]],
        ['sub' => 'GAL', 'marca' => 'Nik', 'linea' => 'Galleta Nik Chocolate', 'presentaciones' => [
            '35 g' => 0.55, 'Paquete x6' => 2.90,
        ]],
        ['sub' => 'GAL', 'marca' => 'Margarita', 'linea' => 'Galleta Margarita Clásica', 'presentaciones' => [
            '38 g' => 0.55, 'Paquete x6' => 2.90,
        ]],
        ['sub' => 'GAL', 'marca' => 'Vainilla Field', 'linea' => 'Galleta Vainilla Field', 'presentaciones' => [
            '38 g' => 0.55, 'Paquete x6' => 2.90,
        ]],
        ['sub' => 'GAL', 'marca' => 'Frac', 'linea' => 'Galleta Frac Vainilla', 'presentaciones' => [
            '36 g' => 0.55, 'Paquete x6' => 2.90,
        ]],
        ['sub' => 'GAL', 'marca' => 'Rellenitas', 'linea' => 'Galleta Rellenitas Fresa', 'presentaciones' => [
            '36 g' => 0.50, 'Paquete x6' => 2.70,
        ]],
        ['sub' => 'GAL', 'marca' => 'Club Social', 'linea' => 'Galleta Club Social Original', 'presentaciones' => [
            '26 g' => 0.60, 'Paquete x9' => 4.80,
        ]],
        ['sub' => 'GAL', 'marca' => 'Cua Cua', 'linea' => 'Galleta Cua Cua Chocolate', 'presentaciones' => [
            '26 g' => 0.70,
        ]],

        // ---------------- SNACKS SALADOS ----------------
        ['sub' => 'SAL', 'marca' => "Lay's", 'linea' => "Papas Lay's Clásicas", 'presentaciones' => [
            '38 g' => 1.40, '105 g' => 3.60, '200 g' => 6.50,
        ]],
        ['sub' => 'SAL', 'marca' => "Lay's", 'linea' => "Papas Lay's Corte Americano", 'presentaciones' => [
            '38 g' => 1.40, '105 g' => 3.60,
        ]],
        ['sub' => 'SAL', 'marca' => "Lay's", 'linea' => "Papas Lay's Picante", 'presentaciones' => [
            '38 g' => 1.40, '105 g' => 3.60,
        ]],
        ['sub' => 'SAL', 'marca' => 'Doritos', 'linea' => 'Tortillas Doritos Queso', 'presentaciones' => [
            '38 g' => 1.50, '145 g' => 4.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Doritos', 'linea' => 'Tortillas Doritos Dinamita', 'presentaciones' => [
            '38 g' => 1.50, '145 g' => 4.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Cheetos', 'linea' => 'Cheetos Queso', 'presentaciones' => [
            '32 g' => 1.20, '90 g' => 3.10,
        ]],
        ['sub' => 'SAL', 'marca' => 'Cheetos', 'linea' => 'Cheetos Boliqueso', 'presentaciones' => [
            '32 g' => 1.20, '90 g' => 3.10,
        ]],
        ['sub' => 'SAL', 'marca' => 'Chizitos', 'linea' => 'Chizitos Queso', 'presentaciones' => [
            '30 g' => 1.00, '85 g' => 2.70,
        ]],
        ['sub' => 'SAL', 'marca' => 'Tortees', 'linea' => 'Tortees Nacho', 'presentaciones' => [
            '32 g' => 1.10, '95 g' => 2.90,
        ]],
        ['sub' => 'SAL', 'marca' => 'Piqueo Snax', 'linea' => 'Piqueo Snax Mix', 'presentaciones' => [
            '38 g' => 1.30, '105 g' => 3.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Karinto', 'linea' => 'Karinto Habas Saladas', 'presentaciones' => [
            '35 g' => 1.10, '90 g' => 2.60,
        ]],
        ['sub' => 'SAL', 'marca' => 'Karinto', 'linea' => 'Karinto Maní Salado', 'presentaciones' => [
            '35 g' => 1.10, '90 g' => 2.60,
        ]],
        ['sub' => 'SAL', 'marca' => 'Inka Chips', 'linea' => 'Inka Chips Camote', 'rotacion' => 'media', 'presentaciones' => [
            '45 g' => 2.20, '150 g' => 6.00,
        ]],
        ['sub' => 'SAL', 'marca' => 'Inka Chips', 'linea' => 'Inka Chips Yuca', 'rotacion' => 'media', 'presentaciones' => [
            '45 g' => 2.20, '150 g' => 6.00,
        ]],
        ['sub' => 'SAL', 'marca' => 'Frit-Lay', 'linea' => 'Cancha Serrana Frit-Lay', 'presentaciones' => [
            '40 g' => 1.00, '100 g' => 2.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Pringles', 'linea' => 'Papas Pringles Original', 'rotacion' => 'media', 'presentaciones' => [
            '40 g' => 3.20, '124 g' => 8.50,
        ]],
        ['sub' => 'SAL', 'marca' => 'Pringles', 'linea' => 'Papas Pringles Crema y Cebolla', 'rotacion' => 'media', 'presentaciones' => [
            '40 g' => 3.20, '124 g' => 8.50,
        ]],

        // ---------------- CHOCOLATES ----------------
        ['sub' => 'CHO', 'marca' => 'Sublime', 'linea' => 'Chocolate Sublime Clásico', 'presentaciones' => [
            '30 g' => 1.20, '100 g' => 3.60, 'Pack x6' => 6.60,
        ]],
        ['sub' => 'CHO', 'marca' => 'Sublime', 'linea' => 'Chocolate Sublime Blanco', 'presentaciones' => [
            '30 g' => 1.25, '100 g' => 3.70,
        ]],
        ['sub' => 'CHO', 'marca' => 'Princesa', 'linea' => 'Chocolate Princesa Maní', 'presentaciones' => [
            '32 g' => 1.10, 'Pack x6' => 6.20,
        ]],
        ['sub' => 'CHO', 'marca' => 'Triángulo', 'linea' => 'Chocolate Triángulo D\'Onofrio', 'presentaciones' => [
            '32 g' => 1.10,
        ]],
        ['sub' => 'CHO', 'marca' => 'KitKat', 'linea' => 'Chocolate KitKat', 'presentaciones' => [
            '41.5 g' => 2.20, '4 dedos' => 3.20,
        ]],
        ['sub' => 'CHO', 'marca' => 'Snickers', 'linea' => 'Chocolate Snickers', 'presentaciones' => [
            '50 g' => 2.60,
        ]],
        ['sub' => 'CHO', 'marca' => "M&M's", 'linea' => "Chocolate M&M's Clásico", 'presentaciones' => [
            '47 g' => 2.80,
        ]],
        ['sub' => 'CHO', 'marca' => "M&M's", 'linea' => "Chocolate M&M's Maní", 'presentaciones' => [
            '49 g' => 2.90,
        ]],
        ['sub' => 'CHO', 'marca' => "Hershey's", 'linea' => "Chocolate Hershey's Cookies & Cream", 'rotacion' => 'media', 'presentaciones' => [
            '43 g' => 3.20,
        ]],
        ['sub' => 'CHO', 'marca' => 'Doña Pepa', 'linea' => 'Chocolate Doña Pepa', 'presentaciones' => [
            '32 g' => 1.00,
        ]],
        ['sub' => 'CHO', 'marca' => 'Vizzio', 'linea' => 'Chocolate Vizzio Almendras', 'rotacion' => 'media', 'presentaciones' => [
            '32 g' => 1.60,
        ]],
        ['sub' => 'CHO', 'marca' => 'Cañonazo', 'linea' => 'Chocolate Cañonazo', 'presentaciones' => [
            '32 g' => 1.10,
        ]],

        // ---------------- GOLOSINAS ----------------
        ['sub' => 'GOL', 'marca' => 'Halls', 'linea' => 'Caramelo Halls Mentol', 'presentaciones' => [
            'Barra 25 g' => 1.00,
        ]],
        ['sub' => 'GOL', 'marca' => 'Halls', 'linea' => 'Caramelo Halls Miel', 'presentaciones' => [
            'Barra 25 g' => 1.00,
        ]],
        ['sub' => 'GOL', 'marca' => 'Mentitas', 'linea' => 'Caramelo Mentitas', 'presentaciones' => [
            'Bolsa 100 unidades' => 5.50,
        ]],
        ['sub' => 'GOL', 'marca' => 'Ambrosoli', 'linea' => 'Caramelo Ambrosoli Frutas', 'presentaciones' => [
            'Bolsa 100 unidades' => 6.00,
        ]],
        ['sub' => 'GOL', 'marca' => 'Globo Pop', 'linea' => 'Chupetín Globo Pop', 'presentaciones' => [
            'Unidad' => 0.30, 'Bolsa 24 unidades' => 6.20,
        ]],
        ['sub' => 'GOL', 'marca' => 'Chupetín Chupa Chups', 'linea' => 'Chupetín Chupa Chups', 'presentaciones' => [
            'Unidad' => 0.70,
        ]],
        ['sub' => 'GOL', 'marca' => 'Mogul', 'linea' => 'Gomitas Mogul Frutas', 'presentaciones' => [
            '40 g' => 1.20, '100 g' => 2.60,
        ]],
        ['sub' => 'GOL', 'marca' => 'Trululu', 'linea' => 'Gomitas Trululu Aritos', 'presentaciones' => [
            '40 g' => 1.20,
        ]],
        ['sub' => 'GOL', 'marca' => 'Trident', 'linea' => 'Chicle Trident Menta', 'presentaciones' => [
            'Blister 8 unidades' => 1.60,
        ]],
        ['sub' => 'GOL', 'marca' => 'Trident', 'linea' => 'Chicle Trident Sandía', 'presentaciones' => [
            'Blister 8 unidades' => 1.60,
        ]],
        ['sub' => 'GOL', 'marca' => 'Beldent', 'linea' => 'Chicle Beldent Menta', 'presentaciones' => [
            'Blister 10 unidades' => 1.70,
        ]],
        ['sub' => 'GOL', 'marca' => 'Bubbaloo', 'linea' => 'Chicle Bubbaloo Tutti Frutti', 'presentaciones' => [
            'Unidad' => 0.30, 'Bolsa 50 unidades' => 12.00,
        ]],
        ['sub' => 'GOL', 'marca' => 'Cua Cua', 'linea' => 'Wafer Cua Cua', 'presentaciones' => [
            '26 g' => 0.70,
        ]],
        ['sub' => 'GAL', 'marca' => 'Field', 'linea' => 'Galleta Field Vainilla Rellena', 'presentaciones' => [
            '38 g' => 0.55, 'Paquete x6' => 2.90,
        ]],
        ['sub' => 'GAL', 'marca' => 'Costa', 'linea' => 'Galleta Costa Wafer Vainilla', 'presentaciones' => [
            '40 g' => 0.70, 'Paquete x6' => 3.80,
        ]],
        ['sub' => 'GAL', 'marca' => 'Costa', 'linea' => 'Galleta Costa Wafer Chocolate', 'presentaciones' => [
            '40 g' => 0.70, 'Paquete x6' => 3.80,
        ]],
        ['sub' => 'GAL', 'marca' => 'Victoria', 'linea' => 'Galleta Victoria Soda', 'presentaciones' => [
            '34 g' => 0.50, 'Paquete x6' => 2.70,
        ]],
        ['sub' => 'GAL', 'marca' => 'Fénix', 'linea' => 'Galleta Fénix Agua', 'presentaciones' => [
            '34 g' => 0.45, 'Paquete x6' => 2.40,
        ]],
        ['sub' => 'SAL', 'marca' => "Lay's", 'linea' => "Papas Lay's Mediterráneas", 'presentaciones' => [
            '38 g' => 1.45, '105 g' => 3.70,
        ]],
        ['sub' => 'SAL', 'marca' => 'Doritos', 'linea' => 'Tortillas Doritos Flamin Hot', 'presentaciones' => [
            '38 g' => 1.50, '145 g' => 4.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Karinto', 'linea' => 'Karinto Piqueo Mixto', 'presentaciones' => [
            '35 g' => 1.15, '90 g' => 2.70,
        ]],
        ['sub' => 'CHO', 'marca' => 'Sublime', 'linea' => 'Chocolate Sublime Extremo', 'presentaciones' => [
            '32 g' => 1.30, 'Pack x6' => 7.00,
        ]],
        ['sub' => 'CHO', 'marca' => 'Nestlé', 'linea' => 'Chocolate Nestlé Crunch', 'presentaciones' => [
            '38 g' => 2.30,
        ]],
        ['sub' => 'GOL', 'marca' => 'Mogul', 'linea' => 'Gomitas Mogul Dientes', 'presentaciones' => [
            '40 g' => 1.20,
        ]],
        ['sub' => 'GOL', 'marca' => 'Halls', 'linea' => 'Caramelo Halls Fresa', 'presentaciones' => [
            'Barra 25 g' => 1.00,
        ]],
    ],
];
