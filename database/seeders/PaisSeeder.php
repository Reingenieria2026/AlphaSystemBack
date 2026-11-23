<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaisSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Desactiva temporalmente las FK
        Schema::disableForeignKeyConstraints();

        // 2) Trunca la tabla (borrar todo y reiniciar auto‑increment si hubiera)
        DB::table('pais')->truncate();

        // 3) Reactiva las FK
        Schema::enableForeignKeyConstraints();

        // 4) Inserta tus países
        DB::table('pais')->insert([
            [
                'nombrePais' => 'México',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // puedes agregar más registros aquí si los necesitas
        ]);
    }
}
