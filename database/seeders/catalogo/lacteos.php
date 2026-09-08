<?php

/** Lácteos, embutidos, huevos y panadería. Vida útil corta o media. */
return [
    'codigo' => 'LAC',
    'margen' => [0.12, 0.28],
    'rotacion' => 'alta',
    'vida' => 'corta',

    'familias' => [
        // ---------------- LECHE ----------------
        ['sub' => 'LEC', 'marca' => 'Gloria', 'linea' => 'Leche Gloria Evaporada Entera', 'vida' => 'larga', 'presentaciones' => [
            '395 g lata' => 3.10, '170 g lata' => 1.60, 'Pack x6 395 g' => 18.00,
        ]],
        ['sub' => 'LEC', 'marca' => 'Gloria', 'linea' => 'Leche Gloria Evaporada Light', 'vida' => 'larga', 'presentaciones' => [
            '395 g lata' => 3.30,
        ]],
        ['sub' => 'LEC', 'marca' => 'Gloria', 'linea' => 'Leche Gloria Deslactosada', 'vida' => 'larga', 'presentaciones' => [
            '395 g lata' => 3.40, '1 L' => 5.20,
        ]],
        ['sub' => 'LEC', 'marca' => 'Gloria', 'linea' => 'Leche Gloria UHT Entera', 'vida' => 'media', 'presentaciones' => [
            '1 L' => 4.60,
        ]],
        ['sub' => 'LEC', 'marca' => 'Gloria', 'linea' => 'Leche Gloria Chocolatada', 'vida' => 'media', 'presentaciones' => [
            '180 ml' => 1.40, '1 L' => 5.00,
        ]],
        ['sub' => 'LEC', 'marca' => 'Gloria', 'linea' => 'Leche Condensada Gloria', 'vida' => 'larga', 'presentaciones' => [
            '393 g lata' => 5.40,
        ]],
        ['sub' => 'LEC', 'marca' => 'Laive', 'linea' => 'Leche Laive Evaporada Entera', 'vida' => 'larga', 'presentaciones' => [
            '400 g lata' => 3.20,
        ]],
        ['sub' => 'LEC', 'marca' => 'Laive', 'linea' => 'Leche Laive UHT Sin Lactosa', 'vida' => 'media', 'presentaciones' => [
            '1 L' => 5.60,
        ]],
        ['sub' => 'LEC', 'marca' => 'Bonlé', 'linea' => 'Leche Bonlé Evaporada Entera', 'vida' => 'larga', 'presentaciones' => [
            '400 g lata' => 3.00,
        ]],
        ['sub' => 'LEC', 'marca' => 'Ideal', 'linea' => 'Leche Ideal Amanecer Evaporada', 'vida' => 'larga', 'presentaciones' => [
            '390 g lata' => 2.90, '170 g lata' => 1.50,
        ]],
        ['sub' => 'LEC', 'marca' => 'Ideal', 'linea' => 'Leche Ideal Cremosita', 'vida' => 'larga', 'presentaciones' => [
            '390 g lata' => 3.00,
        ]],
        ['sub' => 'LEC', 'marca' => 'Anchor', 'linea' => 'Leche Anchor en Polvo Entera', 'vida' => 'larga', 'rotacion' => 'media', 'presentaciones' => [
            '400 g' => 12.00,
        ]],

        // ---------------- YOGURT ----------------
        ['sub' => 'YOG', 'marca' => 'Gloria', 'linea' => 'Yogurt Gloria Fresa', 'presentaciones' => [
            '180 g' => 1.30, '500 g' => 3.20, '1 L' => 5.40, '1.7 L' => 8.60,
        ]],
        ['sub' => 'YOG', 'marca' => 'Gloria', 'linea' => 'Yogurt Gloria Vainilla Francesa', 'presentaciones' => [
            '180 g' => 1.30, '1 L' => 5.40,
        ]],
        ['sub' => 'YOG', 'marca' => 'Gloria', 'linea' => 'Yogurt Gloria Durazno', 'presentaciones' => [
            '180 g' => 1.30, '1 L' => 5.40,
        ]],
        ['sub' => 'YOG', 'marca' => 'Gloria', 'linea' => 'Yogurt Gloria Griego', 'rotacion' => 'media', 'presentaciones' => [
            '150 g' => 2.60,
        ]],
        ['sub' => 'YOG', 'marca' => 'Laive', 'linea' => 'Yogurt Laive Sbelt Fresa', 'presentaciones' => [
            '180 g' => 1.50, '1 L' => 6.20,
        ]],
        ['sub' => 'YOG', 'marca' => 'Laive', 'linea' => 'Yogurt Laive Bio Natural', 'rotacion' => 'media', 'presentaciones' => [
            '1 L' => 6.40,
        ]],
        ['sub' => 'YOG', 'marca' => 'Danlac', 'linea' => 'Yogurt Danlac Fresa', 'rotacion' => 'media', 'presentaciones' => [
            '1 L' => 4.90,
        ]],
        ['sub' => 'YOG', 'marca' => 'Milkito', 'linea' => 'Yogurt Milkito Fresa', 'presentaciones' => [
            '180 g' => 1.20, '1 L' => 5.00,
        ]],

        // ---------------- QUESOS Y MANTEQUILLAS ----------------
        ['sub' => 'QUE', 'marca' => 'Laive', 'linea' => 'Queso Laive Fresco', 'presentaciones' => [
            '500 g' => 12.50,
        ]],
        ['sub' => 'QUE', 'marca' => 'Laive', 'linea' => 'Queso Laive Edam en Tajadas', 'rotacion' => 'media', 'presentaciones' => [
            '180 g' => 8.40,
        ]],
        ['sub' => 'QUE', 'marca' => 'Bonlé', 'linea' => 'Queso Bonlé Fresco', 'presentaciones' => [
            '500 g' => 11.80,
        ]],
        ['sub' => 'QUE', 'marca' => 'Gloria', 'linea' => 'Queso Gloria Mozzarella', 'rotacion' => 'media', 'presentaciones' => [
            '200 g' => 9.20,
        ]],
        ['sub' => 'QUE', 'marca' => 'Laive', 'linea' => 'Mantequilla Laive con Sal', 'presentaciones' => [
            '100 g' => 3.60, '200 g' => 6.40,
        ]],
        ['sub' => 'QUE', 'marca' => 'Gloria', 'linea' => 'Mantequilla Gloria con Sal', 'presentaciones' => [
            '200 g' => 6.20,
        ]],
        ['sub' => 'QUE', 'marca' => 'Sello de Oro', 'linea' => 'Margarina Sello de Oro', 'vida' => 'media', 'presentaciones' => [
            '90 g' => 1.80, '225 g' => 3.80,
        ]],
        ['sub' => 'QUE', 'marca' => 'Manty', 'linea' => 'Margarina Manty', 'vida' => 'media', 'presentaciones' => [
            '90 g' => 1.70, '225 g' => 3.60,
        ]],
        ['sub' => 'QUE', 'marca' => 'Gloria', 'linea' => 'Crema de Leche Gloria', 'vida' => 'larga', 'rotacion' => 'media', 'presentaciones' => [
            '170 g' => 3.40,
        ]],
        ['sub' => 'QUE', 'marca' => 'Nestlé', 'linea' => 'Crema de Leche Nestlé', 'vida' => 'larga', 'rotacion' => 'media', 'presentaciones' => [
            '300 ml' => 5.60,
        ]],

        // ---------------- EMBUTIDOS ----------------
        ['sub' => 'EMB', 'marca' => 'San Fernando', 'linea' => 'Hot Dog San Fernando Clásico', 'presentaciones' => [
            '230 g' => 6.20, '450 g' => 11.50, '1 kg' => 24.00,
        ]],
        ['sub' => 'EMB', 'marca' => 'San Fernando', 'linea' => 'Jamonada San Fernando', 'presentaciones' => [
            '200 g' => 4.80, '500 g' => 10.50,
        ]],
        ['sub' => 'EMB', 'marca' => 'San Fernando', 'linea' => 'Jamón San Fernando Inglés', 'rotacion' => 'media', 'presentaciones' => [
            '200 g' => 7.60,
        ]],
        ['sub' => 'EMB', 'marca' => 'San Fernando', 'linea' => 'Chorizo San Fernando Parrillero', 'rotacion' => 'media', 'presentaciones' => [
            '400 g' => 11.00,
        ]],
        ['sub' => 'EMB', 'marca' => 'Otto Kunz', 'linea' => 'Hot Dog Otto Kunz', 'presentaciones' => [
            '230 g' => 6.40, '450 g' => 12.00,
        ]],
        ['sub' => 'EMB', 'marca' => 'Otto Kunz', 'linea' => 'Jamón Otto Kunz Americano', 'rotacion' => 'media', 'presentaciones' => [
            '200 g' => 8.20,
        ]],
        ['sub' => 'EMB', 'marca' => 'Otto Kunz', 'linea' => 'Mortadela Otto Kunz', 'presentaciones' => [
            '200 g' => 4.60,
        ]],
        ['sub' => 'EMB', 'marca' => 'Braedt', 'linea' => 'Hot Dog Braedt', 'presentaciones' => [
            '230 g' => 6.00, '450 g' => 11.20,
        ]],
        ['sub' => 'EMB', 'marca' => 'Braedt', 'linea' => 'Jamonada Braedt', 'presentaciones' => [
            '200 g' => 4.50,
        ]],
        ['sub' => 'EMB', 'marca' => 'Braedt', 'linea' => 'Tocino Braedt Ahumado', 'rotacion' => 'media', 'presentaciones' => [
            '200 g' => 9.40,
        ]],
        ['sub' => 'EMB', 'marca' => 'La Segoviana', 'linea' => 'Salchicha La Segoviana Huachana', 'rotacion' => 'media', 'presentaciones' => [
            '400 g' => 10.60,
        ]],
        ['sub' => 'EMB', 'marca' => 'La Segoviana', 'linea' => 'Jamonada La Segoviana', 'presentaciones' => [
            '200 g' => 4.40,
        ]],
        ['sub' => 'EMB', 'marca' => 'Laive', 'linea' => 'Hot Dog Laive de Pollo', 'presentaciones' => [
            '230 g' => 5.80,
        ]],

        // ---------------- HUEVOS ----------------
        ['sub' => 'HUE', 'marca' => 'La Calera', 'linea' => 'Huevos La Calera Pardos', 'vida' => 'corta', 'presentaciones' => [
            'Media docena' => 4.20, 'Docena' => 8.00, 'Bandeja 15 unidades' => 9.80, 'Bandeja 30 unidades' => 19.00,
        ]],
        ['sub' => 'HUE', 'marca' => 'Santa Elena', 'linea' => 'Huevos Santa Elena Frescos', 'vida' => 'corta', 'presentaciones' => [
            'Media docena' => 4.00, 'Docena' => 7.70, 'Bandeja 30 unidades' => 18.50,
        ]],
        ['sub' => 'HUE', 'marca' => 'Genérico', 'linea' => 'Huevos de Corral a Granel', 'vida' => 'corta', 'unidad' => 'KG', 'presentaciones' => [
            'Por kilogramo' => 8.20,
        ]],

        // ---------------- PANADERÍA ----------------
        ['sub' => 'PAN', 'marca' => 'Bimbo', 'linea' => 'Pan de Molde Bimbo Blanco', 'presentaciones' => [
            '400 g' => 5.40, '640 g' => 8.20,
        ]],
        ['sub' => 'PAN', 'marca' => 'Bimbo', 'linea' => 'Pan de Molde Bimbo Integral', 'presentaciones' => [
            '400 g' => 5.90, '640 g' => 8.80,
        ]],
        ['sub' => 'PAN', 'marca' => 'Bimbo', 'linea' => 'Pan de Hamburguesa Bimbo', 'rotacion' => 'media', 'presentaciones' => [
            'Paquete x4' => 4.20, 'Paquete x8' => 7.60,
        ]],
        ['sub' => 'PAN', 'marca' => 'Bimbo', 'linea' => 'Pan de Hot Dog Bimbo', 'rotacion' => 'media', 'presentaciones' => [
            'Paquete x8' => 7.20,
        ]],
        ['sub' => 'PAN', 'marca' => 'Union', 'linea' => 'Pan de Molde Union Blanco', 'presentaciones' => [
            '400 g' => 5.00,
        ]],
        ['sub' => 'PAN', 'marca' => 'Wong', 'linea' => 'Pan de Molde Wong Integral', 'rotacion' => 'media', 'presentaciones' => [
            '500 g' => 6.20,
        ]],
        ['sub' => 'PAN', 'marca' => 'Bimbo', 'linea' => 'Keke Bimbo Vainilla', 'vida' => 'media', 'presentaciones' => [
            '60 g' => 1.60, '280 g' => 6.40,
        ]],
        ['sub' => 'PAN', 'marca' => 'Bimbo', 'linea' => 'Bizcocho Bimbo Chocolate', 'vida' => 'media', 'presentaciones' => [
            '60 g' => 1.70,
        ]],
        ['sub' => 'PAN', 'marca' => 'Field', 'linea' => 'Tostadas Field Integrales', 'vida' => 'media', 'presentaciones' => [
            '180 g' => 4.20,
        ]],
        ['sub' => 'PAN', 'marca' => 'Genérico', 'linea' => 'Pan Francés', 'unidad' => 'UNIDAD', 'presentaciones' => [
            'Unidad' => 0.20,
        ]],
        ['sub' => 'PAN', 'marca' => 'Genérico', 'linea' => 'Pan Ciabatta', 'unidad' => 'UNIDAD', 'rotacion' => 'media', 'presentaciones' => [
            'Unidad' => 0.60,
        ]],
    ],
];
