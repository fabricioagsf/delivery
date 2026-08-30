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
        Schema::table('saas_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_roles', 'slug')) {
                $table->string('slug')->nullable()->after('nome');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saas_roles', function (Blueprint $table) {
            if (Schema::hasColumn('saas_roles', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
