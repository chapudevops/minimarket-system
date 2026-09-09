<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de la imagen del producto.
 *
 * Hasta ahora `foto` guardaba un nombre de archivo y nada mas: no habia forma
 * de saber de donde salio la imagen, cuando, ni si alguien la habia validado.
 * Para una imagen de terceros eso no alcanza — hay que poder responder bajo que
 * condiciones se usa y volver al origen.
 *
 * El estado es ademas lo que hace incremental el enriquecimiento: una imagen ya
 * resuelta no se vuelve a consultar, y una foto propia no se pisa nunca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('foto_fuente', 60)->nullable()->after('foto')
                ->comment('Registro de fuentes: data/catalogo-minimarket/diccionarios/fuentes_imagen.csv');

            $table->string('foto_url_origen', 500)->nullable()->after('foto_fuente')
                ->comment('URL exacta de donde salio la imagen, para poder volver a ella');

            $table->date('foto_fecha_consulta')->nullable()->after('foto_url_origen');

            // SIN_IMAGEN por defecto y no NULL: "todavia no se busco" y "se
            // busco y no habia" son estados distintos, pero ninguno es la
            // ausencia de estado. El proceso necesita saber en cual esta.
            $table->string('foto_estado', 20)->default('SIN_IMAGEN')->after('foto_fecha_consulta')
                ->comment('PROPIA, VERIFICADA, REVISAR, SIN_IMAGEN');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['foto_fuente', 'foto_url_origen', 'foto_fecha_consulta', 'foto_estado']);
        });
    }
};
