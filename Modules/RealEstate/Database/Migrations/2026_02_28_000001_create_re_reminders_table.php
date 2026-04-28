<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the re_reminders table.
 *
 * Stores follow-up reminders created by agents for their assigned inquiries.
 * Supports snooze (update remind_at), completion, and max-5-per-inquiry limit.
 * SLA status is computed at runtime from PropertyInquiry timestamps — not stored.
 *
 * Part of F2.1: Follow-up Reminders & SLA Timers
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('re_reminders')) {
            Schema::create('re_reminders', function (Blueprint $table) {
                $table->id();

                // The agent who owns this reminder (must own the inquiry too)
                $table->unsignedBigInteger('agent_id');
                $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');

                // The inquiry this reminder is tied to
                $table->unsignedBigInteger('inquiry_id');
                $table->foreign('inquiry_id')->references('id')->on('re_property_inquiries')->onDelete('cascade');

                // When to fire the reminder
                $table->dateTime('remind_at');

                // Optional note for context ("Call back — client requested afternoon")
                $table->string('message', 500)->nullable();

                // Completion state
                $table->boolean('is_completed')->default(false);
                $table->dateTime('completed_at')->nullable();

                $table->timestamps();

                // Prevent hammering: index for scheduler lookup
                $table->index(['is_completed', 'remind_at'], 'idx_reminders_due');

                // Index for per-inquiry queries (enforce 5-reminder cap check)
                $table->index(['inquiry_id', 'is_completed'], 'idx_reminders_inquiry');

                // Index for agent's reminder list
                $table->index(['agent_id', 'is_completed', 'remind_at'], 'idx_reminders_agent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('re_reminders');
    }
};
