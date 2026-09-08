<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitacora de quien hizo que. En un sistema con caja y comprobantes
     * fiscales hace falta poder responder quien anulo una venta o quien cambio
     * un precio, y hasta ahora no quedaba rastro de nada.
     */
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // El id se pone en null si borran al usuario, pero el nombre queda:
            // una bitacora que se vacia al borrar una cuenta no sirve de nada.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre', 191);

            $table->string('accion', 30);              // CREO, ACTUALIZO, ELIMINO, ANULO, ...
            $table->string('entidad', 60);             // Venta, Producto, Empresa, ...
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('descripcion', 255);        // legible: "Anuló la venta B001-00000162"

            // Solo los campos que cambiaron, con su valor anterior y nuevo.
            $table->json('cambios')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('navegador', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entidad', 'entidad_id'], 'auditorias_entidad_index');
            $table->index('usuario_id', 'auditorias_usuario_index');
            $table->index('created_at', 'auditorias_fecha_index');
            $table->index('accion', 'auditorias_accion_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
