<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table supports multi-room bookings where a single booking
     * can have multiple room types with different quantities.
     */
    public function up(): void
    {
        Schema::create('booking_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_information_id')
                ->constrained('booking_informations')
                ->cascadeOnDelete();
            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->comment('Price per room per night');
            $table->decimal('subtotal', 10, 2)->comment('quantity * nights * unit_price');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->json('meal_options')->nullable()->comment('breakfast, lunch, dinner selections');
            $table->timestamps();

            $table->index(['booking_information_id', 'room_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_room_types');
    }
};
