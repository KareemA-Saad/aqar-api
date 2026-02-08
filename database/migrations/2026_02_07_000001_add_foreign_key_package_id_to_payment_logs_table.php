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
     * The original payment_logs migration used STRING for package_id,
     * but price_plans.id is BIGINT UNSIGNED. We convert the type first,
     * then add the FK constraint.
     */
    public function up(): void
    {
        // 1. Fix any existing orphaned or invalid package_id values (before type conversion)
        DB::statement(<<<'SQL'
            UPDATE payment_logs
            SET package_id = NULL
            WHERE package_id IS NOT NULL
              AND (
                  package_id = ''
                  OR package_id NOT REGEXP '^[0-9]+$'
                  OR CAST(package_id AS UNSIGNED) NOT IN (SELECT id FROM price_plans)
              )
        SQL);

        // 2. Convert package_id from VARCHAR to BIGINT UNSIGNED to match price_plans.id
        //    Using raw SQL to avoid doctrine/dbal dependency
        DB::statement('ALTER TABLE payment_logs MODIFY COLUMN package_id BIGINT UNSIGNED NULL');

        // 3. Add the foreign key constraint
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

        // Revert column type back to VARCHAR (original schema)
        // Note: data loss may occur if package_id values exceed VARCHAR range
        DB::statement('ALTER TABLE payment_logs MODIFY COLUMN package_id VARCHAR(255) NULL');
    }
};
