<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\PricePlan\ReorderFeaturesRequest;
use App\Http\Requests\PricePlan\StorePricePlanRequest;
use App\Http\Requests\PricePlan\UpdatePricePlanRequest;
use App\Http\Resources\PricePlanCollection;
use App\Http\Resources\PricePlanResource;
use App\Services\PricePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin Price Plan Controller
 *
 * Handles price plan management operations by administrators.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Price Plan Management (Admin)',
    description: 'Admin endpoints to manage price plans and features (Guard: api_admin)'
)]
final class PricePlanController extends BaseApiController
{
    public function __construct(
        private readonly PricePlanService $pricePlanService,
    ) {}

    /**
     * Get paginated list of all price plans.
     */
    #[OA\Get(
        path: '/api/v1/admin/price-plans',
        summary: 'List all price plans',
        description: 'Get paginated list of all price plans with features (admin view)',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search by title or subtitle',
        schema: new OA\Schema(type: 'string', example: 'premium')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Filter by status (true/false)',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'type',
        in: 'query',
        required: false,
        description: 'Filter by type (0=Monthly, 1=Yearly, 2=Lifetime)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1, 2])
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plans retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plans retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PricePlanResource')
                ),
                new OA\Property(property: 'pagination', type: 'object'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->has('status') ? $request->boolean('status') : null,
            'type' => $request->has('type') ? (int) $request->query('type') : null,
            'per_page' => min((int) $request->query('per_page', $this->perPage), 100),
        ];

        // Remove null values
        $filters = array_filter($filters, fn ($v) => $v !== null);

        $plans = $this->pricePlanService->getPlansForAdmin($filters);

        return $this->paginated(
            $plans,
            PricePlanResource::class,
            'Price plans retrieved successfully'
        );
    }

    /**
     * Create a new price plan.
     */
    #[OA\Post(
        path: '/api/v1/admin/price-plans',
        summary: 'Create price plan',
        description: 'Create a new price plan with features',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StorePricePlanRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Price plan created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function store(StorePricePlanRequest $request): JsonResponse
    {
        try {
            $plan = $this->pricePlanService->createPlan($request->validatedData());

            return $this->created(
                new PricePlanResource($plan),
                'Price plan created successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create price plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single price plan.
     */
    #[OA\Get(
        path: '/api/v1/admin/price-plans/{id}',
        summary: 'Get price plan details',
        description: 'Retrieve details of a specific price plan with all features',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    public function show(int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        return $this->success(
            new PricePlanResource($plan),
            'Price plan retrieved successfully'
        );
    }

    /**
     * Update a price plan.
     */
    #[OA\Put(
        path: '/api/v1/admin/price-plans/{id}',
        summary: 'Update price plan',
        description: 'Update an existing price plan',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdatePricePlanRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function update(UpdatePricePlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $updatedPlan = $this->pricePlanService->updatePlan($plan, $request->validatedData());

            return $this->success(
                new PricePlanResource($updatedPlan),
                'Price plan updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update price plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a price plan.
     */
    #[OA\Delete(
        path: '/api/v1/admin/price-plans/{id}',
        summary: 'Delete price plan',
        description: 'Delete a price plan and all its features',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan deleted successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    public function destroy(int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $this->pricePlanService->deletePlan($plan);

            return $this->success(null, 'Price plan deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete price plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle price plan status.
     */
    #[OA\Patch(
        path: '/api/v1/admin/price-plans/{id}/toggle-status',
        summary: 'Toggle plan status',
        description: 'Toggle the active/inactive status of a price plan',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Price plan status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan status updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $updatedPlan = $this->pricePlanService->toggleStatus($plan);

            return $this->success(
                new PricePlanResource($updatedPlan),
                'Price plan status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to toggle price plan status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reorder plan features.
     */
    #[OA\Patch(
        path: '/api/v1/admin/price-plans/{id}/reorder-features',
        summary: 'Reorder plan features',
        description: 'Reorder the features of a price plan',
        security: [['sanctum_admin' => []]],
        tags: ['Price Plan Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Price Plan ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ReorderFeaturesRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Features reordered successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Features reordered successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Price plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Price plan not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function reorderFeatures(ReorderFeaturesRequest $request, int $id): JsonResponse
    {
        $plan = $this->pricePlanService->getPlanById($id);

        if ($plan === null) {
            return $this->notFound('Price plan not found');
        }

        try {
            $updatedPlan = $this->pricePlanService->reorderFeatures($plan, $request->getFeatureIds());

            return $this->success(
                new PricePlanResource($updatedPlan),
                'Features reordered successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to reorder features: ' . $e->getMessage(), 500);
        }
    }
}
