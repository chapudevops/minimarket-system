<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorias del catalogo.
 *
 * Hasta ahora la clasificacion viajaba dentro del codigo interno
 * (BEB-GAS-000001) y no habia forma de preguntarle a la base "cuanto vendi en
 * bebidas". El prefijo del SKU sigue existiendo, pero como identificador: la
 * categoria pasa a ser un dato con su propia columna, porque un producto puede
 * recategorizarse sin que su SKU cambie —el SKU es estable por definicion— y
 * porque hay productos sin prefijo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre', 80)->unique();
            $tabla->string('codigo', 3)->unique();
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->boolean('estado')->default(true);
            $tabla->timestamps();

            $tabla->index(['estado', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
