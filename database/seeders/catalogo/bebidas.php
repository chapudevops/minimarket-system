<?php

/**
 * Gaseosas, agua, energizantes, bebidas deportivas, jugos y néctares.
 *
 * Las presentaciones son las que realmente se ven en góndola peruana; el
 * número es el precio de compra aproximado en soles.
 */
return [
    'codigo' => 'BEB',
    'margen' => [0.15, 0.30],
    'rotacion' => 'alta',
    'vida' => 'media',

    'familias' => [
        // ---------------- GASEOSAS ----------------
        ['sub' => 'GAS', 'marca' => 'Coca-Cola', 'linea' => 'Coca-Cola Original', 'presentaciones' => [
            '355 ml lata' => 1.90, '500 ml' => 2.30, '600 ml' => 2.60, '1 L' => 3.40,
            '1.5 L' => 4.30, '2 L' => 5.20, '2.5 L' => 6.10, '3 L' => 7.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Coca-Cola', 'linea' => 'Coca-Cola Sin Azúcar', 'presentaciones' => [
            '355 ml lata' => 1.95, '500 ml' => 2.35, '1.5 L' => 4.35, '2 L' => 5.25, '3 L' => 7.05,
        ]],
        ['sub' => 'GAS', 'marca' => 'Coca-Cola', 'linea' => 'Coca-Cola Zero', 'presentaciones' => [
            '355 ml lata' => 1.95, '500 ml' => 2.35, '1.5 L' => 4.35, '2 L' => 5.25,
        ]],
        ['sub' => 'GAS', 'marca' => 'Inca Kola', 'linea' => 'Inca Kola Original', 'presentaciones' => [
            '355 ml lata' => 1.90, '500 ml' => 2.30, '600 ml' => 2.60, '1 L' => 3.40,
            '1.5 L' => 4.30, '2 L' => 5.20, '2.5 L' => 6.10, '3 L' => 7.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Inca Kola', 'linea' => 'Inca Kola Sin Azúcar', 'presentaciones' => [
            '500 ml' => 2.35, '1.5 L' => 4.35, '2 L' => 5.25, '3 L' => 7.05,
        ]],
        ['sub' => 'GAS', 'marca' => 'Fanta', 'linea' => 'Fanta Naranja', 'presentaciones' => [
            '355 ml lata' => 1.80, '500 ml' => 2.20, '1.5 L' => 4.10, '3 L' => 6.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Fanta', 'linea' => 'Fanta Kola Inglesa', 'presentaciones' => [
            '500 ml' => 2.20, '1.5 L' => 4.10, '3 L' => 6.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Sprite', 'linea' => 'Sprite Lima Limón', 'presentaciones' => [
            '355 ml lata' => 1.80, '500 ml' => 2.20, '1.5 L' => 4.10, '2 L' => 4.90, '3 L' => 6.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Pepsi', 'linea' => 'Pepsi Cola', 'presentaciones' => [
            '355 ml lata' => 1.70, '500 ml' => 2.00, '1.5 L' => 3.70, '2 L' => 4.40, '3 L' => 6.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Pepsi', 'linea' => 'Pepsi Black Sin Azúcar', 'presentaciones' => [
            '500 ml' => 2.05, '1.5 L' => 3.75, '3 L' => 6.05,
        ]],
        ['sub' => 'GAS', 'marca' => '7UP', 'linea' => '7UP Lima Limón', 'presentaciones' => [
            '355 ml lata' => 1.70, '500 ml' => 2.00, '1.5 L' => 3.70, '3 L' => 6.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Concordia', 'linea' => 'Concordia Fresa', 'presentaciones' => [
            '500 ml' => 1.90, '1.5 L' => 3.50, '3 L' => 5.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Concordia', 'linea' => 'Concordia Piña', 'presentaciones' => [
            '500 ml' => 1.90, '1.5 L' => 3.50, '3 L' => 5.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Kola Real', 'linea' => 'Kola Real Amarilla', 'presentaciones' => [
            '500 ml' => 1.40, '1.5 L' => 2.70, '2.5 L' => 3.90, '3.3 L' => 4.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Kola Real', 'linea' => 'Kola Real Negra', 'presentaciones' => [
            '500 ml' => 1.40, '1.5 L' => 2.70, '3.3 L' => 4.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Big Cola', 'linea' => 'Big Cola Negra', 'presentaciones' => [
            '500 ml' => 1.50, '1.6 L' => 2.90, '2.6 L' => 4.20, '3.3 L' => 4.90,
        ]],
        ['sub' => 'GAS', 'marca' => 'Guaraná', 'linea' => 'Guaraná Backus', 'presentaciones' => [
            '500 ml' => 2.10, '1.5 L' => 3.90, '3 L' => 6.20,
        ]],
        ['sub' => 'GAS', 'marca' => 'Triple Kola', 'linea' => 'Triple Kola Amarilla', 'presentaciones' => [
            '500 ml' => 1.35, '1.5 L' => 2.60, '3.3 L' => 4.40,
        ]],
        ['sub' => 'GAS', 'marca' => 'Oro', 'linea' => 'Oro Kola Amarilla', 'presentaciones' => [
            '500 ml' => 1.30, '1.5 L' => 2.50, '3.3 L' => 4.30,
        ]],

        // ---------------- AGUA ----------------
        ['sub' => 'AGU', 'marca' => 'San Luis', 'linea' => 'Agua San Luis Sin Gas', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 1.10, '1 L' => 1.60, '2.5 L' => 3.10, '7 L' => 6.90, '20 L bidón' => 14.00,
        ]],
        ['sub' => 'AGU', 'marca' => 'San Luis', 'linea' => 'Agua San Luis Con Gas', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 1.15, '1 L' => 1.70, '2.5 L' => 3.20,
        ]],
        ['sub' => 'AGU', 'marca' => 'Cielo', 'linea' => 'Agua Cielo Sin Gas', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 0.95, '1 L' => 1.40, '2.5 L' => 2.80, '7 L' => 6.20, '20 L bidón' => 13.00,
        ]],
        ['sub' => 'AGU', 'marca' => 'Cielo', 'linea' => 'Agua Cielo Con Gas', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 1.00, '2.5 L' => 2.90,
        ]],
        ['sub' => 'AGU', 'marca' => 'San Mateo', 'linea' => 'Agua Mineral San Mateo Sin Gas', 'vida' => 'larga', 'presentaciones' => [
            '600 ml' => 1.60, '2.5 L' => 4.20,
        ]],
        ['sub' => 'AGU', 'marca' => 'San Mateo', 'linea' => 'Agua Mineral San Mateo Con Gas', 'vida' => 'larga', 'presentaciones' => [
            '600 ml' => 1.65, '2.5 L' => 4.30,
        ]],
        ['sub' => 'AGU', 'marca' => 'Socosani', 'linea' => 'Agua Mineral Socosani Sin Gas', 'vida' => 'larga', 'presentaciones' => [
            '600 ml' => 1.80, '2.5 L' => 4.50,
        ]],
        ['sub' => 'AGU', 'marca' => 'Socosani', 'linea' => 'Agua Mineral Socosani Con Gas', 'vida' => 'larga', 'presentaciones' => [
            '600 ml' => 1.85, '2.5 L' => 4.60,
        ]],
        ['sub' => 'AGU', 'marca' => 'Vida', 'linea' => 'Agua Vida Sin Gas', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 0.90, '2.5 L' => 2.70, '7 L' => 6.00,
        ]],
        ['sub' => 'AGU', 'marca' => 'Loa', 'linea' => 'Agua Loa Sin Gas', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 0.85, '2.5 L' => 2.60,
        ]],

        // ---------------- ENERGIZANTES ----------------
        ['sub' => 'ENE', 'marca' => 'Red Bull', 'linea' => 'Red Bull Energy Drink', 'rotacion' => 'media', 'presentaciones' => [
            '250 ml lata' => 6.50, '355 ml lata' => 8.40, '473 ml lata' => 10.50,
        ]],
        ['sub' => 'ENE', 'marca' => 'Red Bull', 'linea' => 'Red Bull Sugarfree', 'rotacion' => 'media', 'presentaciones' => [
            '250 ml lata' => 6.60, '355 ml lata' => 8.50,
        ]],
        ['sub' => 'ENE', 'marca' => 'Monster', 'linea' => 'Monster Energy Original', 'rotacion' => 'media', 'presentaciones' => [
            '473 ml lata' => 7.20,
        ]],
        ['sub' => 'ENE', 'marca' => 'Monster', 'linea' => 'Monster Energy Ultra', 'rotacion' => 'media', 'presentaciones' => [
            '473 ml lata' => 7.30,
        ]],
        ['sub' => 'ENE', 'marca' => 'Monster', 'linea' => 'Monster Energy Mango Loco', 'rotacion' => 'media', 'presentaciones' => [
            '473 ml lata' => 7.30,
        ]],
        ['sub' => 'ENE', 'marca' => 'Volt', 'linea' => 'Volt Energy Original', 'presentaciones' => [
            '300 ml' => 2.10, '500 ml' => 3.10,
        ]],
        ['sub' => 'ENE', 'marca' => 'Volt', 'linea' => 'Volt Energy Guaraná', 'presentaciones' => [
            '300 ml' => 2.10, '500 ml' => 3.10,
        ]],
        ['sub' => 'ENE', 'marca' => 'Burn', 'linea' => 'Burn Energy Drink', 'rotacion' => 'baja', 'presentaciones' => [
            '250 ml lata' => 4.80,
        ]],

        // ---------------- BEBIDAS DEPORTIVAS ----------------
        ['sub' => 'DEP', 'marca' => 'Sporade', 'linea' => 'Sporade Tropical', 'presentaciones' => [
            '500 ml' => 1.90, '750 ml' => 2.60, '1.5 L' => 4.30,
        ]],
        ['sub' => 'DEP', 'marca' => 'Sporade', 'linea' => 'Sporade Mandarina', 'presentaciones' => [
            '500 ml' => 1.90, '750 ml' => 2.60,
        ]],
        ['sub' => 'DEP', 'marca' => 'Sporade', 'linea' => 'Sporade Blue Berry', 'presentaciones' => [
            '500 ml' => 1.90, '750 ml' => 2.60,
        ]],
        ['sub' => 'DEP', 'marca' => 'Gatorade', 'linea' => 'Gatorade Tropical', 'presentaciones' => [
            '500 ml' => 2.80, '750 ml' => 3.80, '1 L' => 4.90,
        ]],
        ['sub' => 'DEP', 'marca' => 'Gatorade', 'linea' => 'Gatorade Naranja', 'presentaciones' => [
            '500 ml' => 2.80, '750 ml' => 3.80,
        ]],
        ['sub' => 'DEP', 'marca' => 'Gatorade', 'linea' => 'Gatorade Uva', 'presentaciones' => [
            '500 ml' => 2.80, '750 ml' => 3.80,
        ]],
        ['sub' => 'DEP', 'marca' => 'Powerade', 'linea' => 'Powerade Mora Azul', 'presentaciones' => [
            '500 ml' => 2.60, '750 ml' => 3.50,
        ]],
        ['sub' => 'DEP', 'marca' => 'Powerade', 'linea' => 'Powerade Naranja', 'presentaciones' => [
            '500 ml' => 2.60, '750 ml' => 3.50,
        ]],

        // ---------------- JUGOS Y NÉCTARES ----------------
        ['sub' => 'JUG', 'marca' => 'Frugos', 'linea' => 'Frugos Néctar Durazno', 'presentaciones' => [
            '235 ml' => 1.20, '300 ml' => 1.60, '1 L' => 4.20, '1.5 L' => 5.80,
        ]],
        ['sub' => 'JUG', 'marca' => 'Frugos', 'linea' => 'Frugos Néctar Manzana', 'presentaciones' => [
            '235 ml' => 1.20, '300 ml' => 1.60, '1 L' => 4.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Frugos', 'linea' => 'Frugos Néctar Piña', 'presentaciones' => [
            '235 ml' => 1.20, '1 L' => 4.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Frugos', 'linea' => 'Frugos Néctar Mango', 'presentaciones' => [
            '235 ml' => 1.20, '1 L' => 4.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Pulp', 'linea' => 'Pulp Néctar Durazno', 'presentaciones' => [
            '250 ml' => 1.00, '1 L' => 3.40,
        ]],
        ['sub' => 'JUG', 'marca' => 'Pulp', 'linea' => 'Pulp Néctar Mango', 'presentaciones' => [
            '250 ml' => 1.00, '1 L' => 3.40,
        ]],
        ['sub' => 'JUG', 'marca' => 'Pulp', 'linea' => 'Pulp Néctar Manzana', 'presentaciones' => [
            '250 ml' => 1.00, '1 L' => 3.40,
        ]],
        ['sub' => 'JUG', 'marca' => 'Cifrut', 'linea' => 'Cifrut Citrus Punch', 'presentaciones' => [
            '400 ml' => 1.10, '1.5 L' => 2.90, '3 L' => 5.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Cifrut', 'linea' => 'Cifrut Naranja', 'presentaciones' => [
            '400 ml' => 1.10, '1.5 L' => 2.90, '3 L' => 5.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Tampico', 'linea' => 'Tampico Citrus Punch', 'presentaciones' => [
            '450 ml' => 1.20, '1 L' => 2.40, '3.3 L' => 5.60,
        ]],
        ['sub' => 'JUG', 'marca' => 'Gloria', 'linea' => 'Jugo Gloria Durazno', 'presentaciones' => [
            '1 L' => 3.90,
        ]],
        ['sub' => 'JUG', 'marca' => 'Gloria', 'linea' => 'Jugo Gloria Naranja', 'presentaciones' => [
            '1 L' => 3.90,
        ]],
        ['sub' => 'JUG', 'marca' => 'Laive', 'linea' => 'Jugo Laive Naranja', 'presentaciones' => [
            '1 L' => 4.10,
        ]],
        ['sub' => 'JUG', 'marca' => 'Watts', 'linea' => 'Watts Néctar Durazno', 'presentaciones' => [
            '1 L' => 4.00,
        ]],
        ['sub' => 'JUG', 'marca' => 'Aquarius', 'linea' => 'Aquarius Pera', 'presentaciones' => [
            '500 ml' => 2.30, '1.5 L' => 4.40,
        ]],
        ['sub' => 'JUG', 'marca' => 'Free Tea', 'linea' => 'Free Tea Limón', 'presentaciones' => [
            '500 ml' => 1.80, '1.5 L' => 3.60,
        ]],
        ['sub' => 'JUG', 'marca' => 'Lipton', 'linea' => 'Lipton Ice Tea Durazno', 'presentaciones' => [
            '500 ml' => 2.20, '1.5 L' => 4.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Chicha Naturale', 'linea' => 'Chicha Morada Naturale', 'presentaciones' => [
            '500 ml' => 2.00, '1.5 L' => 4.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Coca-Cola', 'linea' => 'Coca-Cola Original Retornable', 'presentaciones' => [
            '1.5 L retornable' => 3.60, '2 L retornable' => 4.40, '3 L retornable' => 6.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Inca Kola', 'linea' => 'Inca Kola Original Retornable', 'presentaciones' => [
            '1.5 L retornable' => 3.60, '2 L retornable' => 4.40, '3 L retornable' => 6.00,
        ]],
        ['sub' => 'GAS', 'marca' => 'Sprite', 'linea' => 'Sprite Sin Azúcar', 'presentaciones' => [
            '500 ml' => 2.25, '1.5 L' => 4.15, '3 L' => 6.65,
        ]],
        ['sub' => 'GAS', 'marca' => 'Fanta', 'linea' => 'Fanta Piña', 'presentaciones' => [
            '500 ml' => 2.20, '1.5 L' => 4.10, '3 L' => 6.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Concordia', 'linea' => 'Concordia Naranja', 'presentaciones' => [
            '500 ml' => 1.90, '1.5 L' => 3.50, '3 L' => 5.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Kola Real', 'linea' => 'Kola Real Piña', 'presentaciones' => [
            '500 ml' => 1.40, '1.5 L' => 2.70, '3.3 L' => 4.60,
        ]],
        ['sub' => 'GAS', 'marca' => 'Kola Real', 'linea' => 'Kola Real Fresa', 'presentaciones' => [
            '500 ml' => 1.40, '1.5 L' => 2.70, '3.3 L' => 4.60,
        ]],
        ['sub' => 'JUG', 'marca' => 'Frugos', 'linea' => 'Frugos Néctar Naranja', 'presentaciones' => [
            '235 ml' => 1.20, '300 ml' => 1.60, '1 L' => 4.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Cifrut', 'linea' => 'Cifrut Piña', 'presentaciones' => [
            '400 ml' => 1.10, '1.5 L' => 2.90, '3 L' => 5.20,
        ]],
        ['sub' => 'JUG', 'marca' => 'Pulp', 'linea' => 'Pulp Néctar Piña', 'presentaciones' => [
            '250 ml' => 1.00, '1 L' => 3.40,
        ]],
        ['sub' => 'DEP', 'marca' => 'Sporade', 'linea' => 'Sporade Naranja', 'presentaciones' => [
            '500 ml' => 1.90, '750 ml' => 2.60,
        ]],
        ['sub' => 'AGU', 'marca' => 'San Luis', 'linea' => 'Agua San Luis Saborizada Manzana', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 1.60, '2.5 L' => 3.60,
        ]],
        ['sub' => 'AGU', 'marca' => 'Cielo', 'linea' => 'Agua Cielo Saborizada Limón', 'vida' => 'larga', 'presentaciones' => [
            '625 ml' => 1.50,
        ]],
    ],
];
