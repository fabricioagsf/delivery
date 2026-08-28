<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_complementos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->enum('tipo', ['adicional', 'remocao'])->default('adicional');
            $table->string('nome', 120);
            $table->decimal('preco', 10, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_complementos');
    }
};
