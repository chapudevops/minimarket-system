<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa la afectacion del IGV de los otros tratamientos tributarios.
 *
 * Hasta ahora `productos.operacion` cargaba con todo, y no daba: una bebida
 * energetica es GRAVADA de IGV *y ademas* esta en el ambito del ISC; el arroz
 * pilado tiene el tratamiento de la Ley 28211 (IVAP), que no es ninguno de los
 * tres valores de la Catalogo 07 de SUNAT.
 *
 * `operacion` conserva su nombre y su significado original —afectacion del IGV,
 * Catalogo 07— porque renombrarla obligaria a tocar el dump del esquema, los
 * formularios, el JS y el constructor de comprobantes sin ganar nada: con estas
 * dos columnas al lado, deja de ser ambigua.
 *
 * Ambas son informativas por diseno. Un minimarket que compra a un distribuidor
 * no declara ISC ni IVAP en sus boletas: el ISC se aplica a nivel de productor
 * o importador y le llega incorporado en el costo. Se guardan para poder
 * identificar el producto, analizar margenes y responder ante una revision, no
 * para calcular un impuesto en el XML. Ver data/catalogo-minimarket/README.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('afecto_isc')
                ->default(false)
                ->after('operacion')
                ->comment('Ambito del ISC (Apendice III/IV Ley IGV). Informativo: no se calcula en el comprobante.');

            $table->boolean('afecto_ivap')
                ->default(false)
                ->after('afecto_isc')
                ->comment('Ambito del IVAP (Ley 28211, arroz pilado). Informativo: requiere validacion contable.');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['afecto_isc', 'afecto_ivap']);
        });
    }
};
