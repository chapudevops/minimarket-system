<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Ultima red de contencion del correlativo: aunque falle un bloqueo
            // o alguien inserte por fuera de la app, la base rechaza el
            // comprobante repetido. Para SUNAT un numero duplicado es rechazo.
            $table->unique(['tipo_comprobante', 'serie', 'numero'], 'ventas_comprobante_unique');

            // El dashboard filtra por estas dos en cada carga y no habia indice.
            $table->index('fecha_emision', 'ventas_fecha_emision_index');
            $table->index('estado', 'ventas_estado_index');
        });

        // productos.stock era una segunda fuente de verdad que nadie leia (no
        // esta en el $fillable del modelo, y la UI usa el accesor stock_total
        // que suma producto_almacen). Solo podia divergir, asi que se elimina.
        if (Schema::hasColumn('productos', 'stock')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('stock');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_comprobante_unique');
            $table->dropIndex('ventas_fecha_emision_index');
            $table->dropIndex('ventas_estado_index');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('tipo_producto');
        });

        // Se repuebla desde la fuente real para no dejarla en cero.
        DB::statement('
            UPDATE productos p
            LEFT JOIN (SELECT producto_id, SUM(stock) AS total FROM producto_almacen GROUP BY producto_id) a
                ON a.producto_id = p.id
            SET p.stock = COALESCE(a.total, 0)
        ');
    }
};
