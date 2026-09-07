<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La apertura de caja nunca estuvo ligada a una caja fisica: el codigo venia
     * usando el id de la apertura donde correspondia el de la caja, contra las
     * claves foraneas de series.caja_id y ventas.caja_id. Esta columna es el
     * vinculo que faltaba.
     */
    public function up(): void
    {
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->integer('caja_id')->nullable()->after('hora_apertura');
            $table->foreign('caja_id')->references('id')->on('cajas')->nullOnDelete();
        });

        // Aperturas ya existentes: la caja asignada al responsable, y si no
        // tiene, la primera caja registrada.
        $primeraCaja = DB::table('cajas')->orderBy('id')->value('id');

        DB::table('apertura_cajas')->orderBy('id')->each(function ($apertura) use ($primeraCaja) {
            $cajaId = DB::table('users')->where('id', $apertura->responsable_id)->value('caja_id') ?: $primeraCaja;

            if ($cajaId) {
                DB::table('apertura_cajas')->where('id', $apertura->id)->update(['caja_id' => $cajaId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->dropForeign(['caja_id']);
            $table->dropColumn('caja_id');
        });
    }
};
