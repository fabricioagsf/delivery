<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pedidos', 'mesa_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->unsignedBigInteger('mesa_id')->nullable()->after('cliente_id');
                $table->foreign('mesa_id')->references('id')->on('mesas')->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('pedidos', 'pedidos_mesa_entrega_idx')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->index(['mesa_id', 'entregue_mesa_em'], 'pedidos_mesa_entrega_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pedidos', 'mesa_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->dropForeign(['mesa_id']);
                $table->dropIndex('pedidos_mesa_entrega_idx');
                $table->dropColumn('mesa_id');
            });
        }
    }
};