<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\RealEstate\Entities\InquiryNote;
use Modules\RealEstate\Entities\PropertyInquiry;
use Spatie\Activitylog\Models\Activity;

/**
 * InquiryNoteService
 *
 * Business logic for F2.2: Inquiry Timeline & Notes.
 *
 * Responsibilities:
 *  - CRUD for agent notes on inquiries (InquiryNote model)
 *  - Building the merged chronological timeline:
 *      Auto-events from Spatie activity_log  (status changes, agent assignments, contacts)
 *      Manual entries from re_inquiry_notes  (agent notes)
 *
 * @package Modules\RealEstate\Services
 */
class InquiryNoteService
{
    /**
     * Maximum note length (characters).
     */
    public const MAX_NOTE_LENGTH = 2000;

    // =========================================================================
    // NOTES — CRUD
    // =========================================================================

    /**
     * Add a note to an inquiry, authored by the given agent.
     *
     * Validates:
     *  - Agent must own the inquiry (agent_id match)
     *  - Content ≤ MAX_NOTE_LENGTH
     *
     * Also records a 'note_added' activity on the inquiry via Spatie
     * so the event appears in the timeline.
     *
     * @param int   $agentId
     * @param int   $inquiryId
     * @param array $data  Keys: content (required), is_internal (optional, default false)
     * @return array{ok: bool, note?: InquiryNote, error?: string, code?: int}
     */
    public function addNote(int $agentId, int $inquiryId, array $data): array
    {
        $inquiry = PropertyInquiry::where('id', $inquiryId)
            ->where('agent_id', $agentId)
            ->first();

        if (!$inquiry) {
            return ['ok' => false, 'error' => 'Inquiry not found or not assigned to you.', 'code' => 404];
        }

        $content = mb_substr(trim($data['content'] ?? ''), 0, self::MAX_NOTE_LENGTH);

        if (mb_strlen($content) === 0) {
            return ['ok' => false, 'error' => 'Note content cannot be empty.', 'code' => 422];
        }

        $isInternal = (bool) ($data['is_internal'] ?? false);

        $note = InquiryNote::create([
            'inquiry_id'  => $inquiryId,
            'agent_id'    => $agentId,
            'content'     => $content,
            'is_internal' => $isInternal,
        ]);

        // Record a manual activity so the note appears in the timeline
        activity('inquiry')
            ->performedOn($inquiry)
            ->causedBy($note->agent ?? auth()->user())
            ->withProperties([
                'note_id'     => $note->id,
                'is_internal' => $isInternal,
                'preview'     => mb_substr($content, 0, 80) . (mb_strlen($content) > 80 ? '…' : ''),
            ])
            ->event('note_added')
            ->log('Note added');

        return ['ok' => true, 'note' => $note->load('agent')];
    }

    /**
     * Get all notes for an inquiry, agent-scoped.
     * Returns soft-deleted notes too (for admin), but normally excludes them.
     *
     * @param int  $inquiryId
     * @param int  $agentId     Agent must own inquiry
     * @param bool $withTrashed Include soft-deleted notes (admin view)
     * @return array{ok: bool, notes?: Collection, error?: string, code?: int}
     */
    public function getNotesForInquiry(int $inquiryId, int $agentId, bool $withTrashed = false): array
    {
        $inquiry = PropertyInquiry::where('id', $inquiryId)
            ->where('agent_id', $agentId)
            ->first();

        if (!$inquiry) {
            return ['ok' => false, 'error' => 'Inquiry not found or not assigned to you.', 'code' => 404];
        }

        $query = InquiryNote::with('agent')
            ->forInquiry($inquiryId)
            ->orderBy('created_at', 'asc');

        if ($withTrashed) {
            $query->withTrashed();
        }

        return ['ok' => true, 'notes' => $query->get()];
    }

