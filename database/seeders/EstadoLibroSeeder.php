<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoLibroSeeder extends Seeder
{
    public function run(): void
    {
        $estados = ['Disponible', 'Prestado', 'Dañado', 'Extraviado', 'No Disponible'];

        foreach ($estados as $estado) {
            DB::table('estado_libro')->updateOrInsert(
                ['estadoLibro' => $estado],
                ['estadoLibro' => $estado]
            );
        }
        
        if (!DB::table('estado_libro')->where('estadoLibro', 'Eliminado')->exists()) {
            DB::table('estado_libro')->insert(['estadoLibro' => 'Eliminado']);
        }
    }
}
