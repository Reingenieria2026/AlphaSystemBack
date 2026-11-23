<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            

            // Si NO existe 'intentos_fallidos', la creamos
            if (!Schema::hasColumn('usuario', 'intentos_fallidos')) {
                $table->unsignedSmallInteger('intentos_fallidos')->default(0)->after('idEstadoUsr');
            }

            
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            
            if (Schema::hasColumn('usuario', 'intentos_fallidos')) {
                $table->dropColumn('intentos_fallidos');
            }
            // No borro 'contrasena' en down por seguridad (evita pérdida de datos).
            // Si quieres revertir también 'contrasena', descomenta:
            // if (Schema::hasColumn('usuario', 'contrasena')) {
            //     $table->dropColumn('contrasena');
            // }
        });
    }
};
