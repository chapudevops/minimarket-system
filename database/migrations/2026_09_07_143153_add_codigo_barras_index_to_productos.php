<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El terminal busca el codigo que manda el lector por igualdad, asi que
     * ahora si tiene sentido indexarlo. No es unico a proposito: hay productos
     * sin codigo de barras y varios pueden quedar en NULL.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->index('codigo_barras', 'productos_codigo_barras_index');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_codigo_barras_index');
        });
    }
};
