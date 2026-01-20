<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_tenants')
                ->default(1)
                ->after('campaign_create_permission')
                ->comment('Maximum number of tenants allowed per user on this plan');
        });

        // Update existing plans with recommended values based on typical pricing tiers
        // Free/Basic plans: 1 tenant, Standard: 3, Premium: 5, Enterprise: 10
        DB::table('price_plans')->where('id', 1)->update(['max_tenants' => 1]);
        DB::table('price_plans')->where('id', 2)->update(['max_tenants' => 1]);
        DB::table('price_plans')->where('id', 3)->update(['max_tenants' => 3]);
        DB::table('price_plans')->where('id', 4)->update(['max_tenants' => 5]);
        DB::table('price_plans')->where('id', 5)->update(['max_tenants' => 10]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_plans', function (Blueprint $table) {
            $table->dropColumn('max_tenants');
        });
    }
};
