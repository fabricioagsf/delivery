<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_employee_store', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('loja_id');
            $table->timestamps();

            $table->unique(['employee_id', 'loja_id']);
            $table->foreign('employee_id')->references('id')->on('saas_employees')->onDelete('cascade');
            $table->foreign('loja_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_employee_store');
    }
};
