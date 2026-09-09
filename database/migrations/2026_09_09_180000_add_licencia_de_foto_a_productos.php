<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licencia y atribucion de la imagen, guardadas por producto.
 *
 * Podrian derivarse del registro de fuentes en vez de repetirse aca, pero eso
 * seria un error: si manana Open Food Facts cambia sus condiciones, las
 * imagenes que ya bajamos siguen amparadas por la licencia que estaba vigente
 * cuando se obtuvieron. Junto con foto_fecha_consulta, estas dos columnas son
 * la prueba de bajo que terminos se uso cada archivo.
 *
 * Ademas separan dos cosas que no son la misma: la licencia de los DATOS de la
 * fuente (nombre, marca, cantidad: en OFF es ODbL) y la de la IMAGEN (CC BY-SA).
 * Aca se guarda la de la imagen, que es la que obliga a poner el credito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('foto_licencia', 80)->nullable()->after('foto_fuente')
                ->comment('Licencia de la IMAGEN declarada por la fuente al momento de obtenerla');

            $table->string('foto_atribucion', 180)->nullable()->after('foto_licencia')
                ->comment('Credito exacto que exige esa licencia');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['foto_licencia', 'foto_atribucion']);
        });
    }
};
