<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El codigo de barras pasa a ser unico cuando existe.
 *
 * En MySQL un indice UNIQUE permite tantos NULL como haga falta, que es
 * exactamente la semantica que queremos: la mayoria del catalogo no tiene EAN
 * verificable y va NULL, pero dos productos distintos jamas pueden compartir un
 * EAN real; si eso pasa, el lector del terminal cobra el producto equivocado.
 *
 * Antes de crear el indice hay que limpiar la cadena vacia: el formulario de
 * productos guardaba '' cuando el campo se dejaba en blanco, y '' si cuenta
 * como valor repetido.
 */
return new class extends Migration
{
    public function up(): void
    {
        // '' no es "sin codigo de barras", es un codigo de barras vacio. Se
        // normaliza a NULL antes de que el indice unico lo rechace.
        DB::table('productos')->where('codigo_barras', '')->update(['codigo_barras' => null]);

        $repetidos = DB::table('productos')
            ->whereNotNull('codigo_barras')
            ->select('codigo_barras')
            ->groupBy('codigo_barras')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('codigo_barras');

        // No se decide por el usuario cual de los productos se queda con el
        // codigo: eso es informacion de negocio. La migracion se detiene y
        // dice exactamente que revisar.
        if ($repetidos->isNotEmpty()) {
            throw new RuntimeException(
                'Hay codigos de barras repetidos en productos y no se puede crear el indice unico. '
                .'Revisa y corrige estos codigos antes de migrar: '.$repetidos->implode(', ')
            );
        }

        Schema::table('productos', function (Blueprint $table) {
            if ($this->tieneIndice('productos_codigo_barras_index')) {
                $table->dropIndex('productos_codigo_barras_index');
            }

            $table->unique('codigo_barras', 'productos_codigo_barras_unique');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_codigo_barras_unique');
            $table->index('codigo_barras', 'productos_codigo_barras_index');
        });
    }

    private function tieneIndice(string $nombre): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'productos')
            ->where('index_name', $nombre)
            ->exists();
    }
};
