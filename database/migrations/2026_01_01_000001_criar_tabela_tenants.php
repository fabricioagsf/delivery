<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('auth-multi.tabelas.tenants', 'tenants'), function (Blueprint $tabela) {
            $tabela->id()->comment('Primary key do tenant');
            $tabela->string('nome', 150);
            $tabela->string('slug', 100)->unique();
            $tabela->string('dominio', 190)->nullable()->unique();
            $tabela->string('status', 20)->default('ativo');
            $tabela->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('auth-multi.tabelas.tenants', 'tenants'));
    }
};
