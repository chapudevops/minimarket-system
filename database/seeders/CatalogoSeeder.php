<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->clientes();
            $this->proveedores();
            $this->productos();
        });
    }

    private function clientes(): void
    {
        $clientes = [
            ['DNI', '44556677', 'Rosa Quispe Mamani',              'Jr. Puno 456, La Molina',        '987654321'],
            ['DNI', '41239876', 'Carlos Huaman Rojas',             'Av. Javier Prado 890',           '963258741'],
            ['DNI', '09876543', 'Maria Elena Torres Vega',         'Calle Los Cedros 122',           '954871236'],
            ['DNI', '72635412', 'Jorge Antonio Salazar Diaz',      'Av. La Fontana 780',             '912345678'],
            ['DNI', '48127390', 'Ana Lucia Ramirez Soto',          'Jr. Las Magnolias 45',           '976543210'],
            ['DNI', '10293847', 'Pedro Castillo Ninahuanca',       'Av. Separadora Industrial 2200', '945612378'],
            ['RUC', '20548812345', 'INVERSIONES EL ROBLE S.A.C.',  'Av. Industrial 455, Ate',        '013456789'],
            ['RUC', '20601234567', 'DISTRIBUIDORA ANDINA E.I.R.L.', 'Av. Nicolas Ayllon 3100, Ate',   '014567890'],
            ['RUC', '10412398765', 'BODEGA SAN MARTIN',            'Jr. Union 234, Cercado',         '015678901'],
            ['CE',  '001234567',   'Diego Fernando Ramos Pena',    'Av. Aviacion 1500, San Borja',   '938271654'],
            ['DNI', '00000000',    'CLIENTE VARIOS',               'Venta al publico',               null],
        ];

        foreach ($clientes as [$tipo, $doc, $nombre, $direccion, $telefono]) {
            DB::table('clientes')->updateOrInsert(
                ['numero_documento' => $doc],
                [
                    'tipo_documento' => $tipo,
                    'nombre_razon_social' => $nombre,
                    'direccion' => $direccion,
                    'telefono' => $telefono,
                    'departamento' => 'Lima',
                    'provincia' => 'Lima',
                    'distrito' => 'La Molina',
                    'estado' => 1,
                ]
            );
        }
    }

    private function proveedores(): void
    {
        $proveedores = [
            ['20100055237', 'ALICORP S.A.A.',                     'Av. Argentina 4793, Callao'],
            ['20100190797', 'GLORIA S.A.',                        'Av. Republica de Panama 2461'],
            ['20100128056', 'BACKUS Y JOHNSTON S.A.A.',           'Av. Nicolas Ayllon 3986, Ate'],
            ['20331066703', 'DISTRIBUIDORA NORTE PACASMAYO',      'Av. Los Frutales 220, Ate'],
            ['20605544321', 'COMERCIAL LIMA NORTE S.A.C.',        'Av. Tupac Amaru 1450, Comas'],
            ['20512998877', 'ABARROTES EL SOL E.I.R.L.',          'Mercado Mayorista, Santa Anita'],
        ];

        foreach ($proveedores as [$ruc, $nombre, $direccion]) {
            DB::table('proveedores')->updateOrInsert(
                ['numero_documento' => $ruc],
                [
                    'tipo_documento' => 'RUC',
                    'nombre_razon_social' => $nombre,
                    'direccion' => $direccion,
                    'telefono' => '01'.random_int(2000000, 7999999),
                    'departamento' => 'Lima',
                    'provincia' => 'Lima',
                    'distrito' => 'Lima',
                    'estado' => 1,
                ]
            );
        }
    }

    private function productos(): void
    {
        // [codigo, descripcion, marca, presentacion, unidad, compra, venta, stock, stock_minimo]
        //
        // NO hay columna de codigo de barras y no debe volver a haberla con
        // datos inventados. Estas 33 filas llevaban EAN sinteticos con prefijo
        // peruano (775...) que parecian reales: el lector los habria dado por
        // buenos y el dia que entrara el producto de verdad, con su EAN real,
        // habria dos filas para el mismo articulo.
        //
        // Un codigo de barras se carga cuando se escanea el producto fisico o
        // viene en la lista del proveedor. Hasta entonces, NULL.
        $productos = [
            ['P001', 'Arroz Costeño Extra 5 kg',            'Costeño',   'Bolsa 5 kg',    'UNIDAD',  18.50, 23.90, 120, 20],
            ['P002', 'Aceite Primor Premium 1 L',           'Primor',    'Botella 1 L',   'UNIDAD',   8.20, 11.50,  90, 15],
            ['P003', 'Azucar Rubia Cartavio 1 kg',          'Cartavio',  'Bolsa 1 kg',    'UNIDAD',   3.60,  5.20, 150, 25],
            ['P004', 'Leche Gloria Evaporada 400 g',        'Gloria',    'Lata 400 g',    'UNIDAD',   3.10,  4.50, 200, 40],
            ['P005', 'Leche Gloria Deslactosada 400 g',     'Gloria',    'Lata 400 g',    'UNIDAD',   3.40,  4.90,  80, 20],
            ['P006', 'Fideos Don Vittorio Spaghetti 500 g', 'Don Vittorio', 'Bolsa 500 g', 'UNIDAD',   3.20,  4.60, 110, 20],
            ['P007', 'Atun Florida Filete en Aceite 170 g', 'Florida',   'Lata 170 g',    'UNIDAD',   5.80,  8.20,  75, 15],
            ['P008', 'Gaseosa Inca Kola 1.5 L',             'Inca Kola', 'Botella 1.5 L', 'UNIDAD',   5.50,  7.50, 140, 30],
            ['P009', 'Gaseosa Coca Cola 1.5 L',             'Coca Cola', 'Botella 1.5 L', 'UNIDAD',   5.60,  7.60, 130, 30],
            ['P010', 'Agua San Luis sin gas 2.5 L',         'San Luis',  'Bidon 2.5 L',   'UNIDAD',   3.90,  5.50,  95, 20],
            ['P011', 'Detergente Bolivar 780 g',            'Bolivar',   'Bolsa 780 g',   'UNIDAD',   7.10,  9.90,  60, 12],
            ['P012', 'Jabon Bolivar Barra 240 g',           'Bolivar',   'Barra 240 g',   'UNIDAD',   2.40,  3.60,  85, 20],
            ['P013', 'Papel Higienico Elite 4 rollos',      'Elite',     'Paquete x4',    'PAQUETE',  4.80,  6.90, 100, 24],
            ['P014', 'Yogurt Gloria Fresa 1 L',             'Gloria',    'Botella 1 L',   'UNIDAD',   5.20,  7.20,  55, 12],
            ['P015', 'Galletas Soda Field 6 pack',          'Field',     'Paquete x6',    'PAQUETE',  2.90,  4.20, 130, 30],
            ['P016', 'Galletas Oreo 36 g',                  'Oreo',      'Unidad 36 g',   'UNIDAD',   0.80,  1.50, 250, 50],
            ['P017', 'Cerveza Pilsen Callao 650 ml',        'Pilsen',    'Botella 650 ml', 'UNIDAD',   6.20,  8.50,  70, 24],
            ['P018', 'Cerveza Cristal 650 ml',              'Cristal',   'Botella 650 ml', 'UNIDAD',   6.10,  8.40,  65, 24],
            ['P019', 'Sal Marina Emsal 1 kg',               'Emsal',     'Bolsa 1 kg',    'UNIDAD',   1.30,  2.20,  90, 15],
            ['P020', 'Harina Blanca Flor 1 kg',             'Blanca Flor', 'Bolsa 1 kg',   'UNIDAD',   4.10,  5.80,  70, 15],
            ['P021', 'Mantequilla Laive 200 g',             'Laive',     'Barra 200 g',   'UNIDAD',   6.40,  8.90,  40, 10],
            ['P022', 'Cafe Altomayo Instantaneo 50 g',      'Altomayo',  'Frasco 50 g',   'UNIDAD',   8.90, 12.50,  45, 10],
            ['P023', 'Te Hornimans Anis 100 sobres',        'Hornimans', 'Caja x100',     'CAJA',     9.50, 13.00,  30,  8],
            ['P024', 'Lejia Clorox 1 L',                    'Clorox',    'Botella 1 L',   'UNIDAD',   3.70,  5.40,  60, 12],
            ['P025', 'Shampoo Head & Shoulders 375 ml',     'H&S',       'Botella 375 ml', 'UNIDAD',  16.90, 22.90,  25,  6],
            ['P026', 'Pasta Dental Colgate 90 g',           'Colgate',   'Tubo 90 g',     'UNIDAD',   4.30,  6.50,  55, 12],
            ['P027', 'Chocolate Sublime 30 g',              'Sublime',   'Unidad 30 g',   'UNIDAD',   1.20,  2.00, 180, 40],
            ['P028', 'Panetton Donofrio 900 g',             'Donofrio',  'Caja 900 g',    'UNIDAD',  18.00, 25.90,  20,  5],
            ['P029', 'Queso Fresco Laive 500 g',            'Laive',     'Paquete 500 g', 'UNIDAD',  12.50, 17.90,  18,  6],
            ['P030', 'Huevos de Corral x 15 unidades',      'Granja',    'Bandeja x15',   'PAQUETE',  9.80, 13.50,  35, 10],
            // Los tres siguientes quedan bajo el minimo a proposito, para que el
            // dashboard muestre alertas de stock desde el primer arranque.
            ['P031', 'Menestra Lenteja 500 g',              'Costeño',   'Bolsa 500 g',   'UNIDAD',   3.90,  5.60,   4, 15],
            ['P032', 'Suavizante Downy 800 ml',             'Downy',     'Botella 800 ml', 'UNIDAD',   9.20, 12.90,   2, 10],
            ['P033', 'Avena Quaker 170 g',                  'Quaker',    'Bolsa 170 g',   'UNIDAD',   2.10,  3.30,   6, 20],
        ];

        $almacenes = DB::table('almacenes')->orderBy('id')->pluck('id')->all();

        foreach ($productos as $p) {
            [$codigo, $desc, $marca, $pres, $unidad, $compra, $venta, $stock, $minimo] = $p;

            DB::table('productos')->updateOrInsert(
                ['codigo_interno' => $codigo],
                [
                    // Sin fuente verificable no hay EAN: NULL es correcto y el
                    // POS ya sabe buscar por codigo interno y por descripcion.
                    'codigo_barras' => null,
                    'descripcion' => $desc,
                    'marca' => $marca,
                    'presentacion' => $pres,
                    'unidad' => $unidad,
                    'operacion' => 'GRAVADO',
                    'tipo_producto' => 'PRODUCTO',
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'stock_minimo' => $minimo,
                    'detraccion' => 0,
                    'estado' => 1,
                ]
            );

            $productoId = DB::table('productos')->where('codigo_interno', $codigo)->value('id');

            // El dashboard suma el stock desde producto_almacen, no desde
            // productos.stock, asi que hay que repartirlo entre los almacenes.
            $principal = (int) round($stock * 0.7);
            $reparto = [$almacenes[0] => $principal];

            if (isset($almacenes[1])) {
                $reparto[$almacenes[1]] = $stock - $principal;
            }

            foreach ($reparto as $almacenId => $cantidad) {
                DB::table('producto_almacen')->updateOrInsert(
                    ['producto_id' => $productoId, 'almacen_id' => $almacenId],
                    ['stock' => $cantidad]
                );
            }
        }
    }
}
