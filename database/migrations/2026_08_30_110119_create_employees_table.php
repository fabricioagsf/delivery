<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('cargo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('saas_empresas')->onDelete('cascade');
        });

        Schema::create('saas_employee_filial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('filial_id');
            $table->timestamps();

            $table->unique(['employee_id', 'filial_id']);
            $table->foreign('employee_id')->references('id')->on('saas_employees')->onDelete('cascade');
            $table->foreign('filial_id')->references('id')->on('saas_filiais')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_employee_filial');
        Schema::dropIfExists('saas_employees');
    }
};
