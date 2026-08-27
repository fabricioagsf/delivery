<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            // gatilho = trigger do banco; aplicacao = trait Auditoravel (com autoria)
            $table->enum('origem', ['gatilho', 'aplicacao'])->default('gatilho');
            $table->string('acao', 10); // INSERT, UPDATE, DELETE
            $table->string('tabela', 64);
            $table->string('registro_id', 191)->nullable();
            $table->json('dados_antigos')->nullable();
            $table->json('dados_novos')->nullable();
            $table->string('autor', 191)->nullable();   // quem fez (camada aplicação)
            $table->string('ip', 45)->nullable();
            $table->string('url', 255)->nullable();

            // campos extras para restauração de eventos de UPDATE/DELETE via gatilho
            $table->timestamp('criado_em')->useCurrent();

            $table->index(['tabela', 'registro_id']);
            $table->index('criado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};
