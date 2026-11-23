<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeneroSeeder extends Seeder
{
    public function run(): void
    {
        // Desactiva las restricciones de FK
        Schema::disableForeignKeyConstraints();

        // Vacía la tabla (truncate también reinicia autoincrement si lo tuvieras)
        DB::table('genero')->truncate();

        // Reactiva las restricciones de FK
        Schema::enableForeignKeyConstraints();

        // Inserta los registros
        DB::table('genero')->insert([
            ['nombreGenero' => 'Ficción'],
            ['nombreGenero' => 'Ciencia'],
            ['nombreGenero' => 'Historia'],
            ['nombreGenero' => 'Fantasía'],
            ['nombreGenero' => 'Biografía'],
        ]);
    }
}
