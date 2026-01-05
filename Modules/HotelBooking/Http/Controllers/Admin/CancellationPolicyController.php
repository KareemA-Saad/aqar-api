<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\StoreCancellationPolicyRequest;
use Modules\HotelBooking\Http\Resources\CancellationPolicyResource;
use Modules\HotelBooking\Services\CancellationPolicyService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Cancellation Policy Management', description: 'Cancellation policy management endpoints')]
class CancellationPolicyController extends Controller
{
    protected CancellationPolicyService $policyService;

    public function __construct(CancellationPolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    #[OA\Get(
        path: '/api/v1/admin/cancellation-policies',
        summary: 'List all cancellation policies',
        description: 'Get paginated list of all cancellation policies',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Policies list retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $policies = $this->policyService->getPolicies($request->all());

        return response()->json([
            'success' => true,
            'data' => CancellationPolicyResource::collection($policies),
            'meta' => [
                'current_page' => $policies->currentPage(),
                'last_page' => $policies->lastPage(),
                'per_page' => $policies->perPage(),
                'total' => $policies->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cancellation-policies',
        summary: 'Create cancellation policy',
        description: 'Create a new cancellation policy with tiers',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCancellationPolicyRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Policy created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreCancellationPolicyRequest $request): JsonResponse
    {
        $policy = $this->policyService->createPolicy($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Cancellation policy created successfully.'),
            'data' => new CancellationPolicyResource($policy),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/cancellation-policies/{id}',
        summary: 'Get policy details',
        description: 'Get detailed information about a specific cancellation policy',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Policy details retrieved'),
            new OA\Response(response: 404, description: 'Policy not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $policy = $this->policyService->getPolicy($id);

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => __('Cancellation policy not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CancellationPolicyResource($policy),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cancellation-policies/{id}',
        summary: 'Update policy',
        description: 'Update an existing cancellation policy',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCancellationPolicyRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Policy updated successfully'),
            new OA\Response(response: 404, description: 'Policy not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(StoreCancellationPolicyRequest $request, int $id): JsonResponse
    {
        $policy = $this->policyService->updatePolicy($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Cancellation policy updated successfully.'),
            'data' => new CancellationPolicyResource($policy),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/cancellation-policies/{id}',
        summary: 'Delete policy',
        description: 'Delete a cancellation policy (if not in use)',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Policy deleted successfully'),
            new OA\Response(response: 400, description: 'Cannot delete policy in use'),
            new OA\Response(response: 404, description: 'Policy not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->policyService->deletePolicy($id);

            return response()->json([
                'success' => true,
                'message' => __('Cancellation policy deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Patch(
        path: '/api/v1/admin/cancellation-policies/{id}/toggle-status',
        summary: 'Toggle policy status',
        description: 'Toggle the active/inactive status of a policy',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Policy status toggled'),
            new OA\Response(response: 404, description: 'Policy not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $policy = $this->policyService->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => __('Policy status updated successfully.'),
            'data' => [
                'id' => $policy->id,
                'is_active' => $policy->is_active,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cancellation-policies/{id}/clone',
        summary: 'Clone policy',
        description: 'Create a copy of an existing policy',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Policy cloned successfully'),
            new OA\Response(response: 404, description: 'Policy not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function clone(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:cancellation_policies,name',
        ]);

        $policy = $this->policyService->clonePolicy($id, $request->name);

        return response()->json([
            'success' => true,
            'message' => __('Cancellation policy cloned successfully.'),
            'data' => new CancellationPolicyResource($policy),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/cancellation-policies/{id}/usage',
        summary: 'Get policy usage',
        description: 'Get usage statistics for a policy',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usage statistics retrieved'),
            new OA\Response(response: 404, description: 'Policy not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function usage(int $id): JsonResponse
    {
        $stats = $this->policyService->getPolicyUsageStats($id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/cancellation-policies/active',
        summary: 'Get active policies',
        description: 'Get all active cancellation policies',
        tags: ['Admin Cancellation Policy Management'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Active policies retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function active(): JsonResponse
    {
        $policies = $this->policyService->getActivePolicies();

        return response()->json([
            'success' => true,
            'data' => CancellationPolicyResource::collection($policies),
        ]);
    }
}
