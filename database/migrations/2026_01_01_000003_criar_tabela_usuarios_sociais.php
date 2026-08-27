<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('auth-multi.tabelas.sociais', 'usuarios_sociais'), function (Blueprint $tabela) {
            $tabela->id()->comment('Primary key do vinculo social');
            $tabela->foreignId('usuario_id')
                ->constrained(config('auth-multi.tabelas.usuarios', 'usuarios'))
                ->cascadeOnDelete();
            $tabela->string('provedor', 20)->index();
            $tabela->string('provedor_usuario_id', 190);
            $tabela->json('dados')->nullable();
            $tabela->timestamps();

            $tabela->unique(['provedor', 'provedor_usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('auth-multi.tabelas.sociais', 'usuarios_sociais'));
    }
};
