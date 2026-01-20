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
        Schema::create('re_compounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('re_areas')->onDelete('cascade');
            $table->foreignId('developer_id')->nullable()->constrained('re_developers')->onDelete('set null');
            
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            
            // Location (Geo-spatial)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Media
            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();
            $table->string('virtual_tour_url')->nullable();
            
            // Compound Details
            $table->year('launch_year')->nullable();
            $table->year('delivery_year')->nullable();
            $table->decimal('total_area', 12, 2)->nullable(); // in sqm
            $table->unsignedInteger('units_count')->nullable();
            
            // Status
            $table->enum('construction_status', ['planning', 'under_construction', 'completed'])->default('under_construction');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('priority')->default(0);
            
            // SEO Fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Cached Stats
            $table->unsignedInteger('properties_count')->default(0);
            $table->unsignedInteger('available_properties_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            
            // Price Range Cache
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();
            $table->string('price_currency', 3)->default('USD');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['area_id', 'developer_id', 'is_published']);
            $table->index(['construction_status', 'is_featured']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_compounds');
    }
};
