<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('re_compounds', function (Blueprint $table) {
            // Add latitude and longitude columns
            $table->decimal('latitude', 10, 7)->nullable()->after('slug');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            
            // Add full address field
            $table->string('address', 500)->nullable()->after('longitude');
            
            // Add indexes for geo queries
            $table->index(['latitude', 'longitude'], 're_compounds_geo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('re_compounds', function (Blueprint $table) {
            $table->dropIndex('re_compounds_geo_idx');
            $table->dropColumn(['latitude', 'longitude', 'address']);
        });
    }
};
