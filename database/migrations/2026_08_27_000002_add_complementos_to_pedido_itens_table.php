<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            // Snapshot das personalizações escolhidas no momento do pedido:
            // [{tipo, nome, preco, valor_complementos}...]. O preço dos adicionais
            // é congelado aqui para o painel exibir fiel ao que o cliente pediu.
            $table->json('complementos')->nullable()->after('preco_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            $table->dropColumn('complementos');
        });
    }
};
