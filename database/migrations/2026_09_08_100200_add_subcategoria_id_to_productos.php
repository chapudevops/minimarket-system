<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El producto apunta a su subcategoria, y la categoria se deriva.
 *
 * Guardar categoria_id y subcategoria_id a la vez crea una dependencia
 * transitiva: nada impediria grabar "Bebidas / Arroz". Con una sola columna
 * esa incoherencia no se puede ni representar, y la categoria sale con un join
 * o con $producto->subcategoria->categoria. El costo es ese join en los
 * reportes; sobre miles de productos con ambas claves indexadas no se nota.
 *
 * NULL significa "sin clasificar todavia", no es un error: un producto puede
 * existir antes de que alguien decida donde va.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $tabla) {
            $tabla->foreignId('subcategoria_id')
                ->nullable()
                ->after('descripcion')
                ->constrained('subcategorias')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $tabla) {
            $tabla->dropForeign(['subcategoria_id']);
            $tabla->dropColumn('subcategoria_id');
        });
    }
};
