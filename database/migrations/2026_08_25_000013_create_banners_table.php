<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120)->nullable();
            $table->string('imagem', 255);
            $table->string('link', 255)->nullable();
            $table->unsignedInteger('ordem')->default(0);

            // Agendamento: banner entra no ar em inicio_em e sai em fim_em.
            // Campo nulo = sem limite naquela extremidade.
            $table->dateTime('inicio_em')->nullable();
            $table->dateTime('fim_em')->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo', 'inicio_em', 'fim_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
