<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlmacenCajaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $almacenes = [
                ['descripcion' => 'Almacen Principal', 'establecimiento' => 'Oficina Principal'],
                ['descripcion' => 'Almacen Tienda',    'establecimiento' => 'Sucursal La Molina'],
            ];

            foreach ($almacenes as $almacen) {
                DB::table('almacenes')->updateOrInsert(['descripcion' => $almacen['descripcion']], $almacen);
            }

            $cajas = ['Caja 01 - Mostrador', 'Caja 02 - Atencion rapida'];

            foreach ($cajas as $caja) {
                DB::table('cajas')->updateOrInsert(['descripcion' => $caja], ['descripcion' => $caja]);
            }

            $cajaPrincipal = DB::table('cajas')->where('descripcion', $cajas[0])->value('id');

            // Correlativos arrancan alineados con las ventas que siembra MovimientoSeeder.
            $series = [
                ['serie' => 'B001', 'tipo_comprobante' => 'BOLETA',     'correlativo' => 0],
                ['serie' => 'F001', 'tipo_comprobante' => 'FACTURA',    'correlativo' => 0],
                ['serie' => 'NV01', 'tipo_comprobante' => 'NOTA_VENTA', 'correlativo' => 0],
                ['serie' => 'C001', 'tipo_comprobante' => 'COTIZACION', 'correlativo' => 0],
            ];

            foreach ($series as $serie) {
                DB::table('series')->updateOrInsert(
                    ['serie' => $serie['serie'], 'tipo_comprobante' => $serie['tipo_comprobante']],
                    $serie + ['caja_id' => $cajaPrincipal]
                );
            }
        });
    }
}
