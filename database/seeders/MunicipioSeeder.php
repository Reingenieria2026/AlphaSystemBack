<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        $json  = File::get(database_path('seeders/data/municipios_mexico.json'));
        $datos = json_decode($json, true);

        $mapa = [];
        $estadosDB = DB::table('estado')->get(['idEstado', 'nombreEstado']);
        foreach ($estadosDB as $e) {
            $key = Str::lower(Str::ascii($e->nombreEstado));
            $mapa[$key] = $e->idEstado;
        }

        foreach ($datos as $estadoNombre => $municipios) {
            $key = Str::lower(Str::ascii($estadoNombre));

            if (! isset($mapa[$key])) {
                $this->command->warn("⚠ Estado no encontrado en BD: {$estadoNombre}");
                continue;
            }

            $idEstado = $mapa[$key];
            foreach ($municipios as $nombreMunicipio) {
                DB::table('municipio')->insert([
                    'nombreMunicipio' => $nombreMunicipio,
                    'idEstado'        => $idEstado,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }
}
