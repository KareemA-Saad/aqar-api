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
        Schema::create('re_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('name', 255);
            $table->json('criteria'); // Stores search parameters (price_min, price_max, bedrooms, etc.)
            
            // Alert configuration
            $table->boolean('alerts_enabled')->default(true);
            $table->enum('alert_frequency', ['daily', 'weekly'])->default('daily');
            
            // Tracking timestamps
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamp('last_matched_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'alerts_enabled']);
            $table->index('last_alerted_at');
            
            // Unique constraint: prevent duplicate search names per user (case-insensitive handled in service)
            $table->unique(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_saved_searches');
    }
};
