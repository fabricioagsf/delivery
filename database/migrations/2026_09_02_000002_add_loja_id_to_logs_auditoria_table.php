<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria com isolamento por loja: `logs_auditoria.loja_id` permite ao
 * painel filtrar (e restaurar) apenas eventos da loja ativa. Backfill lê o
 * `loja_id` guardado nos snapshots JSON (dados_novos/dados_antigos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->unsignedBigInteger('loja_id')->nullable()->after('tabela');
            $table->index('loja_id');
        });

        // Backfill: extrai o loja_id dos snapshots dos eventos já gravados.
        // CAST AS UNSIGNED de NULL/algo inválido devolve NULL (fica sem loja).
        DB::statement(
            'UPDATE logs_auditoria SET loja_id = COALESCE('
            .'CAST(JSON_EXTRACT(dados_novos, \'$.loja_id\') AS UNSIGNED), '
            .'CAST(JSON_EXTRACT(dados_antigos, \'$.loja_id\') AS UNSIGNED)) '
            .'WHERE loja_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->dropIndex(['loja_id']);
            $table->dropColumn('loja_id');
        });
    }
};