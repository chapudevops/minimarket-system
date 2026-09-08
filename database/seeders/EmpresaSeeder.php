<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Varios controllers hacen Empresa::first() sin fallback (cotizaciones,
            // guias de remision, notas de credito/debito), asi que esta fila hace
            // falta para que esas vistas no revienten.
            DB::table('empresa')->updateOrInsert(
                ['ruc' => '20512345678'],
                [
                    'razon_social' => 'MINIMARKET LA ESQUINA S.A.C.',
                    'nombre_comercial' => 'Minimarket La Esquina',
                    'direccion' => 'Av. Los Proceres 1234, Urb. Santa Patricia',
                    'pais' => 'Perú',
                    'departamento' => 'Lima',
                    'provincia' => 'Lima',
                    'distrito' => 'La Molina',
                    'email_contabilidad' => 'contabilidad@laesquina.pe',
                    'servidor_sunat' => 'beta',
                    'estado' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }
}
