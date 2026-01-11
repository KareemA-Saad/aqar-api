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
     * Cancellation policies can be set at hotel or room type level.
     * Multiple tiers allow for graduated refund percentages.
     */
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')
                ->nullable()
                ->constrained('hotels')
                ->cascadeOnDelete();
            $table->foreignId('room_type_id')
                ->nullable()
                ->constrained('room_types')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_refundable')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['hotel_id', 'is_default']);
            $table->index(['room_type_id']);
        });

        // Policy tiers for graduated refund rules
        Schema::create('cancellation_policy_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_policy_id')
                ->constrained('cancellation_policies')
                ->cascadeOnDelete();
            $table->unsignedInteger('hours_before_checkin')->comment('Cancel at least X hours before check-in');
            $table->unsignedInteger('refund_percentage')->comment('Percentage of payment to refund (0-100)');
            $table->timestamps();

            $table->index(['cancellation_policy_id', 'hours_before_checkin'], 'cp_tiers_policy_hours_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellation_policy_tiers');
        Schema::dropIfExists('cancellation_policies');
    }
};
