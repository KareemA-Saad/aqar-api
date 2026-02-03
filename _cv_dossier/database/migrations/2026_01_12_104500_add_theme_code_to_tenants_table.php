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
        if (!Schema::hasColumn('tenants', 'theme_code')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('theme_code')->nullable()->after('theme_slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'theme_code')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('theme_code');
            });
        }
    }
};
