<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // Por segurança, NUNCA armazenamos o número completo do cartão.
            // Guardamos apenas apelido, bandeira e os 4 últimos dígitos.
            $table->string('apelido', 80);
            $table->string('bandeira', 40);
            $table->string('numero_final', 4);
            $table->string('validade', 7);
            $table->string('titular', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes');
    }
};
