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
    }

    public function down(): void
    {
        if (Schema::hasColumn('pedidos', 'mesa_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->dropForeign(['mesa_id']);
                $table->dropColumn('mesa_id');
            });
        }
    }
};