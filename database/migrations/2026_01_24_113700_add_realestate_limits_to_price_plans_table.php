<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add RealEstate plan limit columns to price_plans table.
 * 
 * These columns control how many properties and compounds a tenant can create
 * based on their subscription plan.
 * 
 * Values:
 * - Positive integer: Maximum allowed items
 * - 0 or null: Unlimited
 * - -1: Feature disabled
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('central')->table('price_plans', function (Blueprint $table) {
            // Property listing limit (e.g., 25, 50, 100, unlimited)
            $table->integer('property_permission_feature')
                ->nullable()
                ->default(0)
                ->after('knowledgebase_permission_feature')
                ->comment('Max properties allowed. 0 = unlimited, -1 = disabled');

            // Compound listing limit
            $table->integer('compound_permission_feature')
                ->nullable()
                ->default(0)
                ->after('property_permission_feature')
                ->comment('Max compounds allowed. 0 = unlimited, -1 = disabled');

            // Inquiry limit per month (optional)
            $table->integer('inquiry_permission_feature')
                ->nullable()
                ->default(0)
                ->after('compound_permission_feature')
                ->comment('Max inquiries per month. 0 = unlimited');

            // Saved properties limit (for customers)
            $table->integer('saved_property_permission_feature')
                ->nullable()
                ->default(0)
                ->after('inquiry_permission_feature')
                ->comment('Max saved properties. 0 = unlimited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->table('price_plans', function (Blueprint $table) {
            $table->dropColumn([
                'property_permission_feature',
                'compound_permission_feature',
                'inquiry_permission_feature',
                'saved_property_permission_feature',
            ]);
        });
    }
};
