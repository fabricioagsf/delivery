<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('categorias', 'loja_id')) {
            try {
                Schema::table('categorias', fn (Blueprint $t) => $t->dropForeign(['loja_id']));
            } catch (Throwable) {
                // ignore if FK not present
            }
            Schema::table('categorias', fn (Blueprint $t) => $t->dropColumn('loja_id'));
        }

        if (collect(Schema::getIndexes('categorias'))->contains(fn ($i) => $i['name'] === 'categorias_loja_slug_unique')) {
            Schema::table('categorias', fn (Blueprint $t) => $t->dropUnique('categorias_loja_slug_unique'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Remoção de loja_id de categorias não tem reversão.');
    }
};
