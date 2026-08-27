<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // pendente | pago — preenchido pelos gateways (Mercado Pago / Efí)
            $table->string('pagamento_status', 20)->nullable();
            // id do pagamento no gateway (payment id MP ou txid Efí)
            $table->string('pagamento_id', 191)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['pagamento_status', 'pagamento_id']);
        });
    }
};
