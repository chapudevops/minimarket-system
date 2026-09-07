<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /** Columnas que pasan a guardarse cifradas. */
    private const SECRETOS = ['clave', 'clave_certificado', 'client_secret'];

    /**
     * Las credenciales de SUNAT se guardaban en texto plano y el certificado
     * .pfx en el disco publico, con nombre predecible. Esta migracion:
     *   1. ensancha las columnas — un valor cifrado supera los 255 caracteres
     *   2. cifra lo que ya hubiera guardado
     *   3. mueve los certificados al disco privado
     */
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            foreach (self::SECRETOS as $columna) {
                $table->text($columna)->nullable()->change();
            }
        });

        foreach (DB::table('empresa')->get() as $empresa) {
            $cambios = [];

            foreach (self::SECRETOS as $columna) {
                $valor = $empresa->$columna;
                if ($valor !== null && $valor !== '') {
                    $cambios[$columna] = Crypt::encryptString($valor);
                }
            }

            if ($cambios) {
                DB::table('empresa')->where('id', $empresa->id)->update($cambios);
            }
        }

        $this->moverCertificados();
    }

    /** De storage/app/public/empresa/certificados a storage/app/empresa/certificados. */
    private function moverCertificados(): void
    {
        $origen = 'public/empresa/certificados';

        if (! Storage::exists($origen)) {
            return;
        }

        foreach (Storage::files($origen) as $archivo) {
            $destino = 'empresa/certificados/' . basename($archivo);

            if (! Storage::exists($destino)) {
                Storage::put($destino, Storage::get($archivo));
            }

            Storage::delete($archivo);
        }
    }

    public function down(): void
    {
        foreach (DB::table('empresa')->get() as $empresa) {
            $cambios = [];

            foreach (self::SECRETOS as $columna) {
                $valor = $empresa->$columna;
                if ($valor !== null && $valor !== '') {
                    try {
                        $cambios[$columna] = Crypt::decryptString($valor);
                    } catch (\Throwable) {
                        // Ya estaba en texto plano.
                    }
                }
            }

            if ($cambios) {
                DB::table('empresa')->where('id', $empresa->id)->update($cambios);
            }
        }

        Schema::table('empresa', function (Blueprint $table) {
            foreach (self::SECRETOS as $columna) {
                $table->string($columna, 255)->nullable()->change();
            }
        });
    }
};
