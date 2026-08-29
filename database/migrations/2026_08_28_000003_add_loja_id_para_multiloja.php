<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-lojas: cada loja é um registro da tabela `tenants` (auth-multi).
 * Adiciona `loja_id` (nullable, FK → tenants com nullOnDelete) nas tabelas
 * de negócio e troca os índices únicos por compostos (loja_id + chave),
 * permitindo os mesmos códigos/slugs em lojas diferentes.
 *
 * Dados existentes ficam vinculados à loja "matriz" (slug gostosuras);
 * `configuracoes` e `textos` mantêm valores globais (loja_id NULL) como
 * padrão da marca, com overrides por loja quando o admin salvar na loja.
 */
return new class extends Migration
{
    private function lojaMatrizId(): ?int
    {
        if (! Schema::hasTable('tenants')) {
            return null;
        }

        return DB::table('tenants')->where('slug', 'gostosuras')->value('id');
    }

    private function backfill(string $tabela, ?int $lojaId): void
    {
        if ($lojaId === null) {
            return;
        }

        DB::table($tabela)->whereNull('loja_id')->update(['loja_id' => $lojaId]);
    }

    private function temIndex(string $tabela, string $nome): bool
    {
        return collect(Schema::getIndexes($tabela))->contains(fn ($idx) => $idx['name'] === $nome);
    }

    private function indiceUnicoPorLoja(string $tabela, array $colunas, string $chaveUnicaAntiga): void
    {
        if ($this->temIndex($tabela, $chaveUnicaAntiga)) {
            Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($chaveUnicaAntiga));
        }

        Schema::table($tabela, fn (Blueprint $t) => $t->unique(array_merge(['loja_id'], $colunas), $tabela.'_loja_'.implode('_', $colunas).'_unique'));
    }

    public function up(): void
    {
        $lojaMatriz = $this->lojaMatrizId();

        // ===== Produtos =====
        if (! Schema::hasColumn('produtos', 'loja_id')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
                $table->foreign('loja_id')->references('id')->on('tenants')->nullOnDelete();
            });

            $this->backfill('produtos', $lojaMatriz);
            $this->indiceUnicoPorLoja('produtos', ['slug'], 'produtos_slug_unique');
        }

        // ===== Categorias =====
        if (! Schema::hasColumn('categorias', 'loja_id')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
                $table->foreign('loja_id')->references('id')->on('tenants')->nullOnDelete();
            });

            $this->backfill('categorias', $lojaMatriz);
            $this->indiceUnicoPorLoja('categorias', ['slug'], 'categorias_slug_unique');
        }

        // ===== Pedidos =====
        if (! Schema::hasColumn('pedidos', 'loja_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
                $table->foreign('loja_id')->references('id')->on('tenants')->nullOnDelete();
                $table->index('loja_id');
            });

            $this->backfill('pedidos', $lojaMatriz);
        }

        // ===== Cupons =====
        if (! Schema::hasColumn('cupons', 'loja_id')) {
            Schema::table('cupons', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
                $table->foreign('loja_id')->references('id')->on('tenants')->nullOnDelete();
            });

            $this->backfill('cupons', $lojaMatriz);
            $this->indiceUnicoPorLoja('cupons', ['codigo'], 'cupons_codigo_unique');
        }

        // ===== Banners =====
        if (! Schema::hasColumn('banners', 'loja_id')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
                $table->foreign('loja_id')->references('id')->on('tenants')->nullOnDelete();
            });

            $this->backfill('banners', $lojaMatriz);
        }

        // ===== Configurações (globais + override por loja) =====
        if (! Schema::hasColumn('configuracoes', 'loja_id')) {
            Schema::table('configuracoes', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
            });
        }

        $this->indiceUnicoPorLoja('configuracoes', ['chave'], 'configuracoes_chave_unique');

        // ===== Textos (globais + override por loja) =====
        if (! Schema::hasColumn('textos', 'loja_id')) {
            Schema::table('textos', function (Blueprint $table) {
                $table->unsignedBigInteger('loja_id')->nullable()->after('id');
            });
        }

        $this->indiceUnicoPorLoja('textos', ['pagina', 'chave'], 'textos_pagina_chave_unique');
    }

    public function down(): void
    {
        throw new RuntimeException('A migração de multi-lojas não pode ser revertida (dados vinculados às lojas).');
    }
};
