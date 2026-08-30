<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_employee_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'role_id', 'filial_id'], 'saas_emp_role_filial_unico');
            $table->foreign('employee_id')->references('id')->on('saas_employees')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('saas_roles')->onDelete('cascade');
            $table->foreign('filial_id')->references('id')->on('saas_filiais')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_employee_role');
    }
};
