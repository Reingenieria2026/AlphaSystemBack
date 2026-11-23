<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CiudadSeeder extends Seeder
{
    public function run(): void
    {
        $municipios = DB::table('municipio')->get(['idMunicipio', 'nombreMunicipio']);

        $ciudades = [];
        foreach ($municipios as $m) {
            for ($i = 1; $i <= 20; $i++) {
                $ciudades[] = [
                    'nombreCiudad' => "Ciudad {$i} de {$m->nombreMunicipio}",
                    'idMunicipio'  => $m->idMunicipio,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        $chunkSize = 200;
        foreach (array_chunk($ciudades, $chunkSize) as $chunk) {
            DB::table('ciudad')->insert($chunk);
        }
    }
}
