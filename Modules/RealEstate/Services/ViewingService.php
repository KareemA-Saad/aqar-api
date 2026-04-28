<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\RealEstate\Entities\Viewing;
use Modules\RealEstate\Notifications\ViewingAppointmentReminderNotification;

/**
 * ViewingService — F2.5 Viewing Scheduler
 *
 * Handles booking, status transitions, daily schedule, and completion.
 */
class ViewingService
{
    // =========================================================================
    // Booking (user-facing)
    // =========================================================================

    /**
     * Book a new viewing (from user or admin).
     */
    public function book(array $data): Viewing
    {
        return Viewing::create([
            'property_id'   => $data['property_id'] ?? null,
            'compound_id'   => $data['compound_id'] ?? null,
            'agent_id'      => $data['agent_id'] ?? null,
            'user_id'       => $data['user_id'] ?? null,
            'contact_name'  => $data['contact_name'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'scheduled_at'  => $data['scheduled_at'],
            'notes'         => $data['notes'] ?? null,
            'status'        => Viewing::STATUS_PENDING,
        ]);
    }

    // =========================================================================
    // Agent list (F2.5 endpoint 1)
    // =========================================================================

    /**
     * Paginated list of viewings for a specific agent, with filters.
     *
     * Filters: status, date_from, date_to, property_id
     */
    public function getForAgent(int $agentId, array $filters = []): LengthAwarePaginator
    {
        $query = Viewing::forAgent($agentId)
            ->with(['property', 'compound'])
            ->orderBy('scheduled_at', 'asc');

        if (!empty($filters['status']) && in_array($filters['status'], Viewing::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('scheduled_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('scheduled_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (!empty($filters['property_id'])) {
            $query->where('property_id', (int) $filters['property_id']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    // =========================================================================
    // Today's schedule (F2.5 endpoint 2)
    // =========================================================================

    /**
     * Today's pending + confirmed viewings for an agent, ordered by time.
     */
    public function getTodayForAgent(int $agentId): Collection
    {
        return Viewing::forAgent($agentId)
            ->today()
            ->active()
            ->with(['property', 'compound'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    // =========================================================================
    // Status update (F2.5 endpoint 3 — PATCH)
    // =========================================================================

    /**
     * Update viewing status (confirm / decline / reschedule).
     *
     * For rescheduling, pass `rescheduled_to` with the new datetime.
     *
     * @throws \InvalidArgumentException on invalid transition
     */
    public function updateStatus(Viewing $viewing, int $agentId, array $data): Viewing
    {
        $this->assertAgentOwns($viewing, $agentId);

        $newStatus = $data['status'];
        $this->assertValidTransition($viewing->status, $newStatus);

        $viewing->status = $newStatus;

        if ($newStatus === Viewing::STATUS_RESCHEDULED) {
            $viewing->rescheduled_to   = Carbon::parse($data['rescheduled_to']);
            $viewing->reschedule_count = $viewing->reschedule_count + 1;
        }

        if (isset($data['notes'])) {
            $viewing->notes = $data['notes'];
        }

        $viewing->save();

        // Send reminder notification to agent when confirmed
        if ($newStatus === Viewing::STATUS_CONFIRMED && $viewing->agent_id) {
            $this->sendConfirmationReminder($viewing);
        }

        return $viewing->refresh();
    }

    // =========================================================================
    // Complete / No-show (F2.5 endpoint 4 — POST complete)
    // =========================================================================

    /**
     * Mark a viewing as completed or no_show.
     */
    public function complete(Viewing $viewing, int $agentId, string $outcome, ?string $resultNotes): Viewing
    {
        $this->assertAgentOwns($viewing, $agentId);

        if (!in_array($outcome, [Viewing::STATUS_COMPLETED, Viewing::STATUS_NO_SHOW], true)) {
            throw new \InvalidArgumentException("Outcome must be 'completed' or 'no_show'.");
        }

        $viewing->status       = $outcome;
        $viewing->result_notes = $resultNotes;
        $viewing->save();

        return $viewing->refresh();
    }

    // =========================================================================
    // Admin list
    // =========================================================================

    public function getPaginatedAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = Viewing::with(['property', 'compound', 'agent'])
            ->orderBy('scheduled_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['agent_id'])) {
            $query->where('agent_id', (int) $filters['agent_id']);
        }

        if (!empty($filters['property_id'])) {
            $query->where('property_id', (int) $filters['property_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('scheduled_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('scheduled_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $query->paginate($perPage);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function assertAgentOwns(Viewing $viewing, int $agentId): void
    {
        if ($viewing->agent_id !== null && $viewing->agent_id !== $agentId) {
            throw new \RuntimeException('This viewing is not assigned to you.');
        }
    }

    /**
     * Allowed status transitions: [from => [allowed to]]
     */
    private function assertValidTransition(string $from, string $to): void
    {
        $allowed = [
            Viewing::STATUS_PENDING    => [Viewing::STATUS_CONFIRMED, Viewing::STATUS_DECLINED, Viewing::STATUS_RESCHEDULED],
            Viewing::STATUS_CONFIRMED  => [Viewing::STATUS_DECLINED, Viewing::STATUS_RESCHEDULED],
            Viewing::STATUS_RESCHEDULED => [Viewing::STATUS_CONFIRMED, Viewing::STATUS_DECLINED],
        ];

        if (!isset($allowed[$from]) || !in_array($to, $allowed[$from], true)) {
            throw new \InvalidArgumentException(
                "Cannot transition viewing from '{$from}' to '{$to}'."
            );
        }
    }

    /**
     * Send a reminder notification to the assigned agent upon confirmation.
     */
    private function sendConfirmationReminder(Viewing $viewing): void
    {
        if (!$viewing->agent) {
            $viewing->load('agent');
        }

        if (!$viewing->agent) {
            return;
        }

        $viewing->agent->notify(new ViewingAppointmentReminderNotification([
            'appointment_date' => $viewing->scheduled_at->toDateTimeString(),
            'property_title'   => $viewing->property?->title ?? ($viewing->compound?->name ?? 'Property Viewing'),
            'customer_name'    => $viewing->contact_name,
            'customer_phone'   => $viewing->contact_phone,
            'customer_email'   => $viewing->contact_email,
            'address'          => null,
            'notes'            => $viewing->notes,
        ]));
    }
}
