<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un comprobante electronico tiene un ciclo de vida propio ante SUNAT y no
     * habia donde guardarlo. Estas columnas son el minimo para poder emitir,
     * reintentar y demostrar que un comprobante fue aceptado.
     *
     * Los listados de la interfaz ya tienen columnas para XML, CDR y estado
     * SUNAT: hasta ahora el backend las devolvia vacias.
     */
    private const TABLAS = ['ventas', 'notas_credito', 'notas_debito'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                // PENDIENTE al emitir; ACEPTADO / RECHAZADO segun el CDR;
                // ANULADO tras la comunicacion de baja.
                $table->string('estado_sunat', 20)->default('PENDIENTE')->after('estado');

                // Valor resumen del XML firmado. Va dentro del QR y del PDF.
                $table->string('hash_xml', 100)->nullable()->after('estado_sunat');

                // Rutas en el disco privado, no el contenido.
                $table->string('ruta_xml', 255)->nullable()->after('hash_xml');
                $table->string('ruta_cdr', 255)->nullable()->after('ruta_xml');

                // Respuesta del CDR: 0 = aceptado, 2xxx = rechazado, 4xxx = observado.
                $table->string('codigo_respuesta', 10)->nullable()->after('ruta_cdr');
                $table->text('descripcion_respuesta')->nullable()->after('codigo_respuesta');

                // Ticket del envio asincrono (resumenes y bajas).
                $table->string('ticket_sunat', 60)->nullable()->after('descripcion_respuesta');

                $table->timestamp('enviado_sunat_at')->nullable()->after('ticket_sunat');
                $table->unsignedTinyInteger('intentos_envio')->default(0)->after('enviado_sunat_at');

                // Para levantar la cola de pendientes sin recorrer la tabla.
                $table->index('estado_sunat', "{$table->getTable()}_estado_sunat_index");
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropIndex("{$table->getTable()}_estado_sunat_index");
                $table->dropColumn([
                    'estado_sunat', 'hash_xml', 'ruta_xml', 'ruta_cdr',
                    'codigo_respuesta', 'descripcion_respuesta', 'ticket_sunat',
                    'enviado_sunat_at', 'intentos_envio',
                ]);
            });
        }
    }
};
