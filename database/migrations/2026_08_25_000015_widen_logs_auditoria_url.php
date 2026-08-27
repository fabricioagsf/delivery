<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // URLs de callback OAuth (com code/state/scope) passam de 255 caracteres.
        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->string('url', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->string('url', 255)->nullable()->change();
        });
    }
};
