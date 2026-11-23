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
        Schema::create('prestamo', function (Blueprint $table) {
            $table->id('idPrestamo');
            $table->unsignedBigInteger('idUsuario');
            $table->string('idLibro'); // referencia a isbn
            $table->date('fechaPrestamo');
            $table->date('fechaEntrega');
            $table->decimal('multa', 10, 2)->nullable();
            $table->boolean('devuelto')->default(false);
            
            $table->foreign('idUsuario')->references('idUsr')->on('usuario');
            $table->foreign('idLibro')->references('isbn')->on('libro');
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamo');
    }
};
