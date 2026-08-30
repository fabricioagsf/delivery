<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('chave');
            $table->text('valor')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'chave']);
            $table->foreign('empresa_id')->references('id')->on('saas_empresas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_configs');
    }
};
