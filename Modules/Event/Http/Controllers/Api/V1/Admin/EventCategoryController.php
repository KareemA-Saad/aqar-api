<?php

declare(strict_types=1);

namespace Modules\Event\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Event\Entities\EventCategory;
use Modules\Event\Http\Requests\EventCategoryRequest;
use Modules\Event\Http\Resources\EventCategoryResource;
use Modules\Event\Services\EventCategoryService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Event Category Controller
 */
#[OA\Tag(
    name: 'Tenant Admin - Event Categories',
    description: 'Manage event categories within a tenant'
)]
final class EventCategoryController extends BaseApiController
{
    public function __construct(
        private readonly EventCategoryService $categoryService,
    ) {}

    /**
     * List all event categories.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-categories',
        summary: 'List event categories',
        description: 'Get paginated list of event categories',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Categories']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1]))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100))]
    #[OA\Response(response: 200, description: 'Categories retrieved successfully')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $categories = $this->categoryService->getCategories($filters, $perPage);

        return $this->paginated($categories, EventCategoryResource::class, 'Event categories retrieved successfully');
    }

    /**
     * Get all active categories (no pagination).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-categories/active',
        summary: 'Get active categories',
        description: 'Get all active event categories without pagination',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Categories']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Active categories retrieved successfully')]
    public function active(): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategories();

        return $this->success(
            EventCategoryResource::collection($categories),
            'Active event categories retrieved successfully'
        );
    }

    /**
     * Create a new event category.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/event-categories',
        summary: 'Create event category',
        description: 'Create a new event category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Categories']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EventCategoryRequest'))]
    #[OA\Response(response: 201, description: 'Category created successfully')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(EventCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return $this->success(
            new EventCategoryResource($category),
            'Event category created successfully',
            201
        );
    }

    /**
     * Get a specific event category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-categories/{id}',
        summary: 'Get event category',
        description: 'Get a specific event category by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Categories']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Category retrieved successfully')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function show(int $id): JsonResponse
    {
        $category = EventCategory::withCount('events')->find($id);

        if (!$category) {
            return $this->error('Event category not found', 404);
        }

        return $this->success(
            new EventCategoryResource($category),
            'Event category retrieved successfully'
        );
    }

    /**
     * Update an event category.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/event-categories/{id}',
        summary: 'Update event category',
        description: 'Update an existing event category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Categories']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EventCategoryRequest'))]
    #[OA\Response(response: 200, description: 'Category updated successfully')]
    #[OA\Response(response: 404, description: 'Category not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(EventCategoryRequest $request, int $id): JsonResponse
    {
        $category = EventCategory::find($id);

        if (!$category) {
            return $this->error('Event category not found', 404);
        }

        $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());

        return $this->success(
            new EventCategoryResource($updatedCategory),
            'Event category updated successfully'
        );
    }

    /**
     * Delete an event category.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/event-categories/{id}',
        summary: 'Delete event category',
        description: 'Delete an event category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Categories']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Category deleted successfully')]
    #[OA\Response(response: 400, description: 'Cannot delete category with associated events')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function destroy(int $id): JsonResponse
    {
        $category = EventCategory::find($id);

        if (!$category) {
            return $this->error('Event category not found', 404);
        }

        try {
            $this->categoryService->deleteCategory($category);
            return $this->success(null, 'Event category deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
