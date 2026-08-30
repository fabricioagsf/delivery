<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Forma de pagamento passa a ser opcional no pedido: o tablet/garçom
        // NÃO pergunta como a pessoa vai pagar; só o fechamento do caixa define.
        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('forma_pagamento', ['pix', 'cartao', 'dinheiro'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('forma_pagamento', ['pix', 'cartao', 'dinheiro'])->nullable(false)->change();
        });
    }
};