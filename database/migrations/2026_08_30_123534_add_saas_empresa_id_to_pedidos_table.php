<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('saas_empresa_id')->nullable()->after('loja_id');
            $table->foreign('saas_empresa_id')
                ->references('id')
                ->on('saas_empresas')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['saas_empresa_id']);
            $table->dropColumn('saas_empresa_id');
        });
    }
};