    /**
     * Soft-delete a note. Agent can only delete their own notes.
     *
     * @param int $noteId
     * @param int $agentId
     * @return array{ok: bool, error?: string, code?: int}
     */
    public function deleteNote(int $noteId, int $agentId): array
    {
        $note = InquiryNote::where('id', $noteId)
            ->where('agent_id', $agentId)
            ->first();

        if (!$note) {
            return ['ok' => false, 'error' => 'Note not found or you do not own it.', 'code' => 404];
        }

        $note->delete(); // soft-delete

        return ['ok' => true];
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    /**
     * Build the merged chronological timeline for an inquiry.
     *
     * Merges:
     *  1. Spatie activity_log events (status changes, agent assignments, contacts, note_added)
     *  2. re_inquiry_notes entries (for the full note content)
     *
     * The activity_log gives us the event shell; the note content comes from
     * the InquiryNote record. This avoids duplicating content in two places.
     *
     * Timeline event shape:
     * {
     *   id:          string (prefixed: "act_123" | "note_456")
     *   type:        string (status_changed | agent_assigned | contacted | note_added | reminder_set | created)
     *   description: string
     *   causer:      { id, name } | null
     *   properties:  object  (event-specific data)
     *   note:        { id, content, is_internal } | null  (only for note_added events)
     *   occurred_at: ISO8601 string
     * }
     *
     * @param int $inquiryId
     * @param int $agentId        Agent must own inquiry
     * @param int $perPage        Default 20
     * @return array{ok: bool, data?: LengthAwarePaginator, error?: string, code?: int}
     */
    public function getTimeline(int $inquiryId, int $agentId, int $perPage = 20): array
    {
        $inquiry = PropertyInquiry::where('id', $inquiryId)
            ->where('agent_id', $agentId)
            ->first();

        if (!$inquiry) {
            return ['ok' => false, 'error' => 'Inquiry not found or not assigned to you.', 'code' => 404];
        }

        // Pull activity log events for this inquiry (all types)
        $activities = Activity::with('causer')
            ->where('subject_type', PropertyInquiry::class)
            ->where('subject_id', $inquiryId)
            ->where('log_name', 'inquiry')
            ->orderBy('created_at', 'asc')
            ->get();

        // Pull notes (including soft-deleted so the timeline shows "note removed")
        $notes = InquiryNote::withTrashed()
            ->with('agent')
            ->forInquiry($inquiryId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->keyBy('id');

        // Merge and sort
        $timeline = $activities->map(function (Activity $activity) use ($notes): array {
            $props    = $activity->properties->toArray();
            $noteData = null;

            if ($activity->event === 'note_added' && isset($props['note_id'])) {
                $note = $notes->get($props['note_id']);
                if ($note) {
                    $noteData = [
                        'id'          => $note->id,
                        'content'     => $note->deleted_at ? null : $note->content,
                        'is_internal' => $note->is_internal,
                        'deleted'     => $note->deleted_at !== null,
                    ];
                }
            }

            return [
                'id'          => 'act_' . $activity->id,
                'type'        => $activity->event ?? 'updated',
                'description' => $activity->description,
                'causer'      => $activity->causer ? [
                    'id'   => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'properties'  => $this->formatProperties($activity->event, $props),
                'note'        => $noteData,
                'occurred_at' => $activity->created_at?->toISOString(),
            ];
        })->sortByDesc('occurred_at')->values();

        // Manual pagination on the merged collection
        $page     = request()->input('page', 1);
        $offset   = ($page - 1) * $perPage;
        $items    = $timeline->slice($offset, $perPage)->values();
        $total    = $timeline->count();

        // Return a simple paginator-compatible array so the controller can
        // build its own meta block without a real LengthAwarePaginator overhead
        return [
            'ok'   => true,
            'data' => [
                'items'        => $items,
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => (int) $page,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Format activity properties into a clean, human-readable shape per event type.
     */
    private function formatProperties(string $event, array $raw): array
    {
        return match ($event) {
            'status_changed' => [
                'from' => $raw['old']['status'] ?? null,
                'to'   => $raw['attributes']['status'] ?? null,
            ],
            'agent_assigned' => [
                'from' => $raw['old']['agent_id'] ?? null,
                'to'   => $raw['attributes']['agent_id'] ?? null,
            ],
            'contacted' => [
                'contacted_at' => $raw['attributes']['contacted_at'] ?? null,
            ],
            'note_added' => [
                'is_internal' => $raw['is_internal'] ?? false,
                'preview'     => $raw['preview'] ?? null,
            ],
            default => $raw,
        };
    }
}
