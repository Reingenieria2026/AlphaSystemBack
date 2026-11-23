<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('idUsr');
            $table->string('nombreUsr', 100);
            $table->string('apellidoP', 100);
            $table->string('apellidoM', 100)->nullable();
            $table->binary('fotoUsr')->nullable();
            $table->string('calle', 255)->nullable();
            $table->string('colonia', 255)->nullable();
            $table->unsignedBigInteger('idCiudad');
            $table->string('telefonoUsr', 20)->nullable();
            $table->string('correoUsr', 100)->unique();
            $table->string('contrasena', 255);
            $table->unsignedBigInteger('idTipoUsr');
            $table->unsignedBigInteger('idEstadoUsr');
    
            $table->foreign('idCiudad')->references('idCiudad')->on('ciudad');
            $table->foreign('idTipoUsr')->references('idTipoUsr')->on('tipo_usuario');
            $table->foreign('idEstadoUsr')->references('idEstadoUsr')->on('estado_usuario');
            $table->integer('intentos_fallidos')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
