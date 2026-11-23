<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('estado_usuario')->insert([
            [
                'estadoUsr'  => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'estadoUsr'  => 'Inactivo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'estadoUsr'  => 'Suspendido',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'estadoUsr'  => 'Eliminado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
