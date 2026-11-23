<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Desactiva temporalmente las FK
        Schema::disableForeignKeyConstraints();

        // 2) Vacía la tabla (y reinicia auto‐increment si hubiera)
        DB::table('usuario')->truncate();

        // 3) Reactiva las FK
        Schema::enableForeignKeyConstraints();

        // 4) Inserta tu usuario admin
        DB::table('usuario')->insert([
            'nombreUsr'   => 'Admin',
            'apellidoP'   => 'Alpha',
            'apellidoM'   => 'System',
            'fotoUsr'     => null,
            'calle'       => 'Principal 123',
            'colonia'     => 'Centro',
            'idCiudad'    => 1,
            'telefonoUsr' => '1234567890',
            'correoUsr'   => 'admin@alpha.com',
            'contrasena'  => Hash::make('admin123'),
            'idTipoUsr'   => 1,
            'idEstadoUsr' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
