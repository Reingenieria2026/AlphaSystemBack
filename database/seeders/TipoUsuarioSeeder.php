<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tipo_usuario')->insert([
            [
                'nombreTipo'  => 'Administrador',
                'descripcion' => 'Usuario con todos los privilegios del sistema',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'nombreTipo'  => 'Registrado',
                'descripcion' => 'Usuario que puede hacer reservas y préstamos',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'nombreTipo'  => 'No registrado',
                'descripcion' => 'Usuario visitante sin cuenta activa',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
    