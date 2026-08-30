<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loja_id')->nullable()->index();
            $table->string('slug', 50);
            $table->string('nome', 100);
            $table->boolean('ativo')->default(false);
            $table->timestamps();

            // Uma configuração por (loja, slug); loja_id NULL = regra global.
            $table->unique(['loja_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};