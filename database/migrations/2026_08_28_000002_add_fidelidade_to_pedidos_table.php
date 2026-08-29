<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->decimal('pontos_ganhos', 10, 2)->default(0)->after('cupom_desconto');
            $table->decimal('pontos_utilizados', 10, 2)->default(0)->after('pontos_ganhos');
            $table->decimal('pontos_desconto', 10, 2)->default(0)->after('pontos_utilizados');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['pontos_ganhos', 'pontos_utilizados', 'pontos_desconto']);
        });
    }
};
