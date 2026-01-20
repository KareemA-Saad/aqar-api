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
        Schema::create('re_compound_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compound_id')->constrained('re_compounds')->onDelete('cascade');
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->enum('type', ['gallery', 'master_plan', 'unit_plan', 'location_map'])->default('gallery');
            $table->integer('order')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['compound_id', 'type', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_compound_images');
    }
};
