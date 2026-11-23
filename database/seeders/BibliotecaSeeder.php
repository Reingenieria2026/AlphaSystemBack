<?php

namespace Database\Seeders;

use App\Models\Biblioteca;
use Illuminate\Database\Seeder;

class BibliotecaSeeder extends Seeder
{
    public function run(): void
    {
        Biblioteca::firstOrCreate(
            ['slug' => 'demo'],
            [
                'nombre' => 'Biblioteca Demo',
                'email_contacto' => 'demo@biblioteca.local',
                'theme_name' => 'Default',
                'theme_version' => 1,
                'theme_json' => [
                    'color-marron'      => '#44322D',
                    'color-naranja'     => '#FF6F61',
                    'color-beige'       => '#FAF3ED',
                    'blanco'            => '#ffffff',
                    'blanco_opciones'   => '#fff5f0'
                ],
                'is_activa' => true,
            ]
        );
    }
}
