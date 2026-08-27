<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            // null = produção sem controle de estoque; 0 = esgotado (bloqueia venda)
            $table->unsignedInteger('estoque')->nullable()->after('preco');
            $table->unsignedInteger('estoque_minimo')->default(5)->after('estoque');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['estoque', 'estoque_minimo']);
        });
    }
};
