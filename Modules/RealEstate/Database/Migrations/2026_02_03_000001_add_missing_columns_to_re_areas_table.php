<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds missing columns to re_areas table that are used by AreaSeeder and AreaService.
     */
    public function up(): void
    {
        Schema::table('re_areas', function (Blueprint $table) {
            // Feature flag for highlighting popular areas
            $table->boolean('is_featured')->default(false)->after('status');
            
            // Geographic coordinates for map display
            $table->decimal('latitude', 10, 8)->nullable()->after('is_featured');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Area thumbnail image
            $table->string('image')->nullable()->after('longitude');
            
            // Add index for featured areas query
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('re_areas', function (Blueprint $table) {
            $table->dropIndex(['is_featured']);
            $table->dropColumn(['is_featured', 'latitude', 'longitude', 'image']);
        });
    }
};
