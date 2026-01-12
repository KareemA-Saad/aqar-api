<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\AppointmentCategory;
use Modules\Appointment\Entities\AppointmentSubcategory;
use Modules\Appointment\Http\Requests\Api\V1\StoreCategoryRequest;
use Modules\Appointment\Http\Requests\Api\V1\UpdateCategoryRequest;
use Modules\Appointment\Http\Resources\CategoryCollection;
use Modules\Appointment\Http\Resources\CategoryResource;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Category Controller
 *
 * Manages appointment categories and subcategories within a tenant context.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Appointment Categories',
    description: 'Manage appointment categories within a tenant'
)]
final class CategoryController extends BaseApiController
{
    /**
     * List all categories with subcategories.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories',
        summary: 'List categories',
        description: 'Get all appointment categories with their subcategories',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'with_appointments_count',
        in: 'query',
        description: 'Include appointments count',
        schema: new OA\Schema(type: 'boolean', default: false)
    )]
    #[OA\Response(
        response: 200,
        description: 'Categories retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Categories retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CategoryCollection'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $query = AppointmentCategory::with('subcategories');

        if ($request->has('status')) {
            $query->where('status', (bool) $request->status);
        }

        if ($request->boolean('with_appointments_count')) {
            $query->withCount('appointments');
        }

        $categories = $query->orderBy('id', 'desc')->get();

        return $this->success(
            CategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    /**
     * Create a new category.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories',
        summary: 'Create category',
        description: 'Create a new appointment category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreCategoryRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Category created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Category created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = AppointmentCategory::create([
            'title' => $request->title,
            'status' => $request->status ?? true,
        ]);

        return $this->success(
            new CategoryResource($category),
            'Category created successfully',
            201
        );
    }

    /**
     * Get a single category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories/{id}',
        summary: 'Get category',
        description: 'Get a single category by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Category retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Category retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function show(int $id): JsonResponse
    {
        $category = AppointmentCategory::with(['subcategories', 'appointments'])->find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        return $this->success(
            new CategoryResource($category),
            'Category retrieved successfully'
        );
    }

    /**
     * Update a category.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories/{id}',
        summary: 'Update category',
        description: 'Update an existing category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateCategoryRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Category updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Category updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = AppointmentCategory::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $category->update([
            'title' => $request->title ?? $category->title,
            'status' => $request->status ?? $category->status,
        ]);

        return $this->success(
            new CategoryResource($category->fresh('subcategories')),
            'Category updated successfully'
        );
    }

    /**
     * Delete a category.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories/{id}',
        summary: 'Delete category',
        description: 'Delete a category and optionally its subcategories',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Category deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Category deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function destroy(int $id): JsonResponse
    {
        $category = AppointmentCategory::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // Delete subcategories first
        $category->subcategories()->delete();
        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }

    /**
     * Toggle category status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories/{id}/toggle-status',
        summary: 'Toggle category status',
        description: 'Toggle the active/inactive status of a category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Status toggled successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function toggleStatus(int $id): JsonResponse
    {
        $category = AppointmentCategory::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $category->update(['status' => !$category->status]);

        return $this->success(
            new CategoryResource($category->fresh()),
            'Category status toggled successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Subcategory Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get subcategories for a category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories/{categoryId}/subcategories',
        summary: 'List subcategories',
        description: 'Get all subcategories for a category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'categoryId',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Subcategories retrieved successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function subcategories(int $categoryId): JsonResponse
    {
        $category = AppointmentCategory::find($categoryId);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $subcategories = AppointmentSubcategory::where('appointment_category_id', $categoryId)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($subcategories, 'Subcategories retrieved successfully');
    }

    /**
     * Create a subcategory.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/categories/{categoryId}/subcategories',
        summary: 'Create subcategory',
        description: 'Create a new subcategory under a category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'categoryId',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Hair Styling'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Subcategory created successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function storeSubcategory(Request $request, int $categoryId): JsonResponse
    {
        $category = AppointmentCategory::find($categoryId);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        $subcategory = AppointmentSubcategory::create([
            'appointment_category_id' => $categoryId,
            'title' => $request->title,
            'status' => $request->status ?? true,
        ]);

        return $this->success($subcategory, 'Subcategory created successfully', 201);
    }

    /**
     * Update a subcategory.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/subcategories/{id}',
        summary: 'Update subcategory',
        description: 'Update an existing subcategory',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Subcategory ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Hair Styling'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'appointment_category_id', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Subcategory updated successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Subcategory not found')]
    public function updateSubcategory(Request $request, int $id): JsonResponse
    {
        $subcategory = AppointmentSubcategory::find($id);

        if (!$subcategory) {
            return $this->error('Subcategory not found', 404);
        }

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'appointment_category_id' => ['nullable', 'integer', 'exists:appointment_categories,id'],
        ]);

        $subcategory->update([
            'title' => $request->title ?? $subcategory->title,
            'status' => $request->status ?? $subcategory->status,
            'appointment_category_id' => $request->appointment_category_id ?? $subcategory->appointment_category_id,
        ]);

        return $this->success($subcategory->fresh(), 'Subcategory updated successfully');
    }

    /**
     * Delete a subcategory.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/subcategories/{id}',
        summary: 'Delete subcategory',
        description: 'Delete a subcategory',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Subcategory ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Subcategory deleted successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Subcategory not found')]
    public function destroySubcategory(int $id): JsonResponse
    {
        $subcategory = AppointmentSubcategory::find($id);

        if (!$subcategory) {
            return $this->error('Subcategory not found', 404);
        }

        $subcategory->delete();

        return $this->success(null, 'Subcategory deleted successfully');
    }
}
