<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    public function run(): void
    {
        // Obtén el id de México
        $mxId = DB::table('pais')
            ->where('nombrePais', 'México')
            ->value('idPais');

        $nombres = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
            'Chiapas', 'Chihuahua', 'Ciudad de Mexico', 'Coahuila', 'Colima',
            'Durango', 'Estado de Mexico', 'Guanajuato', 'Guerrero', 'Hidalgo',
            'Jalisco', 'Michoacan', 'Morelos', 'Nayarit', 'Nuevo Leon',
            'Oaxaca', 'Puebla', 'Queretaro', 'Quintana Roo', 'San Luis Potosi',
            'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala',
            'Veracruz', 'Yucatan', 'Zacatecas',
        ];

        $estados = [];
        foreach ($nombres as $nom) {
            $estados[] = [
                'nombreEstado' => $nom,
                'idPais'       => $mxId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        DB::table('estado')->insert($estados);
    }
}
