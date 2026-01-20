<?php

declare(strict_types=1);

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
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('subscription_status', ['active', 'trial', 'expired', 'suspended'])
                ->default('active')
                ->after('theme_code')
                ->comment('Current subscription status of the tenant');
            
            $table->timestamp('suspended_at')
                ->nullable()
                ->after('subscription_status')
                ->comment('Timestamp when tenant was suspended');
            
            $table->string('suspension_reason')
                ->nullable()
                ->after('suspended_at')
                ->comment('Reason for tenant suspension');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'suspended_at', 'suspension_reason']);
        });
    }
};
