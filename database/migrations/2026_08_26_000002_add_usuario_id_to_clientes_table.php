<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $tabela) {
            $tabela->foreignId('usuario_id')
                ->nullable()
                ->after('id')
                ->constrained('usuarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $tabela) {
            $tabela->dropForeign(['usuario_id']);
            $tabela->dropColumn('usuario_id');
        });
    }
};
