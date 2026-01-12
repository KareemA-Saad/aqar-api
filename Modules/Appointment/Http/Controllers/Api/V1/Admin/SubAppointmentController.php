<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\SubAppointment;
use Modules\Appointment\Http\Requests\Api\V1\StoreSubAppointmentRequest;
use Modules\Appointment\Http\Requests\Api\V1\UpdateSubAppointmentRequest;
use Modules\Appointment\Http\Resources\SubAppointmentCollection;
use Modules\Appointment\Http\Resources\SubAppointmentResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin SubAppointment Controller
 *
 * Manages sub-appointments (add-on services) within a tenant context.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Sub Appointments',
    description: 'Manage sub-appointments (add-on services) within a tenant'
)]
final class SubAppointmentController extends BaseApiController
{
    /**
     * List all sub-appointments with pagination and filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments',
        summary: 'List sub-appointments',
        description: 'Get paginated list of sub-appointments with optional filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search by title or description',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status (1=active, 0=inactive)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1])
    )]
    #[OA\Parameter(
        name: 'is_popular',
        in: 'query',
        description: 'Filter by popular status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'min_price',
        in: 'query',
        description: 'Minimum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'max_price',
        in: 'query',
        description: 'Maximum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['title', 'price', 'views', 'created_at', 'updated_at', 'status'])
    )]
    #[OA\Parameter(
        name: 'sort_order',
        in: 'query',
        description: 'Sort direction',
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Sub-appointments retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sub-appointments retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubAppointmentCollection'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $query = SubAppointment::query()->with('metainfo')->withCount('comments');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', (bool) $request->status);
        }

        // Popular filter
        if ($request->has('is_popular')) {
            $query->where('is_popular', (bool) $request->is_popular);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['title', 'price', 'views', 'created_at', 'updated_at', 'status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $subAppointments = $query->paginate($perPage);

        return $this->success(
            new SubAppointmentCollection($subAppointments),
            'Sub-appointments retrieved successfully'
        );
    }

    /**
     * Create a new sub-appointment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments',
        summary: 'Create sub-appointment',
        description: 'Create a new sub-appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
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
        content: new OA\JsonContent(ref: '#/components/schemas/StoreSubAppointmentRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Sub-appointment created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sub-appointment created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubAppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreSubAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $subAppointment = DB::transaction(function () use ($data) {
            // Generate slug
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->generateUniqueSlug($slug);

            $subAppointment = SubAppointment::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? 0,
                'slug' => $slug,
                'status' => $data['status'] ?? true,
                'is_popular' => $data['is_popular'] ?? false,
                'image' => $data['image'] ?? null,
                'person' => $data['person'] ?? 1,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                $subAppointment->metainfo()->create([
                    'title' => $data['meta_title'] ?? null,
                    'description' => $data['meta_description'] ?? null,
                    'image' => $data['meta_image'] ?? null,
                ]);
            }

            return $subAppointment;
        });

        return $this->success(
            new SubAppointmentResource($subAppointment->fresh('metainfo')),
            'Sub-appointment created successfully',
            201
        );
    }

    /**
     * Get a single sub-appointment.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments/{id}',
        summary: 'Get sub-appointment',
        description: 'Get a single sub-appointment by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
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
        description: 'Sub-appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Sub-appointment retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sub-appointment retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubAppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Sub-appointment not found')]
    public function show(int $id): JsonResponse
    {
        $subAppointment = SubAppointment::with(['metainfo', 'comments'])->find($id);

        if (!$subAppointment) {
            return $this->error('Sub-appointment not found', 404);
        }

        return $this->success(
            new SubAppointmentResource($subAppointment),
            'Sub-appointment retrieved successfully'
        );
    }

    /**
     * Update a sub-appointment.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments/{id}',
        summary: 'Update sub-appointment',
        description: 'Update an existing sub-appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
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
        description: 'Sub-appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateSubAppointmentRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Sub-appointment updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sub-appointment updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubAppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Sub-appointment not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateSubAppointmentRequest $request, int $id): JsonResponse
    {
        $subAppointment = SubAppointment::find($id);

        if (!$subAppointment) {
            return $this->error('Sub-appointment not found', 404);
        }

        $data = $request->validated();

        DB::transaction(function () use ($subAppointment, $data) {
            // Update slug if title changed
            if (isset($data['title']) && $data['title'] !== $subAppointment->title) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                $data['slug'] = $this->generateUniqueSlug($slug, $subAppointment->id);
            }

            $subAppointment->update([
                'title' => $data['title'] ?? $subAppointment->title,
                'description' => $data['description'] ?? $subAppointment->description,
                'price' => $data['price'] ?? $subAppointment->price,
                'slug' => $data['slug'] ?? $subAppointment->slug,
                'status' => $data['status'] ?? $subAppointment->status,
                'is_popular' => $data['is_popular'] ?? $subAppointment->is_popular,
                'image' => $data['image'] ?? $subAppointment->image,
                'person' => $data['person'] ?? $subAppointment->person,
            ]);

            // Update meta info
            if (isset($data['meta_title']) || isset($data['meta_description'])) {
                $metaData = [
                    'title' => $data['meta_title'] ?? $subAppointment->metainfo?->title,
                    'description' => $data['meta_description'] ?? $subAppointment->metainfo?->description,
                    'image' => $data['meta_image'] ?? $subAppointment->metainfo?->image,
                ];

                if ($subAppointment->metainfo) {
                    $subAppointment->metainfo->update($metaData);
                } else {
                    $subAppointment->metainfo()->create($metaData);
                }
            }
        });

        return $this->success(
            new SubAppointmentResource($subAppointment->fresh('metainfo')),
            'Sub-appointment updated successfully'
        );
    }

    /**
     * Delete a sub-appointment.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments/{id}',
        summary: 'Delete sub-appointment',
        description: 'Delete a sub-appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
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
        description: 'Sub-appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Sub-appointment deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sub-appointment deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Sub-appointment not found')]
    public function destroy(int $id): JsonResponse
    {
        $subAppointment = SubAppointment::find($id);

        if (!$subAppointment) {
            return $this->error('Sub-appointment not found', 404);
        }

        DB::transaction(function () use ($subAppointment) {
            // Delete meta info
            if ($subAppointment->metainfo) {
                $subAppointment->metainfo->delete();
            }

            // Delete additional appointment links
            $subAppointment->additional_appointments()->delete();

            $subAppointment->delete();
        });

        return $this->success(null, 'Sub-appointment deleted successfully');
    }

    /**
     * Toggle sub-appointment status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments/{id}/toggle-status',
        summary: 'Toggle sub-appointment status',
        description: 'Toggle the active/inactive status of a sub-appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
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
        description: 'Sub-appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sub-appointment status toggled successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubAppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Sub-appointment not found')]
    public function toggleStatus(int $id): JsonResponse
    {
        $subAppointment = SubAppointment::find($id);

        if (!$subAppointment) {
            return $this->error('Sub-appointment not found', 404);
        }

        $subAppointment->update(['status' => !$subAppointment->status]);

        return $this->success(
            new SubAppointmentResource($subAppointment->fresh()),
            'Sub-appointment status toggled successfully'
        );
    }

    /**
     * Bulk delete sub-appointments.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/sub-appointments/bulk-delete',
        summary: 'Bulk delete sub-appointments',
        description: 'Delete multiple sub-appointments at once',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Sub Appointments']
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
        content: new OA\JsonContent(
            required: ['ids'],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2, 3]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Sub-appointments deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: '3 sub-appointments deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:sub_appointments,id'],
        ]);

        $count = 0;
        $subAppointments = SubAppointment::whereIn('id', $request->ids)->get();

        DB::transaction(function () use ($subAppointments, &$count) {
            foreach ($subAppointments as $subAppointment) {
                if ($subAppointment->metainfo) {
                    $subAppointment->metainfo->delete();
                }
                $subAppointment->additional_appointments()->delete();
                $subAppointment->delete();
                $count++;
            }
        });

        return $this->success(null, "{$count} sub-appointments deleted successfully");
    }

    /**
     * Generate a unique slug.
     */
    private function generateUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        $query = SubAppointment::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $query = SubAppointment::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $counter++;
        }

        return $slug;
    }
}
