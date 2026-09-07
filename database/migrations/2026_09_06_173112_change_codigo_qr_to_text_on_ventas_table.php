<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * codigo_qr recibia un data-URI SVG en base64 (~13 KB) dentro de un
     * varchar(255): con STRICT_TRANS_TABLES eso aborta la venta entera. A partir
     * de ahora guarda solo el contenido del QR y la imagen se arma al imprimir,
     * pero se amplia a TEXT para que el largo deje de ser un limite.
     * El indice sobre la columna no lo usaba ninguna consulta.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex('idx_codigo_qr');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->text('codigo_qr')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('codigo_qr', 255)->nullable()->change();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->index('codigo_qr', 'idx_codigo_qr');
        });
    }
};
