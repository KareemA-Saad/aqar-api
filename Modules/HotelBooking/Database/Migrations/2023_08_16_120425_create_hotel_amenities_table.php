<?php

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
        Schema::disableForeignKeyConstraints();

        Schema::create('hotel_amenities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("hotel_id");
            $table->unsignedBigInteger("amenities_id");
            $table->foreign('hotel_id')->references("id")->on("hotels")->cascadeOnDelete();
            $table->foreign('amenities_id')->references("id")->on("amenities")->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_aminities');
    }
};
