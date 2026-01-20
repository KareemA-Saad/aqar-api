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
        Schema::create('re_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compound_id')->constrained('re_compounds')->onDelete('cascade');
            $table->foreignId('property_type_id')->constrained('re_property_types')->onDelete('restrict');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('price_type', ['total', 'per_sqm'])->default('total');
            $table->enum('listing_type', ['sale', 'rent'])->default('sale');
            $table->enum('payment_option', ['cash', 'installment', 'both'])->default('cash');
            
            // Installment Details (JSON)
            $table->json('installment_details')->nullable();
            
            // Property Features
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->enum('area_unit', ['sqm', 'sqft'])->default('sqm');
            $table->unsignedSmallInteger('floor_number')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->enum('finishing', ['finished', 'semi_finished', 'unfinished', 'furnished'])->nullable();
            $table->enum('view', ['garden', 'pool', 'street', 'sea', 'city', 'landscape', 'none'])->nullable();
            
            // Availability
            $table->boolean('is_available')->default(true);
            $table->date('delivery_date')->nullable();
            $table->string('reference_number')->unique()->nullable();
            
            // Media
            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();
            $table->string('virtual_tour_url')->nullable();
            $table->json('floor_plan_images')->nullable();
            
            // SEO Fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Status & Features
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('priority')->default(0);
            
            // Stats
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('inquiry_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for filtering
            $table->index(['compound_id', 'property_type_id', 'is_published', 'listing_type'], 're_properties_main_idx');
            $table->index(['price', 'area', 'bedrooms'], 're_properties_filter_idx');
            $table->index(['is_featured', 'priority'], 're_properties_featured_idx');
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_properties');
    }
};
