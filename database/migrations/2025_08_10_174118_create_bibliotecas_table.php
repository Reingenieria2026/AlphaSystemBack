<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('biblioteca', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 150)->unique();
            $table->string('slug', 80)->unique();
            $table->string('dominio', 190)->nullable()->unique();
            $table->string('email_contacto', 190)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->text('direccion')->nullable();

            // Branding
            $table->string('logo_path', 255)->nullable();
            $table->string('icon_path', 255)->nullable();

            // Tema
            $table->json('theme_json')->nullable();
            $table->unsignedInteger('theme_version')->default(1);
            $table->string('theme_name', 80)->nullable();

            $table->boolean('is_activa')->default(true);

            $table->timestamps();

            // Índices adicionales útiles
            $table->index(['is_activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biblioteca');
    }
};
