<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->enum('tipo', ['percentual', 'fixo'])->default('percentual');
            $table->decimal('valor', 10, 2);
            $table->decimal('valor_minimo', 10, 2)->nullable();
            $table->unsignedInteger('limite_usos')->nullable();
            $table->unsignedInteger('usos')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamp('inicio_em')->nullable();
            $table->timestamp('fim_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupons');
    }
};
