<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_filiais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('dominio')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('saas_empresas')->onDelete('cascade');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('saas_empresa_id')->nullable()->after('id');
            $table->foreign('saas_empresa_id')->references('id')->on('saas_empresas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['saas_empresa_id']);
            $table->dropColumn('saas_empresa_id');
        });
        Schema::dropIfExists('saas_filiais');
    }
};
