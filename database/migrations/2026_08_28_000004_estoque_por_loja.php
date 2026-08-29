<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estoque por loja: o catálogo de produtos é compartilhado entre as lojas da rede
 * (Produto deixa de ter loja_id), mas a contagem de estoque e o estoque mínimo
 * passam a viver em `produto_estoques(produto_id, loja_id)`. Cada loja pode ter
 * um estoque independente para o mesmo produto.
 *
 * Dados existentes: o estoque/minimo de cada produto vira uma linha em
 * produto_estoques para cada loja ativa; `produtos.estoque` e
 * `produtos.estoque_minimo` saem do banco.
 *
 * Alteração aprovada pelo usuário (refazer agora).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== produto_estoques =====
        if (! Schema::hasTable('produto_estoques')) {
            Schema::create('produto_estoques', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('produto_id');
                $table->unsignedBigInteger('loja_id');
                $table->integer('estoque')->nullable();
                $table->integer('estoque_minimo')->default(5);
                $table->timestamps();

                $table->foreign('produto_id')->references('id')->on('produtos')->cascadeOnDelete();
                $table->foreign('loja_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->unique(['produto_id', 'loja_id'], 'produto_estoques_produto_loja_unique');
                $table->index('loja_id');
            });
        }

        // ===== Backfill: para cada produto, cria uma linha por loja ativa =====
        if (Schema::hasTable('produtos') && Schema::hasTable('tenants')) {
            $lojas = DB::table('tenants')->where('status', 'ativo')->pluck('id');
            $produtos = DB::table('produtos')
                ->select('id', 'estoque', 'estoque_minimo')
                ->get();

            foreach ($produtos as $p) {
                foreach ($lojas as $lojaId) {
                    DB::table('produto_estoques')->updateOrInsert(
                        ['produto_id' => $p->id, 'loja_id' => $lojaId],
                        [
                            'estoque' => $p->estoque,
                            'estoque_minimo' => $p->estoque_minimo ?? 5,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }

        // ===== Drop estoque columns de produtos =====
        if (Schema::hasColumn('produtos', 'estoque')) {
            Schema::table('produtos', fn (Blueprint $t) => $t->dropColumn('estoque'));
        }
        if (Schema::hasColumn('produtos', 'estoque_minimo')) {
            Schema::table('produtos', fn (Blueprint $t) => $t->dropColumn('estoque_minimo'));
        }

        // ===== Drop loja_id de produtos (catálogo global) =====
        if (Schema::hasColumn('produtos', 'loja_id')) {
            // Drop FK if present (Laravel sets <tabela>_<coluna>_foreign)
            try {
                Schema::table('produtos', fn (Blueprint $t) => $t->dropForeign(['loja_id']));
            } catch (Throwable) {
                // ignore if FK isn't there
            }
            Schema::table('produtos', fn (Blueprint $t) => $t->dropColumn('loja_id'));
        }

        // ===== Drop unique composto (loja_id, slug) de produtos =====
        if (collect(Schema::getIndexes('produtos'))->contains(fn ($i) => $i['name'] === 'produtos_loja_slug_unique')) {
            Schema::table('produtos', fn (Blueprint $t) => $t->dropUnique('produtos_loja_slug_unique'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Refazer estoque por loja não tem reversão.');
    }
};
