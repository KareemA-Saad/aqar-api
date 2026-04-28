<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Agent;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Viewing;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\ViewingService;
use OpenApi\Attributes as OA;

/**
 * Agent Viewing Controller — F2.5 Viewing Scheduler
 *
 * All routes are under TIER 4 agent middleware.
 *
 * Endpoints:
 *   GET   /agent/realestate/viewings            → list (with filters)
 *   GET   /agent/realestate/viewings/today      → today's schedule
 *   PATCH /agent/realestate/viewings/{id}        → confirm / decline / reschedule
 *   POST  /agent/realestate/viewings/{id}/complete → mark completed or no_show
 */
#[OA\Tag(name: 'Agent - Viewings', description: 'Viewing management for agents')]
class ViewingController extends BaseController
{
    public function __construct(
        protected ViewingService $viewingService
    ) {}

    // =========================================================================
    // GET /agent/realestate/viewings  (F2.5 endpoint 1)
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/viewings',
        summary: 'List agent viewings',
        description: 'Returns paginated viewings assigned to the authenticated agent. Filter by status, date range, or property.',
        tags: ['Agent - Viewings'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'declined', 'rescheduled', 'completed', 'no_show'])),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'property_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated viewing list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $agentId   = auth()->id();
        $paginator = $this->viewingService->getForAgent($agentId, $request->only([
            'status', 'date_from', 'date_to', 'property_id', 'per_page',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    // =========================================================================
    // GET /agent/realestate/viewings/today  (F2.5 endpoint 2)
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/viewings/today',
        summary: "Today's viewing schedule",
        description: 'Returns today\'s pending and confirmed viewings for the agent, sorted by time.',
        tags: ['Agent - Viewings'],
        responses: [
            new OA\Response(response: 200, description: "Today's viewings"),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function today(): JsonResponse
    {
        $agentId  = auth()->id();
        $viewings = $this->viewingService->getTodayForAgent($agentId);

        return response()->json([
            'success' => true,
            'count'   => $viewings->count(),
            'data'    => $viewings,
        ]);
    }

    // =========================================================================
    // PATCH /agent/realestate/viewings/{id}  (F2.5 endpoint 3)
    // =========================================================================

    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/agent/realestate/viewings/{id}',
        summary: 'Update viewing status',
        description: 'Agent can confirm, decline, or reschedule a viewing. For rescheduling, provide `rescheduled_to`.',
        tags: ['Agent - Viewings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['confirmed', 'declined', 'rescheduled'], example: 'confirmed'),
                    new OA\Property(property: 'rescheduled_to', type: 'string', format: 'date-time', description: 'Required when status=rescheduled', example: '2026-04-20 10:00:00'),
                    new OA\Property(property: 'notes', type: 'string', description: 'Optional internal note', example: 'Client prefers morning.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Viewing status updated'),
            new OA\Response(response: 400, description: 'Invalid status transition'),
            new OA\Response(response: 403, description: 'Viewing not assigned to you'),
            new OA\Response(response: 404, description: 'Viewing not found'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status'         => 'required|in:confirmed,declined,rescheduled',
            'rescheduled_to' => 'required_if:status,rescheduled|nullable|date|after:now',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $viewing = Viewing::find($id);

        if (!$viewing) {
            return $this->notFound('Viewing not found.');
        }

        try {
            $viewing = $this->viewingService->updateStatus($viewing, auth()->id(), $data);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'data' => $viewing]);
    }

    // =========================================================================
    // POST /agent/realestate/viewings/{id}/complete  (F2.5 endpoint 4)
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/agent/realestate/viewings/{id}/complete',
        summary: 'Mark viewing as completed or no-show',
        description: 'Finalises a confirmed viewing. Set outcome to `completed` or `no_show`.',
        tags: ['Agent - Viewings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['outcome'],
                properties: [
                    new OA\Property(property: 'outcome', type: 'string', enum: ['completed', 'no_show'], example: 'completed'),
                    new OA\Property(property: 'result_notes', type: 'string', description: 'Notes about the viewing outcome', example: 'Client very interested in unit 4B.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Viewing finalised'),
            new OA\Response(response: 400, description: 'Invalid outcome'),
            new OA\Response(response: 403, description: 'Viewing not assigned to you'),
            new OA\Response(response: 404, description: 'Viewing not found'),
        ]
    )]
    public function complete(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'outcome'      => 'required|in:completed,no_show',
            'result_notes' => 'nullable|string|max:2000',
        ]);

        $viewing = Viewing::find($id);

        if (!$viewing) {
            return $this->notFound('Viewing not found.');
        }

        try {
            $viewing = $this->viewingService->complete(
                $viewing,
                auth()->id(),
                $data['outcome'],
                $data['result_notes'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Viewing marked as ' . $data['outcome'] . '.',
            'data'    => $viewing,
        ]);
    }
}
