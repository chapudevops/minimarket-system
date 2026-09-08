<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subcategorias, colgando de su categoria.
 *
 * El codigo es unico DENTRO de la categoria, no globalmente: GALLETAS Y
 * DULCES/GALLETAS y PANADERIA/PAN comparten inicial con otras, y forzar
 * unicidad global obligaria a inventar codigos peores solo para evitar un
 * choque que no molesta a nadie. El par (categoria, codigo) si es unico, que
 * es lo que el prefijo del SKU necesita.
 *
 * ON DELETE RESTRICT: borrar una categoria que tiene subcategorias vivas
 * dejaria productos apuntando al vacio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $tabla->string('nombre', 80);
            $tabla->string('codigo', 3);
            $tabla->string('unidad_sugerida', 20)->default('UNIDAD');
            $tabla->unsignedSmallInteger('orden')->default(0);
            $tabla->boolean('estado')->default(true);
            $tabla->timestamps();

            $tabla->unique(['categoria_id', 'codigo']);
            $tabla->unique(['categoria_id', 'nombre']);
            $tabla->index(['estado', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias');
    }
};
