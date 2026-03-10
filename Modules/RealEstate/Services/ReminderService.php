<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Entities\Reminder;
use Modules\RealEstate\Notifications\ReminderDueNotification;

/**
 * ReminderService
 *
 * Business logic for F2.1: Follow-up Reminders & SLA Timers.
 *
 * Responsibilities:
 *  - CRUD for agent reminders (capped at MAX_REMINDERS_PER_INQUIRY active per inquiry)
 *  - Snooze (+1 h / +4 h / +1 d)
 *  - SLA summary (on_track / warning / breached counts for an agent's inquiries)
 *  - Collect due reminders for the scheduler command (SendDueReminders)
 *
 * @package Modules\RealEstate\Services
 */
class ReminderService
{
    /**
     * Maximum active (non-completed) reminders per inquiry.
     */
    public const MAX_REMINDERS_PER_INQUIRY = 5;

    /**
     * Allowed snooze offsets (passed to Carbon::modify()).
     */
    public const SNOOZE_OPTIONS = [
        '+1 hour',
        '+4 hours',
        '+1 day',
    ];

    // =========================================================================
    // READ
    // =========================================================================

    /**
     * Paginated list of reminders for the authenticated agent.
     *
     * @param int   $agentId
     * @param array $filters  Keys: status(pending|completed|overdue), inquiry_id, from, to, per_page
     */
    public function getAgentReminders(int $agentId, array $filters = []): LengthAwarePaginator
    {
        $query = Reminder::with(['inquiry.property', 'inquiry.compound'])
            ->forAgent($agentId)
            ->orderBy('remind_at', 'asc');

        // Status filter
        $status = $filters['status'] ?? 'pending';

        if ($status === 'completed') {
            $query->where('is_completed', true);
        } elseif ($status === 'overdue') {
            $query->where('is_completed', false)
                  ->where('remind_at', '<', Carbon::now());
        } else {
            // default: pending (not completed)
            $query->where('is_completed', false);
        }

        if (!empty($filters['inquiry_id'])) {
            $query->where('inquiry_id', (int) $filters['inquiry_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('remind_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (!empty($filters['to'])) {
            $query->where('remind_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * All reminders (active + completed) for a specific inquiry, agent-scoped.
     *
     * @param int $inquiryId
     * @param int $agentId   Security: agent must own this inquiry
     */
    public function getInquiryReminders(int $inquiryId, int $agentId): Collection
    {
        return Reminder::where('inquiry_id', $inquiryId)
            ->where('agent_id', $agentId)
            ->orderBy('remind_at', 'asc')
            ->get();
    }

    /**
     * Find a reminder and verify the agent owns it.
     *
     * @param int $reminderId
     * @param int $agentId
     * @return Reminder|null
     */
    public function findOwned(int $reminderId, int $agentId): ?Reminder
    {
        return Reminder::where('id', $reminderId)
            ->where('agent_id', $agentId)
            ->first();
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /**
     * Create a new reminder for an inquiry.
     *
     * Validates:
     *  - Agent owns the inquiry (agent_id match)
     *  - remind_at is in the future
     *  - Active reminder cap not exceeded (MAX_REMINDERS_PER_INQUIRY)
     *
     * @param int   $agentId
     * @param int   $inquiryId
     * @param array $data  Keys: remind_at (required), message (optional)
     * @return array{ok: bool, reminder?: Reminder, error?: string, code?: int}
     */
    public function createReminder(int $agentId, int $inquiryId, array $data): array
    {
        // Verify agent owns the inquiry
        $inquiry = PropertyInquiry::where('id', $inquiryId)
            ->where('agent_id', $agentId)
            ->first();

        if (!$inquiry) {
            return [
                'ok'    => false,
                'error' => 'Inquiry not found or not assigned to you.',
                'code'  => 404,
            ];
        }

        // remind_at must be in the future
        $remindAt = Carbon::parse($data['remind_at']);
        if ($remindAt->isPast()) {
            return [
                'ok'    => false,
                'error' => 'remind_at must be a future date/time.',
                'code'  => 422,
            ];
        }

        // Enforce active reminder cap
        $activeCount = Reminder::where('inquiry_id', $inquiryId)
            ->where('is_completed', false)
            ->count();

        if ($activeCount >= self::MAX_REMINDERS_PER_INQUIRY) {
            return [
                'ok'    => false,
                'error' => 'This inquiry already has the maximum of ' . self::MAX_REMINDERS_PER_INQUIRY . ' active reminders. Complete or delete one before adding another.',
                'code'  => 422,
            ];
        }

        $reminder = Reminder::create([
            'agent_id'   => $agentId,
            'inquiry_id' => $inquiryId,
            'remind_at'  => $remindAt,
            'message'    => isset($data['message']) ? mb_substr(trim($data['message']), 0, 500) : null,
        ]);

        return ['ok' => true, 'reminder' => $reminder->load('inquiry.property')];
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /**
     * Update reminder time and/or message (if not yet completed).
     *
     * @param Reminder $reminder
     * @param array    $data  Keys: remind_at (optional), message (optional)
     * @return array{ok: bool, reminder?: Reminder, error?: string, code?: int}
     */
    public function updateReminder(Reminder $reminder, array $data): array
    {
        if ($reminder->is_completed) {
            return [
                'ok'    => false,
                'error' => 'Cannot update a completed reminder.',
                'code'  => 422,
            ];
        }

        $updates = [];

        if (isset($data['remind_at'])) {
            $remindAt = Carbon::parse($data['remind_at']);
            if ($remindAt->isPast()) {
                return [
                    'ok'    => false,
                    'error' => 'remind_at must be a future date/time.',
                    'code'  => 422,
                ];
            }
            $updates['remind_at'] = $remindAt;
        }

        if (array_key_exists('message', $data)) {
            $updates['message'] = $data['message'] !== null
                ? mb_substr(trim($data['message']), 0, 500)
                : null;
        }

        if (!empty($updates)) {
            $reminder->update($updates);
        }

        return ['ok' => true, 'reminder' => $reminder->fresh()];
    }

    /**
     * Snooze a reminder by one of the allowed offsets.
     *
     * @param Reminder $reminder
     * @param string   $snooze  One of: '+1 hour', '+4 hours', '+1 day'
     * @return array{ok: bool, reminder?: Reminder, error?: string, code?: int}
     */
    public function snoozeReminder(Reminder $reminder, string $snooze): array
    {
        if ($reminder->is_completed) {
            return [
                'ok'    => false,
                'error' => 'Cannot snooze a completed reminder.',
                'code'  => 422,
            ];
        }

        if (!in_array($snooze, self::SNOOZE_OPTIONS, true)) {
            return [
                'ok'    => false,
                'error' => 'Invalid snooze value. Allowed: ' . implode(', ', self::SNOOZE_OPTIONS),
                'code'  => 422,
            ];
        }

        $reminder->update(['remind_at' => Carbon::now()->modify($snooze)]);

        return ['ok' => true, 'reminder' => $reminder->fresh()];
    }

    /**
     * Mark a reminder as completed.
     *
     * @param Reminder $reminder
     * @return array{ok: bool, reminder?: Reminder, error?: string, code?: int}
     */
    public function completeReminder(Reminder $reminder): array
    {
        if ($reminder->is_completed) {
            return [
                'ok'    => false,
                'error' => 'Reminder is already completed.',
                'code'  => 422,
            ];
        }

        $reminder->markCompleted();

        return ['ok' => true, 'reminder' => $reminder->fresh()];
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /**
     * Delete a reminder (hard delete — no soft delete needed for reminders).
     */
    public function deleteReminder(Reminder $reminder): bool
    {
        return (bool) $reminder->delete();
    }

    // =========================================================================
    // SLA SUMMARY
    // =========================================================================

    /**
     * SLA summary for an agent's current open inquiries.
     *
     * Returns counts per SLA status plus the 5 most urgent (oldest uncontacted).
     *
     * @param int $agentId
     * @return array
     */
    public function getSlaSummary(int $agentId): array
    {
        $thresholds = config('realestate.sla_thresholds', [
            'urgent'  => PropertyInquiry::SLA_URGENT_HOURS,
            'warning' => PropertyInquiry::SLA_WARNING_HOURS,
            'breach'  => PropertyInquiry::SLA_BREACH_HOURS,
        ]);

        $urgentHours  = $thresholds['urgent'];
        $warningHours = $thresholds['warning'];

        // Open = not yet closed/converted
        $base = PropertyInquiry::where('agent_id', $agentId)
            ->whereNull('contacted_at')
            ->whereNotIn('status', [
                PropertyInquiry::STATUS_CONVERTED,
                PropertyInquiry::STATUS_CLOSED,
            ]);

        $onTrackCount = (clone $base)
            ->where('created_at', '>=', Carbon::now()->subHours($urgentHours))
            ->count();

        $warningCount = (clone $base)
            ->where('created_at', '<', Carbon::now()->subHours($urgentHours))
            ->where('created_at', '>=', Carbon::now()->subHours($warningHours))
            ->count();

        $breachedCount = (clone $base)
            ->where('created_at', '<', Carbon::now()->subHours($warningHours))
            ->count();

        // Top 5 most urgent (oldest uncontacted open inquiries)
        $topBreached = (clone $base)
            ->with(['property:id,title', 'compound:id,name'])
            ->where('created_at', '<', Carbon::now()->subHours($urgentHours))
            ->oldest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PropertyInquiry $inq) => [
                'id'            => $inq->id,
                'name'          => $inq->name,
                'status'        => $inq->status,
                'sla_status'    => $inq->sla_status,
                'hours_elapsed' => $inq->sla_hours_elapsed,
                'property'      => $inq->property ? ['id' => $inq->property->id, 'title' => $inq->property->title] : null,
                'compound'      => $inq->compound ? ['id' => $inq->compound->id, 'name' => $inq->compound->name] : null,
            ]);

        return [
            'summary' => [
                'on_track' => $onTrackCount,
                'warning'  => $warningCount,
                'breached' => $breachedCount,
                'total_open' => $onTrackCount + $warningCount + $breachedCount,
            ],
            'thresholds_hours' => [
                'urgent'  => $urgentHours,
                'warning' => $warningHours,
                'breach'  => $thresholds['breach'],
            ],
            'top_urgent' => $topBreached,
        ];
    }

    // =========================================================================
    // SCHEDULER SUPPORT
    // =========================================================================

    /**
     * Collect all due reminders with their agents eager-loaded.
     * Called by the SendDueReminders console command.
     *
     * @return Collection<Reminder>
     */
    public function getDueReminders(): Collection
    {
        return Reminder::due()
            ->with(['agent', 'inquiry.property', 'inquiry.compound'])
            ->get();
    }

    /**
     * Dispatch a notification to the agent and mark the reminder as completed.
     * Called once per due reminder by the scheduler command.
     */
    public function dispatchAndComplete(Reminder $reminder): void
    {
        $agent = $reminder->agent;

        if ($agent) {
            $agent->notify(new ReminderDueNotification($reminder));
        }

        $reminder->markCompleted();
    }
}
