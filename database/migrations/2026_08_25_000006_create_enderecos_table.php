<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('rua', 200);
            $table->string('numero', 20);
            $table->string('complemento', 120)->nullable();
            $table->string('bairro', 120);
            $table->string('cidade', 120);
            $table->string('cep', 12)->nullable();
            $table->boolean('principal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};
