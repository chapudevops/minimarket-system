<?php

use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pone los DEFAULT de las columnas en el vocabulario nuevo.
 *
 * Va aparte de la migracion de datos porque aquella ya se aplico. Sin esto,
 * una fila insertada sin estado explicito (un seeder, un INSERT a mano)
 * nacerian con 'COMPLETADA' o 'PENDIENTE', que son justo los valores que se
 * acaban de retirar.
 *
 * Se usa SQL crudo en vez de Schema::table()->change() para no depender de
 * doctrine/dbal, que este proyecto no tiene.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ventas MODIFY estado VARCHAR(20) NOT NULL DEFAULT '".EstadoVenta::APROBADA."'");
        DB::statement("ALTER TABLE ventas MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT '".EstadoSunat::NO_ENVIADO."'");
        DB::statement("ALTER TABLE notas_credito MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT '".EstadoSunat::NO_ENVIADO."'");
        DB::statement("ALTER TABLE notas_debito MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT '".EstadoSunat::NO_ENVIADO."'");
        DB::statement("ALTER TABLE guias_remision MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT '".EstadoSunat::NO_ENVIADO."'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ventas MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'COMPLETADA'");
        DB::statement("ALTER TABLE ventas MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'");
        DB::statement("ALTER TABLE notas_credito MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'");
        DB::statement("ALTER TABLE notas_debito MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'");
        DB::statement("ALTER TABLE guias_remision MODIFY estado_sunat VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE'");
    }
};
