<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Borra de la base los 33 codigos de barras sinteticos que sembraba
 * CatalogoSeeder antes de esta fase.
 *
 * Eran EAN-13 inventados con prefijo peruano (775..., 7750..., 7751...): tenian
 * pinta de reales, el lector del terminal los habria dado por buenos, y el dia
 * que entrara el producto de verdad con su EAN real habria quedado el mismo
 * articulo dos veces en el catalogo.
 *
 * El borrado es quirurgico: solo se anula la fila si TODAVIA tiene exactamente
 * el valor sintetico que sembro el seeder. Si alguien ya escaneo el producto
 * fisico y cargo el EAN real, ese codigo no se toca.
 */
return new class extends Migration
{
    /** codigo_interno => EAN sintetico que hay que borrar. */
    private const FICTICIOS = [
        'P001' => '7751271001234', 'P002' => '7750670001120', 'P003' => '7751271009876',
        'P004' => '7750243012345', 'P005' => '7750243098765', 'P006' => '7751500012340',
        'P007' => '7751271004567', 'P008' => '7750182001234', 'P009' => '7750182005678',
        'P010' => '7751271112233', 'P011' => '7750670004455', 'P012' => '7750670007788',
        'P013' => '7751271556677', 'P014' => '7750243334455', 'P015' => '7751500778899',
        'P016' => '7751500223344', 'P017' => '7750182998877', 'P018' => '7750182556644',
        'P019' => '7751271443322', 'P020' => '7751271667788', 'P021' => '7750243112200',
        'P022' => '7751500991122', 'P023' => '7751271334466', 'P024' => '7750670553311',
        'P025' => '7750670884422', 'P026' => '7751271220099', 'P027' => '7751500445566',
        'P028' => '7751500667700', 'P029' => '7750243776655', 'P030' => '7751271889900',
        'P031' => '7751271010101', 'P032' => '7750670020202', 'P033' => '7751500030303',
    ];

    public function up(): void
    {
        foreach (self::FICTICIOS as $codigoInterno => $ean) {
            DB::table('productos')
                ->where('codigo_interno', $codigoInterno)
                ->where('codigo_barras', $ean)
                ->update(['codigo_barras' => null]);
        }
    }

    /**
     * Sin vuelta atras: reponer un codigo inventado seria volver a meter el
     * problema que esta migracion existe para sacar.
     */
    public function down(): void
    {
        // Intencionalmente vacio.
    }
};
