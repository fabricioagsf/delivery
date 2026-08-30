<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('comissao_valor', 10, 2)->default(0);
            $table->timestamp('registrado_em')->useCurrent();
            $table->timestamps();

            $table->unique(['pedido_id', 'employee_id'], 'pedido_employee_unico');
            $table->foreign('pedido_id')->references('id')->on('pedidos')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('saas_employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_employees');
    }
};
