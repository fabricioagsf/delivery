<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pedidos', 'tipo_entrega')) {
            DB::statement("ALTER TABLE pedidos MODIFY COLUMN tipo_entrega ENUM('entrega', 'retirada', 'mesa') NOT NULL DEFAULT 'entrega'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pedidos', 'tipo_entrega')) {
            DB::statement("ALTER TABLE pedidos MODIFY COLUMN tipo_entrega ENUM('entrega', 'retirada') NOT NULL DEFAULT 'entrega'");
        }
    }
};