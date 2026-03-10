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
     * Creates the re_viewings table for F2.5 Viewing Scheduler.
     * Replaces the never-implemented F1.3 appointments concept.
     */
    public function up(): void
    {
        Schema::create('re_viewings', function (Blueprint $table) {
            $table->id();

            // What is being viewed
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->unsignedBigInteger('compound_id')->nullable()->index();

            // Who is involved
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Contact info (denormalised for non-registered visitors)
            $table->string('contact_name');
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            // Scheduling
            $table->dateTime('scheduled_at');
            $table->enum('status', [
                'pending',
                'confirmed',
                'declined',
                'rescheduled',
                'completed',
                'no_show',
            ])->default('pending')->index();

            // Notes
            $table->text('notes')->nullable();
            $table->text('result_notes')->nullable()->comment('Agent notes after completing/no-show');

            // Rescheduling tracking
            $table->unsignedSmallInteger('reschedule_count')->default(0);
            $table->dateTime('rescheduled_to')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FK constraints (using tenant DB; no cross-DB FKs needed)
            $table->foreign('property_id')->references('id')->on('re_properties')->onDelete('set null');
            $table->foreign('compound_id')->references('id')->on('re_compounds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_viewings');
    }
};
