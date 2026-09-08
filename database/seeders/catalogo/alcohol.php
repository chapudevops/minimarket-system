<?php

/**
 * Cervezas, vinos, piscos y espumantes.
 *
 * Se mantienen en su propia categoría (LIC) porque la venta de alcohol tiene
 * restricciones horarias y de edad que el sistema puede querer aplicar aparte.
 */
return [
    'codigo' => 'LIC',
    'margen' => [0.18, 0.32],
    'rotacion' => 'alta',
    'vida' => 'larga',

    'familias' => [
        // ---------------- CERVEZAS ----------------
        ['sub' => 'CER', 'marca' => 'Pilsen Callao', 'linea' => 'Cerveza Pilsen Callao', 'presentaciones' => [
            '310 ml lata' => 3.20, '355 ml lata' => 3.60, '473 ml lata' => 4.40,
            '620 ml botella' => 5.60, 'Six pack 355 ml' => 20.50,
        ]],
        ['sub' => 'CER', 'marca' => 'Cristal', 'linea' => 'Cerveza Cristal', 'presentaciones' => [
            '310 ml lata' => 3.10, '355 ml lata' => 3.50, '473 ml lata' => 4.30,
            '620 ml botella' => 5.50, 'Six pack 355 ml' => 20.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Cusqueña', 'linea' => 'Cerveza Cusqueña Dorada', 'presentaciones' => [
            '310 ml lata' => 3.60, '330 ml botella' => 4.20, '620 ml botella' => 6.50, 'Six pack 330 ml' => 24.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Cusqueña', 'linea' => 'Cerveza Cusqueña Trigo', 'presentaciones' => [
            '310 ml lata' => 3.80, '330 ml botella' => 4.40,
        ]],
        ['sub' => 'CER', 'marca' => 'Cusqueña', 'linea' => 'Cerveza Cusqueña Negra', 'presentaciones' => [
            '310 ml lata' => 3.80, '330 ml botella' => 4.40,
        ]],
        ['sub' => 'CER', 'marca' => 'Arequipeña', 'linea' => 'Cerveza Arequipeña', 'presentaciones' => [
            '355 ml lata' => 3.40, '620 ml botella' => 5.40,
        ]],
        ['sub' => 'CER', 'marca' => 'Trujillo', 'linea' => 'Cerveza Trujillo', 'rotacion' => 'media', 'presentaciones' => [
            '620 ml botella' => 5.30,
        ]],
        ['sub' => 'CER', 'marca' => 'Pilsen Trujillo', 'linea' => 'Cerveza Pilsen Trujillo', 'rotacion' => 'media', 'presentaciones' => [
            '355 ml lata' => 3.40, '620 ml botella' => 5.40,
        ]],
        ['sub' => 'CER', 'marca' => 'Corona', 'linea' => 'Cerveza Corona Extra', 'rotacion' => 'media', 'presentaciones' => [
            '355 ml botella' => 6.20, 'Six pack 355 ml' => 35.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Heineken', 'linea' => 'Cerveza Heineken', 'rotacion' => 'media', 'presentaciones' => [
            '330 ml botella' => 6.00, '473 ml lata' => 7.50, 'Six pack 330 ml' => 34.00,
        ]],
        ['sub' => 'CER', 'marca' => 'Stella Artois', 'linea' => 'Cerveza Stella Artois', 'rotacion' => 'media', 'presentaciones' => [
            '330 ml botella' => 6.30, '473 ml lata' => 7.80,
        ]],
        ['sub' => 'CER', 'marca' => 'Budweiser', 'linea' => 'Cerveza Budweiser', 'rotacion' => 'media', 'presentaciones' => [
            '355 ml lata' => 4.60, '473 ml lata' => 5.80,
        ]],
        ['sub' => 'CER', 'marca' => 'Miller', 'linea' => 'Cerveza Miller Genuine Draft', 'rotacion' => 'baja', 'presentaciones' => [
            '355 ml botella' => 5.40,
        ]],
        ['sub' => 'CER', 'marca' => 'Barena', 'linea' => 'Cerveza Barena', 'rotacion' => 'media', 'presentaciones' => [
            '355 ml lata' => 3.00, '620 ml botella' => 4.90,
        ]],
        ['sub' => 'CER', 'marca' => 'Golden', 'linea' => 'Cerveza Golden', 'rotacion' => 'media', 'presentaciones' => [
            '355 ml lata' => 2.90, '620 ml botella' => 4.70,
        ]],

        // ---------------- VINOS ----------------
        ['sub' => 'VIN', 'marca' => 'Tabernero', 'linea' => 'Vino Tabernero Borgoña Semi Seco', 'rotacion' => 'media', 'presentaciones' => [
            '750 ml' => 16.00,
        ]],
        ['sub' => 'VIN', 'marca' => 'Tabernero', 'linea' => 'Vino Tabernero Rosé Semi Seco', 'rotacion' => 'media', 'presentaciones' => [
            '750 ml' => 16.50,
        ]],
        ['sub' => 'VIN', 'marca' => 'Tabernero', 'linea' => 'Vino Tabernero Gran Tinto', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 22.00,
        ]],
        ['sub' => 'VIN', 'marca' => 'Santiago Queirolo', 'linea' => 'Vino Santiago Queirolo Borgoña', 'rotacion' => 'media', 'presentaciones' => [
            '750 ml' => 17.00, '1.5 L' => 30.00,
        ]],
        ['sub' => 'VIN', 'marca' => 'Santiago Queirolo', 'linea' => 'Vino Santiago Queirolo Magdalena', 'rotacion' => 'media', 'presentaciones' => [
            '750 ml' => 18.00,
        ]],
        ['sub' => 'VIN', 'marca' => 'Ocucaje', 'linea' => 'Vino Ocucaje Borgoña Semi Seco', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 19.00,
        ]],
        ['sub' => 'VIN', 'marca' => 'Casillero del Diablo', 'linea' => 'Vino Casillero del Diablo Cabernet Sauvignon', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 34.00,
        ]],
        ['sub' => 'VIN', 'marca' => 'Gato Negro', 'linea' => 'Vino Gato Negro Cabernet Sauvignon', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 24.00,
        ]],

        // ---------------- PISCOS Y LICORES ----------------
        ['sub' => 'PIS', 'marca' => 'Tabernero', 'linea' => 'Pisco Tabernero Quebranta', 'rotacion' => 'media', 'presentaciones' => [
            '500 ml' => 24.00, '700 ml' => 32.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Tabernero', 'linea' => 'Pisco Tabernero Acholado', 'rotacion' => 'media', 'presentaciones' => [
            '500 ml' => 25.00, '700 ml' => 33.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Queirolo', 'linea' => 'Pisco Queirolo Quebranta', 'rotacion' => 'media', 'presentaciones' => [
            '700 ml' => 34.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Queirolo', 'linea' => 'Pisco Queirolo Italia', 'rotacion' => 'baja', 'presentaciones' => [
            '700 ml' => 36.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Cuatro Gallos', 'linea' => 'Pisco Cuatro Gallos Acholado', 'rotacion' => 'baja', 'presentaciones' => [
            '700 ml' => 30.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Cartavio', 'linea' => 'Ron Cartavio Black', 'rotacion' => 'media', 'presentaciones' => [
            '750 ml' => 28.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Cartavio', 'linea' => 'Ron Cartavio Solera 12 Años', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 62.00,
        ]],
        ['sub' => 'PIS', 'marca' => 'Appleton', 'linea' => 'Ron Appleton Special', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 45.00,
        ]],

        // ---------------- ESPUMANTES ----------------
        ['sub' => 'ESP', 'marca' => 'Tabernero', 'linea' => 'Espumante Tabernero Brut', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 27.00,
        ]],
        ['sub' => 'ESP', 'marca' => 'Tabernero', 'linea' => 'Espumante Tabernero Semi Seco', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 27.00,
        ]],
        ['sub' => 'ESP', 'marca' => 'Santiago Queirolo', 'linea' => 'Espumante Santiago Queirolo Brut', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 29.00,
        ]],
        ['sub' => 'ESP', 'marca' => 'Riccadonna', 'linea' => 'Espumante Riccadonna Asti', 'rotacion' => 'baja', 'presentaciones' => [
            '750 ml' => 42.00,
        ]],
    ],
];
