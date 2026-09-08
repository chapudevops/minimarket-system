<?php

/** Higiene y cuidado personal, cuidado femenino y línea de bebés. */
return [
    'codigo' => 'CUI',
    'margen' => [0.20, 0.40],
    'rotacion' => 'media',
    'vida' => 'ninguna',

    'familias' => [
        // ---------------- CABELLO ----------------
        ['sub' => 'CAB', 'marca' => 'Head & Shoulders', 'linea' => 'Shampoo Head & Shoulders Limpieza Renovadora', 'presentaciones' => [
            'Sachet 10 ml' => 0.60, '180 ml' => 11.00, '375 ml' => 18.00, '700 ml' => 29.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Head & Shoulders', 'linea' => 'Shampoo Head & Shoulders Manzana Fresh', 'presentaciones' => [
            '375 ml' => 18.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Pantene', 'linea' => 'Shampoo Pantene Restauración', 'presentaciones' => [
            'Sachet 10 ml' => 0.60, '400 ml' => 17.00, '750 ml' => 27.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Pantene', 'linea' => 'Acondicionador Pantene Restauración', 'presentaciones' => [
            '400 ml' => 17.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Sedal', 'linea' => 'Shampoo Sedal Rizos Obedientes', 'presentaciones' => [
            'Sachet 10 ml' => 0.50, '340 ml' => 12.50, '650 ml' => 21.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Sedal', 'linea' => 'Shampoo Sedal Ceramidas', 'presentaciones' => [
            '340 ml' => 12.50,
        ]],
        ['sub' => 'CAB', 'marca' => 'Sedal', 'linea' => 'Acondicionador Sedal Ceramidas', 'presentaciones' => [
            '340 ml' => 12.50,
        ]],
        ['sub' => 'CAB', 'marca' => 'Savital', 'linea' => 'Shampoo Savital Sábila', 'presentaciones' => [
            'Sachet 10 ml' => 0.50, '550 ml' => 15.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Ego', 'linea' => 'Shampoo Ego Black', 'rotacion' => 'baja', 'presentaciones' => [
            '400 ml' => 14.00,
        ]],
        ['sub' => 'CAB', 'marca' => 'Anua', 'linea' => 'Shampoo Anua Anticaspa', 'rotacion' => 'baja', 'presentaciones' => [
            '400 ml' => 10.50,
        ]],

        // ---------------- CUERPO ----------------
        ['sub' => 'CUE', 'marca' => 'Protex', 'linea' => 'Jabón Protex Avena', 'rotacion' => 'alta', 'presentaciones' => [
            '110 g' => 2.60, 'Pack x3' => 7.20,
        ]],
        ['sub' => 'CUE', 'marca' => 'Protex', 'linea' => 'Jabón Protex Fresh', 'rotacion' => 'alta', 'presentaciones' => [
            '110 g' => 2.60,
        ]],
        ['sub' => 'CUE', 'marca' => 'Palmolive', 'linea' => 'Jabón Palmolive Naturals', 'rotacion' => 'alta', 'presentaciones' => [
            '110 g' => 2.40, 'Pack x3' => 6.60,
        ]],
        ['sub' => 'CUE', 'marca' => 'Dove', 'linea' => 'Jabón Dove Original', 'presentaciones' => [
            '90 g' => 4.20, 'Pack x3' => 11.50,
        ]],
        ['sub' => 'CUE', 'marca' => 'Lux', 'linea' => 'Jabón Lux Suave Encanto', 'rotacion' => 'alta', 'presentaciones' => [
            '125 g' => 2.30,
        ]],
        ['sub' => 'CUE', 'marca' => 'Neko', 'linea' => 'Jabón Neko Antibacterial', 'rotacion' => 'alta', 'presentaciones' => [
            '125 g' => 2.20,
        ]],
        ['sub' => 'CUE', 'marca' => 'Nivea', 'linea' => 'Crema Nivea Corporal Hidratante', 'presentaciones' => [
            '200 ml' => 14.00, '400 ml' => 22.00,
        ]],
        ['sub' => 'CUE', 'marca' => 'Nivea', 'linea' => 'Crema Nivea Lata Clásica', 'presentaciones' => [
            '60 ml' => 8.40, '150 ml' => 16.00,
        ]],
        ['sub' => 'CUE', 'marca' => 'Mennen', 'linea' => 'Talco Mennen Original', 'presentaciones' => [
            '100 g' => 5.60, '200 g' => 9.40,
        ]],

        // ---------------- DESODORANTES ----------------
        ['sub' => 'DEO', 'marca' => 'Rexona', 'linea' => 'Desodorante Rexona Men Active', 'rotacion' => 'alta', 'presentaciones' => [
            'Barra 50 g' => 9.80, 'Spray 150 ml' => 12.50,
        ]],
        ['sub' => 'DEO', 'marca' => 'Rexona', 'linea' => 'Desodorante Rexona Women Powder', 'rotacion' => 'alta', 'presentaciones' => [
            'Barra 50 g' => 9.80, 'Spray 150 ml' => 12.50,
        ]],
        ['sub' => 'DEO', 'marca' => 'Nivea', 'linea' => 'Desodorante Nivea Dry Comfort', 'presentaciones' => [
            'Barra 50 g' => 10.50, 'Spray 150 ml' => 13.00,
        ]],
        ['sub' => 'DEO', 'marca' => 'Old Spice', 'linea' => 'Desodorante Old Spice Fresh', 'rotacion' => 'media', 'presentaciones' => [
            'Barra 50 g' => 11.00,
        ]],
        ['sub' => 'DEO', 'marca' => 'Speed Stick', 'linea' => 'Desodorante Speed Stick Cool', 'presentaciones' => [
            'Barra 50 g' => 9.20,
        ]],

        // ---------------- HIGIENE BUCAL ----------------
        ['sub' => 'BUC', 'marca' => 'Colgate', 'linea' => 'Crema Dental Colgate Triple Acción', 'rotacion' => 'alta', 'presentaciones' => [
            '22 g' => 1.20, '75 ml' => 4.40, '90 g' => 5.20, '150 ml' => 8.20,
        ]],
        ['sub' => 'BUC', 'marca' => 'Colgate', 'linea' => 'Crema Dental Colgate Máxima Protección', 'rotacion' => 'alta', 'presentaciones' => [
            '90 g' => 5.40,
        ]],
        ['sub' => 'BUC', 'marca' => 'Kolynos', 'linea' => 'Crema Dental Kolynos Super Blanco', 'rotacion' => 'alta', 'presentaciones' => [
            '22 g' => 1.00, '90 g' => 4.60,
        ]],
        ['sub' => 'BUC', 'marca' => 'Dento', 'linea' => 'Crema Dental Dento Menta', 'presentaciones' => [
            '90 g' => 3.60,
        ]],
        ['sub' => 'BUC', 'marca' => 'Colgate', 'linea' => 'Cepillo Dental Colgate Twister', 'presentaciones' => [
            'Unidad' => 5.40, 'Pack x2' => 9.60,
        ]],
        ['sub' => 'BUC', 'marca' => 'Oral-B', 'linea' => 'Cepillo Dental Oral-B Indicator', 'presentaciones' => [
            'Unidad' => 6.80,
        ]],
        ['sub' => 'BUC', 'marca' => 'Listerine', 'linea' => 'Enjuague Bucal Listerine Cool Mint', 'rotacion' => 'media', 'presentaciones' => [
            '250 ml' => 11.00, '500 ml' => 18.00,
        ]],
        ['sub' => 'BUC', 'marca' => 'Colgate', 'linea' => 'Enjuague Bucal Colgate Plax', 'rotacion' => 'media', 'presentaciones' => [
            '250 ml' => 9.50, '500 ml' => 15.50,
        ]],

        // ---------------- AFEITADO ----------------
        ['sub' => 'AFE', 'marca' => 'Gillette', 'linea' => 'Máquina de Afeitar Gillette Prestobarba', 'presentaciones' => [
            'Unidad' => 2.60, 'Pack x3' => 6.80,
        ]],
        ['sub' => 'AFE', 'marca' => 'Gillette', 'linea' => 'Espuma de Afeitar Gillette', 'rotacion' => 'baja', 'presentaciones' => [
            '175 g' => 14.00,
        ]],
        ['sub' => 'AFE', 'marca' => 'Schick', 'linea' => 'Máquina de Afeitar Schick Exacta', 'rotacion' => 'media', 'presentaciones' => [
            'Unidad' => 2.20, 'Pack x3' => 5.80,
        ]],

        // ---------------- CUIDADO FEMENINO ----------------
        ['sub' => 'FEM', 'marca' => 'Nosotras', 'linea' => 'Toallas Higiénicas Nosotras Normal', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x8' => 3.80, 'Paquete x16' => 6.80,
        ]],
        ['sub' => 'FEM', 'marca' => 'Nosotras', 'linea' => 'Toallas Higiénicas Nosotras Nocturna', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x8' => 4.40,
        ]],
        ['sub' => 'FEM', 'marca' => 'Nosotras', 'linea' => 'Protectores Diarios Nosotras', 'presentaciones' => [
            'Paquete x15' => 3.60, 'Paquete x30' => 6.40,
        ]],
        ['sub' => 'FEM', 'marca' => 'Kotex', 'linea' => 'Toallas Higiénicas Kotex Normal', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x8' => 3.90, 'Paquete x16' => 7.00,
        ]],
        ['sub' => 'FEM', 'marca' => 'Kotex', 'linea' => 'Protectores Diarios Kotex', 'presentaciones' => [
            'Paquete x15' => 3.70,
        ]],
        ['sub' => 'FEM', 'marca' => 'Always', 'linea' => 'Toallas Higiénicas Always Ultrafina', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x8' => 4.20, 'Paquete x16' => 7.60,
        ]],
        ['sub' => 'FEM', 'marca' => 'Always', 'linea' => 'Toallas Higiénicas Always Nocturna', 'presentaciones' => [
            'Paquete x8' => 4.80,
        ]],
        ['sub' => 'FEM', 'marca' => 'Nosotras', 'linea' => 'Tampones Nosotras Regular', 'rotacion' => 'baja', 'presentaciones' => [
            'Caja x10' => 8.60,
        ]],

        // ---------------- BEBÉS ----------------
        ['sub' => 'BEB', 'marca' => 'Huggies', 'linea' => 'Pañales Huggies Active Sec Talla M', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x30' => 26.00, 'Paquete x60' => 48.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Huggies', 'linea' => 'Pañales Huggies Active Sec Talla G', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x28' => 26.00, 'Paquete x56' => 48.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Huggies', 'linea' => 'Pañales Huggies Active Sec Talla XG', 'presentaciones' => [
            'Paquete x24' => 26.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Pampers', 'linea' => 'Pañales Pampers Confort Sec Talla M', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x30' => 25.00, 'Paquete x60' => 46.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Pampers', 'linea' => 'Pañales Pampers Confort Sec Talla G', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x28' => 25.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Babysec', 'linea' => 'Pañales Babysec Ultra Talla M', 'presentaciones' => [
            'Paquete x30' => 22.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Babysec', 'linea' => 'Pañales Babysec Ultra Talla G', 'presentaciones' => [
            'Paquete x28' => 22.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Huggies', 'linea' => 'Toallitas Húmedas Huggies One & Done', 'rotacion' => 'alta', 'presentaciones' => [
            'Paquete x48' => 7.40, 'Paquete x80' => 11.50,
        ]],
        ['sub' => 'BEB', 'marca' => 'Babysec', 'linea' => 'Toallitas Húmedas Babysec', 'presentaciones' => [
            'Paquete x50' => 6.20,
        ]],
        ['sub' => 'BEB', 'marca' => 'Johnson\'s', 'linea' => 'Shampoo Johnson\'s Baby Original', 'presentaciones' => [
            '200 ml' => 12.00, '400 ml' => 20.00,
        ]],
        ['sub' => 'BEB', 'marca' => 'Johnson\'s', 'linea' => 'Jabón Johnson\'s Baby', 'presentaciones' => [
            '75 g' => 4.60,
        ]],
        ['sub' => 'BEB', 'marca' => 'Johnson\'s', 'linea' => 'Talco Johnson\'s Baby', 'presentaciones' => [
            '100 g' => 7.20, '200 g' => 11.50,
        ]],
        ['sub' => 'BEB', 'marca' => 'Hipoglós', 'linea' => 'Crema Hipoglós Original', 'rotacion' => 'media', 'presentaciones' => [
            '50 g' => 13.00,
        ]],
    ],
];
