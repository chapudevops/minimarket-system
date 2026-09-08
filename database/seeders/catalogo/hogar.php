<?php

/** Mascotas, descartables, útiles, pilas y accesorios de cocina. */
return [
    'codigo' => 'HOG',
    'margen' => [0.20, 0.38],
    'rotacion' => 'media',
    'vida' => 'ninguna',

    'familias' => [
        // ---------------- MASCOTAS ----------------
        ['sub' => 'MAS', 'marca' => 'Ricocan', 'linea' => 'Alimento Ricocan Adultos Carne', 'vida' => 'larga', 'rotacion' => 'alta', 'presentaciones' => [
            '500 g' => 4.20, '1 kg' => 7.80, '4 kg' => 28.00, '8 kg' => 52.00, '15 kg' => 92.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Ricocan', 'linea' => 'Alimento Ricocan Cachorros', 'vida' => 'larga', 'presentaciones' => [
            '1 kg' => 8.40, '4 kg' => 30.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Mimaskot', 'linea' => 'Alimento Mimaskot Adultos', 'vida' => 'larga', 'rotacion' => 'alta', 'presentaciones' => [
            '500 g' => 4.00, '1 kg' => 7.40, '4 kg' => 26.00, '8 kg' => 49.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Mimaskot', 'linea' => 'Alimento Mimaskot Cachorros', 'vida' => 'larga', 'presentaciones' => [
            '1 kg' => 8.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Pedigree', 'linea' => 'Alimento Pedigree Adultos Carne', 'vida' => 'larga', 'presentaciones' => [
            '1.5 kg' => 16.00, '4 kg' => 38.00, '15 kg' => 128.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Pedigree', 'linea' => 'Alimento Pedigree Cachorros', 'vida' => 'larga', 'rotacion' => 'baja', 'presentaciones' => [
            '1.5 kg' => 17.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Ricocat', 'linea' => 'Alimento Ricocat Adultos Pescado', 'vida' => 'larga', 'presentaciones' => [
            '500 g' => 5.20, '1 kg' => 9.60, '3 kg' => 26.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Cat Chow', 'linea' => 'Alimento Cat Chow Adultos', 'vida' => 'larga', 'rotacion' => 'baja', 'presentaciones' => [
            '1 kg' => 14.00, '3 kg' => 36.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Whiskas', 'linea' => 'Alimento Whiskas Adultos Carne', 'vida' => 'larga', 'presentaciones' => [
            '500 g' => 7.20, '1.5 kg' => 19.00,
        ]],
        ['sub' => 'MAS', 'marca' => 'Whiskas', 'linea' => 'Alimento Húmedo Whiskas Sobre', 'vida' => 'larga', 'presentaciones' => [
            'Sobre 85 g' => 2.40,
        ]],
        ['sub' => 'MAS', 'marca' => 'Ricocan', 'linea' => 'Snack Ricocan Huesitos', 'vida' => 'larga', 'rotacion' => 'baja', 'presentaciones' => [
            '100 g' => 4.60,
        ]],
        ['sub' => 'MAS', 'marca' => 'Genérico', 'linea' => 'Arena Sanitaria para Gatos', 'rotacion' => 'baja', 'presentaciones' => [
            '4 kg' => 14.00, '10 kg' => 30.00,
        ]],

        // ---------------- DESCARTABLES ----------------
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Vasos Descartables 7 oz', 'presentaciones' => [
            'Paquete x25' => 2.20, 'Paquete x50' => 3.80, 'Paquete x100' => 6.80,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Vasos Descartables 12 oz', 'presentaciones' => [
            'Paquete x25' => 3.20, 'Paquete x50' => 5.60,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Platos Descartables Medianos', 'presentaciones' => [
            'Paquete x25' => 3.60, 'Paquete x50' => 6.40,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Cubiertos Descartables Surtidos', 'presentaciones' => [
            'Paquete x25' => 3.00,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Sorbetes Descartables', 'presentaciones' => [
            'Paquete x50' => 1.80, 'Paquete x100' => 3.20,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Envases Descartables con Tapa', 'presentaciones' => [
            'Paquete x25' => 8.40,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Bolsas Camiseta Medianas', 'presentaciones' => [
            'Paquete x100' => 4.20,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Bolsas Camiseta Grandes', 'presentaciones' => [
            'Paquete x100' => 6.00,
        ]],
        ['sub' => 'DSC', 'marca' => 'Reynolds', 'linea' => 'Papel Aluminio Reynolds', 'presentaciones' => [
            'Rollo 7.5 m' => 8.20, 'Rollo 15 m' => 14.00,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Film Plástico para Alimentos', 'presentaciones' => [
            'Rollo 20 m' => 6.40,
        ]],
        ['sub' => 'DSC', 'marca' => 'Genérico', 'linea' => 'Bolsas Herméticas Medianas', 'rotacion' => 'baja', 'presentaciones' => [
            'Paquete x20' => 5.80,
        ]],

        // ---------------- ÚTILES ----------------
        ['sub' => 'UTI', 'marca' => 'Faber-Castell', 'linea' => 'Lapicero Faber-Castell Trilux Azul', 'presentaciones' => [
            'Unidad' => 1.60, 'Caja x12' => 16.00,
        ]],
        ['sub' => 'UTI', 'marca' => 'Faber-Castell', 'linea' => 'Lapicero Faber-Castell Trilux Negro', 'presentaciones' => [
            'Unidad' => 1.60,
        ]],
        ['sub' => 'UTI', 'marca' => 'Faber-Castell', 'linea' => 'Lápiz Faber-Castell 2B', 'presentaciones' => [
            'Unidad' => 1.20, 'Caja x12' => 12.00,
        ]],
        ['sub' => 'UTI', 'marca' => 'Faber-Castell', 'linea' => 'Borrador Faber-Castell Blanco', 'presentaciones' => [
            'Unidad' => 1.00,
        ]],
        ['sub' => 'UTI', 'marca' => 'Faber-Castell', 'linea' => 'Tajador Faber-Castell', 'presentaciones' => [
            'Unidad' => 1.40,
        ]],
        ['sub' => 'UTI', 'marca' => 'Standford', 'linea' => 'Cuaderno Standford Cuadriculado A4', 'rotacion' => 'baja', 'presentaciones' => [
            '80 hojas' => 5.60, '100 hojas' => 6.80,
        ]],
        ['sub' => 'UTI', 'marca' => 'Justus', 'linea' => 'Cuaderno Justus Rayado A4', 'rotacion' => 'baja', 'presentaciones' => [
            '80 hojas' => 5.20,
        ]],
        ['sub' => 'UTI', 'marca' => 'Genérico', 'linea' => 'Cinta Adhesiva Transparente', 'presentaciones' => [
            'Rollo pequeño' => 1.60, 'Rollo grande' => 3.40,
        ]],
        ['sub' => 'UTI', 'marca' => 'Tekbond', 'linea' => 'Goma en Barra Tekbond', 'presentaciones' => [
            '20 g' => 3.20,
        ]],
        ['sub' => 'UTI', 'marca' => 'Artesco', 'linea' => 'Corrector Líquido Artesco', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 3.80,
        ]],

        // ---------------- PILAS Y ACCESORIOS ----------------
        ['sub' => 'PIL', 'marca' => 'Duracell', 'linea' => 'Pila Duracell AA', 'presentaciones' => [
            'Blister x2' => 8.40, 'Blister x4' => 15.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Duracell', 'linea' => 'Pila Duracell AAA', 'presentaciones' => [
            'Blister x2' => 8.40, 'Blister x4' => 15.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Duracell', 'linea' => 'Batería Duracell 9V', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 12.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Energizer', 'linea' => 'Pila Energizer AA', 'presentaciones' => [
            'Blister x2' => 8.00, 'Blister x4' => 14.50,
        ]],
        ['sub' => 'PIL', 'marca' => 'Energizer', 'linea' => 'Pila Energizer AAA', 'presentaciones' => [
            'Blister x2' => 8.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Panasonic', 'linea' => 'Pila Panasonic AA', 'presentaciones' => [
            'Blister x2' => 5.60, 'Blister x4' => 10.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Bic', 'linea' => 'Encendedor Bic Clásico', 'rotacion' => 'alta', 'presentaciones' => [
            'Unidad' => 2.20,
        ]],
        ['sub' => 'PIL', 'marca' => 'Llama', 'linea' => 'Fósforos Llama', 'rotacion' => 'alta', 'presentaciones' => [
            'Caja' => 0.60, 'Paquete x10 cajas' => 5.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Philips', 'linea' => 'Foco LED Philips 9W Luz Fría', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 9.80,
        ]],
        ['sub' => 'PIL', 'marca' => 'Opalux', 'linea' => 'Foco LED Opalux 12W Luz Cálida', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 8.20,
        ]],
        ['sub' => 'PIL', 'marca' => 'Genérico', 'linea' => 'Cable USB Tipo C 1 m', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 9.00,
        ]],
        ['sub' => 'PIL', 'marca' => 'Genérico', 'linea' => 'Cable USB Micro 1 m', 'rotacion' => 'baja', 'presentaciones' => [
            'Unidad' => 7.50,
        ]],
    ],
];
