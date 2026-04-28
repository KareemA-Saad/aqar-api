<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the re_inquiry_notes table.
 *
 * Structured notes written by agents on inquiries.
 * Replaces the fragile admin_notes text-append pattern.
 *
 * - content is capped at 2000 characters (enforced in service layer)
 * - is_internal = true  → only agents/admins can read
 * - is_internal = false → visible to admin + agent (still NOT to the buyer user)
 * - Soft-deleted so the timeline retains the event (note_added) even if the note is removed
 *
 * Part of F2.2: Inquiry Timeline & Notes
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('re_inquiry_notes')) {
            Schema::create('re_inquiry_notes', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('inquiry_id');
                $table->foreign('inquiry_id')
                    ->references('id')->on('re_property_inquiries')
                    ->onDelete('cascade');

                // The agent who wrote this note
                $table->unsignedBigInteger('agent_id');
                $table->foreign('agent_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');

                // Note body — max 2000 chars enforced in service
                $table->text('content');

                // Internal flag: if true, only visible to agents/admins
                $table->boolean('is_internal')->default(false);

                // Soft-delete so timeline still shows "note added/removed" events
                $table->softDeletes();

                $table->timestamps();

                // Queries: notes for a specific inquiry (timeline, count)
                $table->index(['inquiry_id', 'deleted_at'], 'idx_notes_inquiry');

                // Queries: notes by a specific agent
                $table->index(['agent_id', 'inquiry_id'], 'idx_notes_agent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('re_inquiry_notes');
    }
};
