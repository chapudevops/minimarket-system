<?php

/** Arroz, azúcar, aceites, fideos, harinas, menestras, conservas y condimentos. */
return [
    'codigo' => 'ABA',
    'margen' => [0.10, 0.25],
    'rotacion' => 'alta',
    'vida' => 'larga',

    'familias' => [
        // ---------------- ARROZ ----------------
        ['sub' => 'ARR', 'marca' => 'Costeño', 'linea' => 'Arroz Costeño Extra', 'presentaciones' => [
            '750 g' => 3.40, '1 kg' => 4.40, '5 kg' => 21.00,
        ]],
        ['sub' => 'ARR', 'marca' => 'Costeño', 'linea' => 'Arroz Costeño Superior', 'presentaciones' => [
            '1 kg' => 4.10, '5 kg' => 19.50,
        ]],
        ['sub' => 'ARR', 'marca' => 'Costeño', 'linea' => 'Arroz Costeño Integral', 'rotacion' => 'media', 'presentaciones' => [
            '750 g' => 4.60, '1 kg' => 5.90,
        ]],
        ['sub' => 'ARR', 'marca' => 'Paisana', 'linea' => 'Arroz Paisana Extra', 'presentaciones' => [
            '750 g' => 3.30, '1 kg' => 4.30, '5 kg' => 20.50,
        ]],
        ['sub' => 'ARR', 'marca' => 'Faraón', 'linea' => 'Arroz Faraón Extra', 'presentaciones' => [
            '750 g' => 3.35, '1 kg' => 4.35, '5 kg' => 20.80,
        ]],
        ['sub' => 'ARR', 'marca' => 'Valle Norte', 'linea' => 'Arroz Valle Norte Superior', 'presentaciones' => [
            '1 kg' => 4.00, '5 kg' => 19.00,
        ]],
        ['sub' => 'ARR', 'marca' => 'Doña Isabel', 'linea' => 'Arroz Doña Isabel Extra', 'rotacion' => 'media', 'presentaciones' => [
            '1 kg' => 4.20, '5 kg' => 20.00,
        ]],
        ['sub' => 'ARR', 'marca' => 'Molino Rojo', 'linea' => 'Arroz Molino Rojo Superior', 'rotacion' => 'media', 'presentaciones' => [
            '1 kg' => 4.05, '5 kg' => 19.20,
        ]],

        // ---------------- AZÚCAR ----------------
        ['sub' => 'AZU', 'marca' => 'Cartavio', 'linea' => 'Azúcar Rubia Cartavio', 'presentaciones' => [
            '500 g' => 2.10, '1 kg' => 3.80, '5 kg' => 17.50,
        ]],
        ['sub' => 'AZU', 'marca' => 'Cartavio', 'linea' => 'Azúcar Blanca Cartavio', 'presentaciones' => [
            '500 g' => 2.30, '1 kg' => 4.20, '5 kg' => 19.50,
        ]],
        ['sub' => 'AZU', 'marca' => 'Casa Grande', 'linea' => 'Azúcar Rubia Casa Grande', 'presentaciones' => [
            '1 kg' => 3.70, '5 kg' => 17.00,
        ]],
        ['sub' => 'AZU', 'marca' => 'Paramonga', 'linea' => 'Azúcar Rubia Paramonga', 'rotacion' => 'media', 'presentaciones' => [
            '1 kg' => 3.75, '5 kg' => 17.20,
        ]],
        ['sub' => 'AZU', 'marca' => 'Bell\'s', 'linea' => 'Azúcar Blanca Bell\'s', 'rotacion' => 'media', 'presentaciones' => [
            '1 kg' => 4.10,
        ]],

        // ---------------- ACEITES ----------------
        ['sub' => 'ACE', 'marca' => 'Primor', 'linea' => 'Aceite Vegetal Primor Premium', 'presentaciones' => [
            '500 ml' => 5.20, '900 ml' => 8.20, '1 L' => 9.00, '5 L' => 41.00,
        ]],
        ['sub' => 'ACE', 'marca' => 'Primor', 'linea' => 'Aceite Primor Girasol', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 9.40, '1 L' => 10.20,
        ]],
        ['sub' => 'ACE', 'marca' => 'Cocinero', 'linea' => 'Aceite Vegetal Cocinero', 'presentaciones' => [
            '500 ml' => 4.90, '900 ml' => 7.80, '1 L' => 8.60, '5 L' => 39.00,
        ]],
        ['sub' => 'ACE', 'marca' => 'Sao', 'linea' => 'Aceite Vegetal Sao', 'presentaciones' => [
            '500 ml' => 4.70, '900 ml' => 7.50, '1 L' => 8.30,
        ]],
        ['sub' => 'ACE', 'marca' => 'Capri', 'linea' => 'Aceite Vegetal Capri', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 7.60, '1 L' => 8.40,
        ]],
        ['sub' => 'ACE', 'marca' => 'Deleite', 'linea' => 'Aceite Vegetal Deleite', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 7.40, '1 L' => 8.10,
        ]],
        ['sub' => 'ACE', 'marca' => 'Olivar del Sur', 'linea' => 'Aceite de Oliva Olivar del Sur Extra Virgen', 'rotacion' => 'baja', 'presentaciones' => [
            '250 ml' => 14.00, '500 ml' => 25.00,
        ]],

        // ---------------- FIDEOS ----------------
        ['sub' => 'FID', 'marca' => 'Don Vittorio', 'linea' => 'Fideo Don Vittorio Spaghetti', 'presentaciones' => [
            '250 g' => 1.90, '500 g' => 3.20,
        ]],
        ['sub' => 'FID', 'marca' => 'Don Vittorio', 'linea' => 'Fideo Don Vittorio Tallarín', 'presentaciones' => [
            '250 g' => 1.90, '500 g' => 3.20,
        ]],
        ['sub' => 'FID', 'marca' => 'Don Vittorio', 'linea' => 'Fideo Don Vittorio Codito', 'presentaciones' => [
            '250 g' => 1.90, '500 g' => 3.20,
        ]],
        ['sub' => 'FID', 'marca' => 'Don Vittorio', 'linea' => 'Fideo Don Vittorio Tornillo', 'presentaciones' => [
            '250 g' => 1.90, '500 g' => 3.20,
        ]],
        ['sub' => 'FID', 'marca' => 'Don Vittorio', 'linea' => 'Fideo Don Vittorio Cabello de Ángel', 'presentaciones' => [
            '250 g' => 1.95, '500 g' => 3.30,
        ]],
        ['sub' => 'FID', 'marca' => 'Molitalia', 'linea' => 'Fideo Molitalia Spaghetti', 'presentaciones' => [
            '250 g' => 1.70, '500 g' => 2.90,
        ]],
        ['sub' => 'FID', 'marca' => 'Molitalia', 'linea' => 'Fideo Molitalia Canuto', 'presentaciones' => [
            '250 g' => 1.70, '500 g' => 2.90,
        ]],
        ['sub' => 'FID', 'marca' => 'Molitalia', 'linea' => 'Fideo Molitalia Sopa Cabello', 'presentaciones' => [
            '250 g' => 1.65,
        ]],
        ['sub' => 'FID', 'marca' => 'Lavaggi', 'linea' => 'Fideo Lavaggi Tallarín', 'presentaciones' => [
            '250 g' => 1.60, '500 g' => 2.75,
        ]],
        ['sub' => 'FID', 'marca' => 'Lavaggi', 'linea' => 'Fideo Lavaggi Codito', 'presentaciones' => [
            '250 g' => 1.60, '500 g' => 2.75,
        ]],
        ['sub' => 'FID', 'marca' => 'Nicolini', 'linea' => 'Fideo Nicolini Spaghetti', 'presentaciones' => [
            '250 g' => 1.80, '500 g' => 3.05,
        ]],
        ['sub' => 'FID', 'marca' => 'Nicolini', 'linea' => 'Fideo Nicolini Tallarín Grueso', 'presentaciones' => [
            '250 g' => 1.80, '500 g' => 3.05,
        ]],
        ['sub' => 'FID', 'marca' => 'Alianza', 'linea' => 'Fideo Alianza Sopa Letras', 'rotacion' => 'media', 'presentaciones' => [
            '250 g' => 1.55,
        ]],
        ['sub' => 'FID', 'marca' => 'Marco Polo', 'linea' => 'Fideo Marco Polo Tallarín', 'rotacion' => 'media', 'presentaciones' => [
            '500 g' => 2.80,
        ]],

        // ---------------- HARINAS ----------------
        ['sub' => 'HAR', 'marca' => 'Blanca Flor', 'linea' => 'Harina Preparada Blanca Flor', 'presentaciones' => [
            '500 g' => 2.60, '1 kg' => 4.60,
        ]],
        ['sub' => 'HAR', 'marca' => 'Blanca Flor', 'linea' => 'Harina Sin Preparar Blanca Flor', 'presentaciones' => [
            '1 kg' => 4.30,
        ]],
        ['sub' => 'HAR', 'marca' => 'Nicolini', 'linea' => 'Harina Preparada Nicolini', 'presentaciones' => [
            '1 kg' => 4.40,
        ]],
        ['sub' => 'HAR', 'marca' => 'Favorita', 'linea' => 'Harina de Trigo Favorita', 'presentaciones' => [
            '1 kg' => 4.20,
        ]],
        ['sub' => 'HAR', 'marca' => 'Anita', 'linea' => 'Harina de Maíz Anita', 'rotacion' => 'media', 'presentaciones' => [
            '180 g' => 1.80, '500 g' => 4.00,
        ]],
        ['sub' => 'HAR', 'marca' => 'Maizena', 'linea' => 'Maicena Maizena', 'presentaciones' => [
            '200 g' => 2.80, '500 g' => 6.20,
        ]],

        // ---------------- MENESTRAS ----------------
        ['sub' => 'MEN', 'marca' => 'Costeño', 'linea' => 'Lenteja Costeño', 'presentaciones' => [
            '500 g' => 3.80, '1 kg' => 7.20,
        ]],
        ['sub' => 'MEN', 'marca' => 'Costeño', 'linea' => 'Frejol Canario Costeño', 'presentaciones' => [
            '500 g' => 4.40, '1 kg' => 8.40,
        ]],
        ['sub' => 'MEN', 'marca' => 'Costeño', 'linea' => 'Frejol Panamito Costeño', 'presentaciones' => [
            '500 g' => 4.60,
        ]],
        ['sub' => 'MEN', 'marca' => 'Costeño', 'linea' => 'Garbanzo Costeño', 'rotacion' => 'media', 'presentaciones' => [
            '500 g' => 4.80,
        ]],
        ['sub' => 'MEN', 'marca' => 'Costeño', 'linea' => 'Arveja Partida Costeño', 'presentaciones' => [
            '500 g' => 3.60,
        ]],
        ['sub' => 'MEN', 'marca' => 'Valle Norte', 'linea' => 'Lenteja Valle Norte', 'presentaciones' => [
            '500 g' => 3.60, '1 kg' => 6.90,
        ]],
        ['sub' => 'MEN', 'marca' => 'Valle Norte', 'linea' => 'Frejol Castilla Valle Norte', 'rotacion' => 'media', 'presentaciones' => [
            '500 g' => 4.20,
        ]],
        ['sub' => 'MEN', 'marca' => 'Valle Norte', 'linea' => 'Pallar Valle Norte', 'rotacion' => 'baja', 'presentaciones' => [
            '500 g' => 5.20,
        ]],
        ['sub' => 'MEN', 'marca' => 'Doña Isabel', 'linea' => 'Frejol Canario Doña Isabel', 'rotacion' => 'media', 'presentaciones' => [
            '500 g' => 4.30,
        ]],

        // ---------------- CONSERVAS ----------------
        ['sub' => 'CON', 'marca' => 'Florida', 'linea' => 'Atún Florida Filete en Aceite', 'presentaciones' => [
            '170 g' => 5.60, '425 g' => 12.00,
        ]],
        ['sub' => 'CON', 'marca' => 'Florida', 'linea' => 'Atún Florida Trozos en Agua', 'presentaciones' => [
            '170 g' => 5.40,
        ]],
        ['sub' => 'CON', 'marca' => 'Campomar', 'linea' => 'Atún Campomar Filete en Aceite', 'presentaciones' => [
            '170 g' => 5.20,
        ]],
        ['sub' => 'CON', 'marca' => 'Real', 'linea' => 'Atún Real Trozos en Aceite', 'presentaciones' => [
            '170 g' => 5.00,
        ]],
        ['sub' => 'CON', 'marca' => 'A1', 'linea' => 'Atún A1 Filete en Aceite', 'presentaciones' => [
            '170 g' => 5.10,
        ]],
        ['sub' => 'CON', 'marca' => 'Florida', 'linea' => 'Caballa Florida en Salsa de Tomate', 'presentaciones' => [
            '425 g' => 6.40,
        ]],
        ['sub' => 'CON', 'marca' => 'Campomar', 'linea' => 'Caballa Campomar en Agua y Sal', 'presentaciones' => [
            '425 g' => 6.10,
        ]],
        ['sub' => 'CON', 'marca' => 'Primor', 'linea' => 'Sardina Primor en Salsa de Tomate', 'rotacion' => 'media', 'presentaciones' => [
            '425 g' => 5.80,
        ]],
        ['sub' => 'CON', 'marca' => 'Gloria', 'linea' => 'Durazno Gloria en Almíbar', 'rotacion' => 'media', 'presentaciones' => [
            '820 g' => 8.40,
        ]],
        ['sub' => 'CON', 'marca' => 'Aconcagua', 'linea' => 'Piña Aconcagua en Rodajas', 'rotacion' => 'baja', 'presentaciones' => [
            '565 g' => 7.20,
        ]],
        ['sub' => 'CON', 'marca' => 'Fanny', 'linea' => 'Atún Fanny Filete en Aceite', 'presentaciones' => [
            '170 g' => 5.30,
        ]],
        ['sub' => 'CON', 'marca' => 'Costa', 'linea' => 'Arvejas Costa en Conserva', 'rotacion' => 'media', 'presentaciones' => [
            '400 g' => 4.20,
        ]],
        ['sub' => 'CON', 'marca' => 'Costa', 'linea' => 'Choclo Costa en Grano', 'rotacion' => 'media', 'presentaciones' => [
            '400 g' => 4.40,
        ]],

        // ---------------- SALSAS Y CONDIMENTOS ----------------
        ['sub' => 'SAL', 'marca' => 'Alacena', 'linea' => 'Mayonesa Alacena', 'presentaciones' => [
            '95 g' => 2.20, '200 g' => 4.20, '400 g' => 7.60, '950 g' => 15.50,
        ]],
        ['sub' => 'SAL', 'marca' => 'Alacena', 'linea' => 'Crema de Ají Alacena', 'presentaciones' => [
            '85 g' => 2.30, '400 g' => 8.00,
        ]],
        ['sub' => 'SAL', 'marca' => 'Alacena', 'linea' => 'Crema Huancaína Alacena', 'presentaciones' => [
            '400 g' => 8.20,
        ]],
        ['sub' => 'SAL', 'marca' => 'Alacena', 'linea' => 'Ketchup Alacena', 'presentaciones' => [
            '200 g' => 3.60, '380 g' => 6.20,
        ]],
        ['sub' => 'SAL', 'marca' => 'Alacena', 'linea' => 'Mostaza Alacena', 'rotacion' => 'media', 'presentaciones' => [
            '200 g' => 3.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Libby\'s', 'linea' => 'Ketchup Libby\'s', 'rotacion' => 'media', 'presentaciones' => [
            '397 g' => 5.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Pomarola', 'linea' => 'Salsa de Tomate Pomarola', 'presentaciones' => [
            '160 g' => 2.10, '340 g' => 3.90,
        ]],
        ['sub' => 'SAL', 'marca' => 'Kikkoman', 'linea' => 'Sillao Kikkoman', 'rotacion' => 'media', 'presentaciones' => [
            '150 ml' => 6.20,
        ]],
        ['sub' => 'SAL', 'marca' => 'Wong Food', 'linea' => 'Sillao Wong Food', 'presentaciones' => [
            '160 ml' => 3.20, '500 ml' => 7.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Venus', 'linea' => 'Vinagre Blanco Venus', 'presentaciones' => [
            '500 ml' => 2.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Venus', 'linea' => 'Vinagre Tinto Venus', 'rotacion' => 'media', 'presentaciones' => [
            '500 ml' => 2.60,
        ]],
        ['sub' => 'SAL', 'marca' => 'Emsal', 'linea' => 'Sal de Mesa Emsal Yodada', 'presentaciones' => [
            '500 g' => 1.10, '1 kg' => 1.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Marina', 'linea' => 'Sal Marina Marina', 'rotacion' => 'media', 'presentaciones' => [
            '1 kg' => 1.90,
        ]],
        ['sub' => 'SAL', 'marca' => 'Sibarita', 'linea' => 'Sazonador Sibarita Ají Panca', 'presentaciones' => [
            'Sobre 8 g' => 0.50, 'Caja 12 sobres' => 5.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Sibarita', 'linea' => 'Pimienta Sibarita Molida', 'presentaciones' => [
            'Sobre 8 g' => 0.50,
        ]],
        ['sub' => 'SAL', 'marca' => 'Sibarita', 'linea' => 'Comino Sibarita Molido', 'presentaciones' => [
            'Sobre 8 g' => 0.50,
        ]],
        ['sub' => 'SAL', 'marca' => 'Sibarita', 'linea' => 'Orégano Sibarita', 'presentaciones' => [
            'Sobre 8 g' => 0.50,
        ]],
        ['sub' => 'SAL', 'marca' => 'Ajinomoto', 'linea' => 'Ajinomoto Sazonador', 'presentaciones' => [
            'Sobre 10 g' => 0.40, '100 g' => 3.20,
        ]],
        ['sub' => 'SAL', 'marca' => 'Ajinomen', 'linea' => 'Sopa Instantánea Ajinomen Gallina', 'presentaciones' => [
            'Sobre 68 g' => 1.60,
        ]],
        ['sub' => 'SAL', 'marca' => 'Maggi', 'linea' => 'Caldo Maggi Gallina', 'presentaciones' => [
            'Cubo 11 g' => 0.40, 'Caja 8 cubos' => 2.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Maggi', 'linea' => 'Caldo Maggi Carne', 'presentaciones' => [
            'Cubo 11 g' => 0.40, 'Caja 8 cubos' => 2.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Doña Gusta', 'linea' => 'Sazonador Doña Gusta Gallina', 'presentaciones' => [
            'Sobre 10 g' => 0.40, 'Caja 12 sobres' => 4.20,
        ]],
        ['sub' => 'SAL', 'marca' => 'Doña Gusta', 'linea' => 'Sazonador Doña Gusta Carne', 'presentaciones' => [
            'Sobre 10 g' => 0.40,
        ]],
        ['sub' => 'CON', 'marca' => 'Florida', 'linea' => 'Atún Florida Desmenuzado', 'presentaciones' => [
            '170 g' => 4.80,
        ]],
        ['sub' => 'CON', 'marca' => 'Gloria', 'linea' => 'Piña Gloria en Almíbar', 'rotacion' => 'baja', 'presentaciones' => [
            '820 g' => 8.20,
        ]],
        ['sub' => 'CON', 'marca' => 'Costa', 'linea' => 'Champiñones Costa en Conserva', 'rotacion' => 'baja', 'presentaciones' => [
            '400 g' => 6.80,
        ]],
        ['sub' => 'SAL', 'marca' => 'Alacena', 'linea' => 'Crema de Rocoto Alacena', 'presentaciones' => [
            '85 g' => 2.30, '400 g' => 8.00,
        ]],
        ['sub' => 'SAL', 'marca' => 'Tarí', 'linea' => 'Crema de Ají Tarí', 'presentaciones' => [
            '85 g' => 2.10, '400 g' => 7.40,
        ]],
        ['sub' => 'SAL', 'marca' => 'Sibarita', 'linea' => 'Palillo Sibarita', 'presentaciones' => [
            'Sobre 8 g' => 0.50,
        ]],
        ['sub' => 'SAL', 'marca' => 'Sibarita', 'linea' => 'Ají Amarillo Sibarita Molido', 'presentaciones' => [
            'Sobre 8 g' => 0.50,
        ]],
        ['sub' => 'FID', 'marca' => 'Don Vittorio', 'linea' => 'Fideo Don Vittorio Corbata', 'presentaciones' => [
            '250 g' => 1.90, '500 g' => 3.20,
        ]],
        ['sub' => 'FID', 'marca' => 'Molitalia', 'linea' => 'Fideo Molitalia Tornillo', 'presentaciones' => [
            '250 g' => 1.70, '500 g' => 2.90,
        ]],
        ['sub' => 'ARR', 'marca' => 'Paisana', 'linea' => 'Arroz Paisana Superior', 'presentaciones' => [
            '1 kg' => 4.10, '5 kg' => 19.50,
        ]],
        ['sub' => 'MEN', 'marca' => 'Valle Norte', 'linea' => 'Garbanzo Valle Norte', 'rotacion' => 'media', 'presentaciones' => [
            '500 g' => 4.60,
        ]],
        ['sub' => 'ACE', 'marca' => 'Ideal', 'linea' => 'Aceite Vegetal Ideal', 'rotacion' => 'media', 'presentaciones' => [
            '900 ml' => 7.70, '1 L' => 8.40,
        ]],
    ],
];
