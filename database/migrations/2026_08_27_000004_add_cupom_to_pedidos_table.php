<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('cupom_id')->nullable()->after('cartao_id')->constrained('cupons')->nullOnDelete();
            $table->string('cupom_codigo', 40)->nullable()->after('cupom_id');
            $table->decimal('cupom_desconto', 10, 2)->default(0)->after('cupom_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['cupom_id']);
            $table->dropColumn(['cupom_id', 'cupom_codigo', 'cupom_desconto']);
        });
    }
};
