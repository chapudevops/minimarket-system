<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $cajaId    = DB::table('cajas')->orderBy('id')->value('id');
            $almacenId = DB::table('almacenes')->orderBy('id')->value('id');

            $usuarios = [
                ['name' => 'Administrador', 'email' => 'admin@laesquina.pe',   'rol' => 'Administrador'],
                ['name' => 'Lucia Vendedora', 'email' => 'ventas@laesquina.pe', 'rol' => 'Vendedor'],
                ['name' => 'Marco Almacen',   'email' => 'almacen@laesquina.pe','rol' => 'Almacenero'],
            ];

            foreach ($usuarios as $usuario) {
                DB::table('users')->updateOrInsert(
                    ['email' => $usuario['email']],
                    [
                        'name'       => $usuario['name'],
                        'password'   => Hash::make('password'),
                        'caja_id'    => $cajaId,
                        'almacen_id' => $almacenId,
                        'estado'     => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $userId = DB::table('users')->where('email', $usuario['email'])->value('id');
                $roleId = DB::table('roles')->where('nombre', $usuario['rol'])->value('id');

                DB::table('user_roles')->updateOrInsert(
                    ['user_id' => $userId, 'role_id' => $roleId],
                    ['user_id' => $userId, 'role_id' => $roleId]
                );
            }
        });
    }
}
