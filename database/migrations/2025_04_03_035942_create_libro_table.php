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
        Schema::create('libro', function (Blueprint $table) {
            $table->string('isbn')->primary();
            $table->string('titulo', 255);
            $table->binary('fotoUsr')->nullable();
            $table->integer('cantidad');
            $table->integer('anioPubli');
            $table->string('edicion', 50)->nullable();
            $table->string('autor', 100);
            $table->string('editorial', 100);
            $table->unsignedBigInteger('idGenero');
            $table->unsignedBigInteger('idEstadoLibro');
    
            $table->foreign('idGenero')->references('idGenero')->on('genero');
            $table->foreign('idEstadoLibro')->references('idEstadoU')->on('estado_libro');
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libro');
    }
};
