<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->enum('modelo', ['nfe', 'nfce'])->default('nfe');
            $table->enum('status', ['pendente', 'transmitida', 'cancelada', 'erro'])->default('pendente');
            $table->unsignedBigInteger('numero')->nullable();
            $table->unsignedInteger('serie')->default(1);
            $table->string('chave_acesso', 60)->nullable();
            $table->string('xml_path', 255)->nullable();
            $table->string('danfe_path', 255)->nullable();
            $table->text('mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');
    }
};
