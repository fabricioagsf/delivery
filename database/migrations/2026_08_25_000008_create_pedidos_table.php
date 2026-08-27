<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('endereco_id')->nullable()->constrained('enderecos')->nullOnDelete();
            $table->foreignId('cartao_id')->nullable()->constrained('cartoes')->nullOnDelete();

            $table->string('nome_cliente', 150);
            $table->string('telefone', 30);
            $table->string('email', 150)->nullable();

            $table->enum('tipo_entrega', ['entrega', 'retirada'])->default('entrega');
            $table->string('rua', 200)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 120)->nullable();
            $table->string('bairro', 120)->nullable();
            $table->string('cidade', 120)->nullable();
            $table->string('cep', 12)->nullable();

            $table->enum('forma_pagamento', ['pix', 'cartao', 'dinheiro']);
            $table->decimal('troco_para', 10, 2)->nullable();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('taxa_entrega', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->text('observacoes')->nullable();
            $table->string('status', 40)->default('novo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
