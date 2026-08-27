<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('auth-multi.tabelas.usuarios', 'usuarios'), function (Blueprint $tabela) {
            $tabela->id()->comment('Primary key do usuario');
            $tabela->foreignId('tenant_id')
                ->nullable()
                ->constrained(config('auth-multi.tabelas.tenants', 'tenants'))
                ->nullOnDelete();
            $tabela->string('tipo', 20)->index();
            $tabela->string('nome', 150);
            $tabela->string('email', 190);
            $tabela->string('senha', 255);
            $tabela->boolean('ativo')->default(true);
            $tabela->timestamp('ultimo_login')->nullable();
            $tabela->rememberToken();
            $tabela->timestamps();

            $tabela->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('auth-multi.tabelas.usuarios', 'usuarios'));
    }
};
