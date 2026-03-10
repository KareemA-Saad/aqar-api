<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Viewing;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\ViewingService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Viewings', description: 'Viewing appointment management for admins')]
class ViewingController extends BaseController
{
    public function __construct(
        protected ViewingService $viewingService
    ) {}

    // =========================================================================
    // GET /admin/realestate/viewings
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/viewings',
        summary: 'List all viewings',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Viewings'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'declined', 'rescheduled', 'completed', 'no_show'])),
            new OA\Parameter(name: 'agent_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'property_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated viewing list'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->viewingService->getPaginatedAdmin($request->only([
            'status', 'agent_id', 'property_id', 'date_from', 'date_to', 'per_page',
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
    // GET /admin/realestate/viewings/{id}
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/viewings/{id}',
        summary: 'Get a single viewing',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Viewings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Viewing details'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $viewing = Viewing::with(['property', 'compound', 'agent', 'user'])->find($id);

        if (!$viewing) {
            return $this->notFound('Viewing not found.');
        }

        return response()->json(['success' => true, 'data' => $viewing]);
    }

    // =========================================================================
    // PATCH /admin/realestate/viewings/{id}
    // =========================================================================

    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/admin/realestate/viewings/{id}',
        summary: 'Update viewing (admin override)',
        description: 'Admin can update status or re-assign agent.',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Viewings'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'declined', 'rescheduled', 'completed', 'no_show']),
                    new OA\Property(property: 'agent_id', type: 'integer'),
                    new OA\Property(property: 'notes', type: 'string'),
                    new OA\Property(property: 'rescheduled_to', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Viewing updated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $viewing = Viewing::find($id);

        if (!$viewing) {
            return $this->notFound('Viewing not found.');
        }

        $data = $request->validate([
            'status'         => 'sometimes|in:' . implode(',', Viewing::STATUSES),
            'agent_id'       => 'sometimes|nullable|integer',
            'notes'          => 'sometimes|nullable|string|max:1000',
            'rescheduled_to' => 'sometimes|nullable|date',
        ]);

        // Admin direct update (bypasses agent-ownership check)
        if (isset($data['status'])) {
            $viewing->status = $data['status'];

            if ($data['status'] === Viewing::STATUS_RESCHEDULED && isset($data['rescheduled_to'])) {
                $viewing->rescheduled_to   = $data['rescheduled_to'];
                $viewing->reschedule_count = $viewing->reschedule_count + 1;
            }
        }

        if (array_key_exists('agent_id', $data)) {
            $viewing->agent_id = $data['agent_id'];
        }

        if (array_key_exists('notes', $data)) {
            $viewing->notes = $data['notes'];
        }

        $viewing->save();

        return response()->json(['success' => true, 'data' => $viewing->refresh()]);
    }
}
