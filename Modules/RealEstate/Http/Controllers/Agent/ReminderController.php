<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Agent;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Reminder;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\ReminderService;
use OpenApi\Attributes as OA;

/**
 * Agent Reminder Controller
 *
 * Endpoints for F2.1: Follow-up Reminders & SLA Timers.
 *
 * All routes are under TIER 4 agent middleware:
 *   auth:api_tenant_user, tenancy.token, tenant.context
 *
 * Endpoints:
 *   POST   /agent/realestate/inquiries/{id}/reminders        → createForInquiry
 *   GET    /agent/realestate/reminders                        → index
 *   PATCH  /agent/realestate/reminders/{id}                  → update
 *   DELETE /agent/realestate/reminders/{id}                  → destroy
 *   GET    /agent/realestate/sla/summary                     → slaSummary
 */
#[OA\Tag(name: 'Agent Reminders & SLA', description: 'Follow-up reminder management and SLA monitoring for agents')]
class ReminderController extends BaseController
{
    public function __construct(
        protected ReminderService $reminderService
    ) {}

    // =========================================================================
    // GET /agent/realestate/reminders
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/reminders',
        summary: 'List agent reminders',
        description: 'Returns paginated list of the agent\'s follow-up reminders. Filter by status (pending, overdue, completed).',
        tags: ['Agent Reminders & SLA'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'pending|overdue|completed', schema: new OA\Schema(type: 'string', enum: ['pending', 'overdue', 'completed'], default: 'pending')),
            new OA\Parameter(name: 'inquiry_id', in: 'query', description: 'Filter by specific inquiry', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', description: 'Start date filter (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', description: 'End date filter (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reminder list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $agentId   = auth()->id();
        $paginator = $this->reminderService->getAgentReminders($agentId, $request->only([
            'status', 'inquiry_id', 'from', 'to', 'per_page',
        ]));

        $items = $paginator->getCollection()->map(fn (Reminder $r) => $this->formatReminder($r));

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    // =========================================================================
    // POST /agent/realestate/inquiries/{id}/reminders
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/reminders',
        summary: 'Create a reminder for an inquiry',
        description: 'Schedules a follow-up reminder for the agent. Max 5 active reminders per inquiry.',
        tags: ['Agent Reminders & SLA'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['remind_at'],
                properties: [
                    new OA\Property(property: 'remind_at', type: 'string', format: 'date-time', description: 'When to send the reminder (must be in the future)', example: '2026-03-01 10:00:00'),
                    new OA\Property(property: 'message', type: 'string', maxLength: 500, description: 'Optional context note', example: 'Client requested afternoon call'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Reminder created'),
            new OA\Response(response: 404, description: 'Inquiry not found or not assigned to you'),
            new OA\Response(response: 422, description: 'Validation error / cap exceeded'),
        ]
    )]
    public function createForInquiry(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'remind_at' => ['required', 'date', 'after:now'],
            'message'   => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->reminderService->createReminder(auth()->id(), $id, $validated);

        if (!$result['ok']) {
            return $this->error($result['error'], code: $result['code']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reminder created.',
            'data'    => $this->formatReminder($result['reminder']),
        ], 201);
    }

    // =========================================================================
    // PATCH /agent/realestate/reminders/{id}
    // =========================================================================

    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/agent/realestate/reminders/{id}',
        summary: 'Update or snooze a reminder',
        description: 'Update remind_at and/or message. Use "action: snooze" with "snooze" field to snooze. Use "action: complete" to mark done.',
        tags: ['Agent Reminders & SLA'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'action', type: 'string', enum: ['update', 'snooze', 'complete'], default: 'update'),
                    new OA\Property(property: 'remind_at', type: 'string', format: 'date-time', description: 'New reminder time (for action=update)'),
                    new OA\Property(property: 'message', type: 'string', maxLength: 500),
                    new OA\Property(property: 'snooze', type: 'string', enum: ['+1 hour', '+4 hours', '+1 day'], description: 'Snooze offset (for action=snooze)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reminder updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $reminder = $this->reminderService->findOwned($id, auth()->id());

        if (!$reminder) {
            return $this->notFound('Reminder not found.');
        }

        $validated = $request->validate([
            'action'    => ['nullable', 'string', 'in:update,snooze,complete'],
            'remind_at' => ['nullable', 'date', 'after:now'],
            'message'   => ['nullable', 'string', 'max:500'],
            'snooze'    => ['nullable', 'string', 'in:+1 hour,+4 hours,+1 day'],
        ]);

        $action = $validated['action'] ?? 'update';

        if ($action === 'complete') {
            $result = $this->reminderService->completeReminder($reminder);
        } elseif ($action === 'snooze') {
            $snooze = $validated['snooze'] ?? null;
            if (!$snooze) {
                return $this->error('snooze field is required when action is snooze.', code: 422);
            }
            $result = $this->reminderService->snoozeReminder($reminder, $snooze);
        } else {
            $result = $this->reminderService->updateReminder($reminder, $validated);
        }

        if (!$result['ok']) {
            return $this->error($result['error'], code: $result['code']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reminder updated.',
            'data'    => $this->formatReminder($result['reminder']),
        ]);
    }

    // =========================================================================
    // DELETE /agent/realestate/reminders/{id}
    // =========================================================================

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/agent/realestate/reminders/{id}',
        summary: 'Delete a reminder',
        description: 'Permanently deletes the reminder. Only the owning agent can delete.',
        tags: ['Agent Reminders & SLA'],
        responses: [
            new OA\Response(response: 200, description: 'Reminder deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $reminder = $this->reminderService->findOwned($id, auth()->id());

        if (!$reminder) {
            return $this->notFound('Reminder not found.');
        }

        $this->reminderService->deleteReminder($reminder);

        return response()->json([
            'success' => true,
            'message' => 'Reminder deleted.',
        ]);
    }

    // =========================================================================
    // GET /agent/realestate/sla/summary
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/sla/summary',
        summary: 'Get SLA summary for agent',
        description: 'Returns on_track / warning / breached counts for the agent\'s open inquiries, plus top 5 most urgent leads.',
        tags: ['Agent Reminders & SLA'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SLA summary',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'summary', type: 'object', properties: [
                            new OA\Property(property: 'on_track', type: 'integer'),
                            new OA\Property(property: 'warning', type: 'integer'),
                            new OA\Property(property: 'breached', type: 'integer'),
                            new OA\Property(property: 'total_open', type: 'integer'),
                        ]),
                        new OA\Property(property: 'thresholds_hours', type: 'object'),
                        new OA\Property(property: 'top_urgent', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function slaSummary(): JsonResponse
    {
        $summary = $this->reminderService->getSlaSummary(auth()->id());

        return response()->json([
            'success' => true,
            'data'    => $summary,
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Consistent reminder payload for API responses.
     */
    private function formatReminder(Reminder $reminder): array
    {
        $inquiry = $reminder->relationLoaded('inquiry') ? $reminder->inquiry : null;

        return [
            'id'           => $reminder->id,
            'inquiry_id'   => $reminder->inquiry_id,
            'remind_at'    => $reminder->remind_at?->toISOString(),
            'message'      => $reminder->message,
            'is_completed' => $reminder->is_completed,
            'completed_at' => $reminder->completed_at?->toISOString(),
            'status'       => $reminder->status_label,
            'is_overdue'   => $reminder->is_overdue,
            'created_at'   => $reminder->created_at?->toISOString(),
            'inquiry'      => $inquiry ? [
                'id'         => $inquiry->id,
                'name'       => $inquiry->name,
                'phone'      => $inquiry->phone,
                'status'     => $inquiry->status,
                'sla_status' => $inquiry->sla_status,
                'property'   => $inquiry->relationLoaded('property') && $inquiry->property
                    ? ['id' => $inquiry->property->id, 'title' => $inquiry->property->title]
                    : null,
                'compound'   => $inquiry->relationLoaded('compound') && $inquiry->compound
                    ? ['id' => $inquiry->compound->id, 'name' => $inquiry->compound->name]
                    : null,
            ] : null,
        ];
    }
}
