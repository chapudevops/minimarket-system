<?php

/** Limpieza del hogar y papelería doméstica. No perecibles. */
return [
    'codigo' => 'LIM',
    'margen' => [0.15, 0.35],
    'rotacion' => 'alta',
    'vida' => 'ninguna',

    'familias' => [
        // ---------------- DETERGENTES ----------------
        ['sub' => 'DET', 'marca' => 'Ariel', 'linea' => 'Detergente Ariel Poder y Cuidado', 'presentaciones' => [
            '150 g' => 1.60, '360 g' => 3.60, '780 g' => 7.40, '2 kg' => 18.00, '4 kg' => 34.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Ariel', 'linea' => 'Detergente Líquido Ariel', 'rotacion' => 'media', 'presentaciones' => [
            '1 L' => 16.00, '3 L' => 42.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Ace', 'linea' => 'Detergente Ace Limón', 'presentaciones' => [
            '150 g' => 1.30, '360 g' => 3.00, '780 g' => 6.20, '2 kg' => 15.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Bolívar', 'linea' => 'Detergente Bolívar Floral', 'presentaciones' => [
            '150 g' => 1.20, '360 g' => 2.80, '780 g' => 5.80, '2 kg' => 14.00, '4 kg' => 26.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Bolívar', 'linea' => 'Detergente Bolívar Limón', 'presentaciones' => [
            '360 g' => 2.80, '780 g' => 5.80,
        ]],
        ['sub' => 'DET', 'marca' => 'Marsella', 'linea' => 'Detergente Marsella Original', 'presentaciones' => [
            '360 g' => 2.60, '780 g' => 5.40, '2 kg' => 13.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Sapolio', 'linea' => 'Detergente Sapolio Floral', 'presentaciones' => [
            '360 g' => 2.50, '780 g' => 5.20,
        ]],
        ['sub' => 'DET', 'marca' => 'Opal', 'linea' => 'Detergente Opal Limón', 'rotacion' => 'media', 'presentaciones' => [
            '360 g' => 2.40, '780 g' => 5.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Bolívar', 'linea' => 'Jabón Bolívar Barra', 'presentaciones' => [
            '190 g' => 1.90, '240 g' => 2.40,
        ]],
        ['sub' => 'DET', 'marca' => 'Marsella', 'linea' => 'Jabón Marsella Barra', 'presentaciones' => [
            '240 g' => 2.30,
        ]],
        ['sub' => 'DET', 'marca' => 'Trome', 'linea' => 'Jabón Trome Barra', 'presentaciones' => [
            '240 g' => 2.10,
        ]],
        ['sub' => 'DET', 'marca' => 'Downy', 'linea' => 'Suavizante Downy Aroma Floral', 'rotacion' => 'media', 'presentaciones' => [
            '400 ml' => 5.60, '800 ml' => 9.80, '1.8 L' => 19.00,
        ]],
        ['sub' => 'DET', 'marca' => 'Suavitel', 'linea' => 'Suavizante Suavitel Campo Fresco', 'rotacion' => 'media', 'presentaciones' => [
            '450 ml' => 4.80, '850 ml' => 8.40,
        ]],

        // ---------------- LEJÍA Y DESINFECTANTES ----------------
        ['sub' => 'DES', 'marca' => 'Clorox', 'linea' => 'Lejía Clorox Original', 'presentaciones' => [
            '324 ml' => 2.40, '648 ml' => 4.20, '1 L' => 5.80, '2 L' => 10.50, '4 L' => 19.00,
        ]],
        ['sub' => 'DES', 'marca' => 'Sapolio', 'linea' => 'Lejía Sapolio', 'presentaciones' => [
            '324 ml' => 2.00, '1 L' => 4.90, '4 L' => 16.00,
        ]],
        ['sub' => 'DES', 'marca' => 'Patito', 'linea' => 'Lejía Patito', 'rotacion' => 'media', 'presentaciones' => [
            '1 L' => 4.50,
        ]],
        ['sub' => 'DES', 'marca' => 'Poett', 'linea' => 'Desinfectante Poett Lavanda', 'presentaciones' => [
            '324 ml' => 3.40, '648 ml' => 5.90, '1.8 L' => 13.00,
        ]],
        ['sub' => 'DES', 'marca' => 'Poett', 'linea' => 'Desinfectante Poett Bebé', 'presentaciones' => [
            '648 ml' => 5.90,
        ]],
        ['sub' => 'DES', 'marca' => 'Sapolio', 'linea' => 'Desinfectante Sapolio Pino', 'presentaciones' => [
            '648 ml' => 4.80, '1.8 L' => 11.00,
        ]],
        ['sub' => 'DES', 'marca' => 'Pinesol', 'linea' => 'Limpiador Pinesol Original', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 9.20,
        ]],
        ['sub' => 'DES', 'marca' => 'Sapolio', 'linea' => 'Limpiatodo Sapolio Multiuso', 'presentaciones' => [
            '900 ml' => 5.40,
        ]],
        ['sub' => 'DES', 'marca' => 'Mr. Músculo', 'linea' => 'Limpiavidrios Mr. Músculo', 'rotacion' => 'media', 'presentaciones' => [
            '500 ml' => 8.20,
        ]],
        ['sub' => 'DES', 'marca' => 'Sapolio', 'linea' => 'Quitagrasa Sapolio Cocina', 'rotacion' => 'media', 'presentaciones' => [
            '500 ml' => 6.40,
        ]],
        ['sub' => 'DES', 'marca' => 'Harpic', 'linea' => 'Limpiador de Inodoros Harpic', 'rotacion' => 'media', 'presentaciones' => [
            '500 ml' => 8.60,
        ]],

        // ---------------- LAVAVAJILLAS ----------------
        ['sub' => 'LAV', 'marca' => 'Ayudín', 'linea' => 'Lavavajilla Ayudín Limón', 'presentaciones' => [
            '360 g' => 4.20, '900 g' => 8.60,
        ]],
        ['sub' => 'LAV', 'marca' => 'Sapolio', 'linea' => 'Lavavajilla Sapolio Limón', 'presentaciones' => [
            '360 g' => 3.60, '900 g' => 7.40,
        ]],
        ['sub' => 'LAV', 'marca' => 'Ayudín', 'linea' => 'Lavavajilla Líquido Ayudín', 'rotacion' => 'media', 'presentaciones' => [
            '750 ml' => 8.20,
        ]],
        ['sub' => 'LAV', 'marca' => 'Salvo', 'linea' => 'Lavavajilla Salvo Limón', 'rotacion' => 'media', 'presentaciones' => [
            '360 g' => 3.80,
        ]],

        // ---------------- ACCESORIOS DE LIMPIEZA ----------------
        ['sub' => 'ACC', 'marca' => 'Virutex', 'linea' => 'Esponja Virutex Doble Uso', 'presentaciones' => [
            'Unidad' => 1.40, 'Pack x3' => 3.60,
        ]],
        ['sub' => 'ACC', 'marca' => 'Scotch-Brite', 'linea' => 'Esponja Scotch-Brite Multiuso', 'presentaciones' => [
            'Unidad' => 2.20, 'Pack x3' => 5.80,
        ]],
        ['sub' => 'ACC', 'marca' => 'Virutex', 'linea' => 'Paño Absorbente Virutex', 'rotacion' => 'media', 'presentaciones' => [
            'Unidad' => 2.60,
        ]],
        ['sub' => 'ACC', 'marca' => 'Virutex', 'linea' => 'Guantes de Limpieza Virutex', 'rotacion' => 'media', 'presentaciones' => [
            'Par talla M' => 5.40, 'Par talla L' => 5.40,
        ]],
        ['sub' => 'ACC', 'marca' => 'Genérico', 'linea' => 'Escoba de Cerdas Plásticas', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 9.00,
        ]],
        ['sub' => 'ACC', 'marca' => 'Genérico', 'linea' => 'Recogedor de Plástico', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 6.50,
        ]],
        ['sub' => 'ACC', 'marca' => 'Genérico', 'linea' => 'Trapeador de Microfibra', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 12.00,
        ]],
        ['sub' => 'ACC', 'marca' => 'Sapolio', 'linea' => 'Bolsa de Basura Sapolio Mediana', 'presentaciones' => [
            'Rollo x10' => 3.40, 'Rollo x20' => 6.20,
        ]],
        ['sub' => 'ACC', 'marca' => 'Sapolio', 'linea' => 'Bolsa de Basura Sapolio Grande', 'presentaciones' => [
            'Rollo x10' => 4.60, 'Rollo x20' => 8.40,
        ]],

        // ---------------- PAPELERÍA DEL HOGAR ----------------
        ['sub' => 'PAP', 'marca' => 'Elite', 'linea' => 'Papel Higiénico Elite Doble Hoja', 'presentaciones' => [
            'Paquete x4' => 4.80, 'Paquete x8' => 9.20, 'Paquete x12' => 13.50, 'Paquete x24' => 25.00,
        ]],
        ['sub' => 'PAP', 'marca' => 'Elite', 'linea' => 'Papel Higiénico Elite Triple Hoja', 'presentaciones' => [
            'Paquete x4' => 6.20, 'Paquete x12' => 17.00,
        ]],
        ['sub' => 'PAP', 'marca' => 'Suave', 'linea' => 'Papel Higiénico Suave Doble Hoja', 'presentaciones' => [
            'Paquete x4' => 4.20, 'Paquete x12' => 12.00,
        ]],
        ['sub' => 'PAP', 'marca' => 'Paracas', 'linea' => 'Papel Higiénico Paracas Económico', 'presentaciones' => [
            'Paquete x4' => 3.40, 'Paquete x12' => 9.80,
        ]],
        ['sub' => 'PAP', 'marca' => 'Noble', 'linea' => 'Papel Higiénico Noble Doble Hoja', 'presentaciones' => [
            'Paquete x4' => 3.80, 'Paquete x12' => 11.00,
        ]],
        ['sub' => 'PAP', 'marca' => 'Elite', 'linea' => 'Papel Toalla Elite Cocina', 'presentaciones' => [
            'Rollo x1' => 3.60, 'Pack x2' => 6.80,
        ]],
        ['sub' => 'PAP', 'marca' => 'Suave', 'linea' => 'Papel Toalla Suave Cocina', 'presentaciones' => [
            'Rollo x1' => 3.20, 'Pack x2' => 6.00,
        ]],
        ['sub' => 'PAP', 'marca' => 'Elite', 'linea' => 'Servilletas Elite', 'presentaciones' => [
            'Paquete 100 unidades' => 3.40, 'Paquete 200 unidades' => 6.20,
        ]],
        ['sub' => 'PAP', 'marca' => 'Nova', 'linea' => 'Servilletas Nova', 'presentaciones' => [
            'Paquete 100 unidades' => 2.80,
        ]],
        ['sub' => 'PAP', 'marca' => 'Elite', 'linea' => 'Pañuelos Faciales Elite', 'rotacion' => 'media', 'presentaciones' => [
            'Caja 100 unidades' => 4.20, 'Paquete de bolsillo' => 0.80,
        ]],
    ],
];
