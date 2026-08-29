<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-lojas: mesas são de negócio e ficam isoladas por loja (AGENTS §4/§5).
 * Dados existentes vão para a loja matriz (slug gostosuras); o código único
 * passa a ser por loja (mesmo código de mesa pode existir em filiais diferentes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->unsignedBigInteger('loja_id')->nullable()->after('id');
            $table->foreign('loja_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index('loja_id');
        });

        $lojaMatriz = DB::table('tenants')->where('slug', 'gostosuras')->value('id')
            ?? DB::table('tenants')->where('status', 'ativo')->orderBy('id')->value('id');

        if ($lojaMatriz !== null) {
            DB::table('mesas')->whereNull('loja_id')->update(['loja_id' => $lojaMatriz]);
        }

        $temUnica = collect(Schema::getIndexes('mesas'))->contains(fn ($idx) => $idx['name'] === 'mesas_codigo_unique');
        if ($temUnica) {
            Schema::table('mesas', fn (Blueprint $table) => $table->dropUnique('mesas_codigo_unique'));
        }

        Schema::table('mesas', fn (Blueprint $table) => $table->unique(['loja_id', 'codigo'], 'mesas_loja_codigo_unique'));
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropUnique('mesas_loja_codigo_unique');
            $table->dropForeign(['loja_id']);
            $table->dropIndex(['loja_id']);
            $table->dropColumn('loja_id');
        });
    }
};