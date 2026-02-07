<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a foreign key constraint on payment_logs.package_id → price_plans.id
     * to prevent orphaned references when a plan is deleted.
     *
     * Before adding the FK, any orphaned rows (package_id pointing to
     * a non-existent price_plans row) are set to NULL so the migration
     * doesn't fail on existing data.
     */
    public function up(): void
    {
        // 1. Fix any existing orphaned package_id values
        DB::statement(<<<'SQL'
            UPDATE payment_logs
            SET package_id = NULL
            WHERE package_id IS NOT NULL
              AND package_id NOT IN (SELECT id FROM price_plans)
        SQL);

        // 2. Add the foreign key constraint
        Schema::table('payment_logs', function (Blueprint $table) {
            $table->foreign('package_id')
                  ->references('id')
                  ->on('price_plans')
                  ->nullOnDelete();   // If a plan is deleted, set to NULL instead of cascading
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_logs', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
        });
    }
};
