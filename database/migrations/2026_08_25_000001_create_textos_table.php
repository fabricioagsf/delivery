<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textos', function (Blueprint $table) {
            $table->id();
            $table->string('pagina', 60);
            $table->string('chave', 120);
            $table->text('valor');
            $table->timestamps();

            $table->unique(['pagina', 'chave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textos');
    }
};
