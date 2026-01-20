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
        Schema::create('re_compound_amenities', function (Blueprint $table) {
            $table->foreignId('compound_id')->constrained('re_compounds')->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained('re_amenities')->onDelete('cascade');
            
            $table->primary(['compound_id', 'amenity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_compound_amenities');
    }
};
