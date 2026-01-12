<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\Appointment;
use Modules\Appointment\Entities\AppointmentCategory;
use Modules\Appointment\Entities\AppointmentSubcategory;
use Modules\Appointment\Http\Resources\AppointmentCollection;
use Modules\Appointment\Http\Resources\AppointmentResource;
use Modules\Appointment\Http\Resources\CategoryResource;
use Modules\Appointment\Http\Resources\SubcategoryResource;
use Modules\Appointment\Services\AppointmentService;
use OpenApi\Attributes as OA;

/**
 * Frontend Appointment Controller
 *
 * Public-facing appointment browsing endpoints.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(
    name: 'Appointment - Browse',
    description: 'Browse and discover available appointments'
)]
final class AppointmentController extends BaseApiController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    /**
     * List all active appointments with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments',
        summary: 'List appointments',
        description: 'Get paginated list of active appointments with optional filters',
        tags: ['Appointment - Browse']
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
        name: 'category_id',
        in: 'query',
        description: 'Filter by category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'subcategory_id',
        in: 'query',
        description: 'Filter by subcategory ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'price_min',
        in: 'query',
        description: 'Minimum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'price_max',
        in: 'query',
        description: 'Maximum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['title', 'price', 'created_at', 'popular'])
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
        description: 'Appointments retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointments retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentCollection'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'category_id',
            'subcategory_id',
            'price_min',
            'price_max',
            'sort_by',
            'sort_order',
        ]);

        // Only show active appointments publicly
        $filters['status'] = 1;

        $perPage = min((int) $request->input('per_page', 15), 100);

        $appointments = $this->appointmentService->getAppointments($filters, $perPage);

        return $this->success(
            new AppointmentCollection($appointments),
            'Appointments retrieved successfully'
        );
    }

    /**
     * Get a single appointment by ID or slug.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/{identifier}',
        summary: 'Get appointment',
        description: 'Get a single appointment by ID or slug',
        tags: ['Appointment - Browse']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'identifier',
        in: 'path',
        required: true,
        description: 'Appointment ID or slug',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointment retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    public function show(string $identifier): JsonResponse
    {
        // Try to find by ID first, then by slug
        $appointment = is_numeric($identifier)
            ? Appointment::with(['category', 'subcategory', 'subAppointments', 'schedules'])
                ->where('id', $identifier)
                ->where('status', 1)
                ->first()
            : Appointment::with(['category', 'subcategory', 'subAppointments', 'schedules'])
                ->where('slug', $identifier)
                ->where('status', 1)
                ->first();

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        return $this->success(
            new AppointmentResource($appointment),
            'Appointment retrieved successfully'
        );
    }

    /**
     * List all categories.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/categories',
        summary: 'List categories',
        description: 'Get all active appointment categories',
        tags: ['Appointment - Browse']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'with_count',
        in: 'query',
        description: 'Include appointment count',
        schema: new OA\Schema(type: 'boolean', default: false)
    )]
    #[OA\Response(
        response: 200,
        description: 'Categories retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Categories retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/CategoryResource')
                ),
            ]
        )
    )]
    public function categories(Request $request): JsonResponse
    {
        $query = AppointmentCategory::query()
            ->where('status', 1)
            ->orderBy('title');

        if ($request->boolean('with_count')) {
            $query->withCount(['appointments' => function ($q) {
                $q->where('status', 1);
            }]);
        }

        $categories = $query->get();

        return $this->success(
            CategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    /**
     * Get appointments by category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/category/{categoryId}',
        summary: 'Appointments by category',
        description: 'Get appointments filtered by category',
        tags: ['Appointment - Browse']
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
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointments retrieved successfully'
    )]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $category = AppointmentCategory::find($categoryId);

        if (!$category || !$category->status) {
            return $this->error('Category not found', 404);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        $appointments = Appointment::with(['category', 'subcategory'])
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'category' => new CategoryResource($category),
            'appointments' => new AppointmentCollection($appointments),
        ], 'Appointments retrieved successfully');
    }

    /**
     * Get subcategories for a category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/categories/{categoryId}/subcategories',
        summary: 'List subcategories',
        description: 'Get all active subcategories for a category',
        tags: ['Appointment - Browse']
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
    #[OA\Response(response: 404, description: 'Category not found')]
    public function subcategories(int $categoryId): JsonResponse
    {
        $category = AppointmentCategory::find($categoryId);

        if (!$category || !$category->status) {
            return $this->error('Category not found', 404);
        }

        $subcategories = AppointmentSubcategory::where('category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('title')
            ->get();

        return $this->success(
            SubcategoryResource::collection($subcategories),
            'Subcategories retrieved successfully'
        );
    }

    /**
     * Get featured/popular appointments.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/featured',
        summary: 'Featured appointments',
        description: 'Get featured or popular appointments',
        tags: ['Appointment - Browse']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of appointments to return',
        schema: new OA\Schema(type: 'integer', default: 6, maximum: 20)
    )]
    #[OA\Response(
        response: 200,
        description: 'Featured appointments retrieved successfully'
    )]
    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 6), 20);

        // Get appointments with most bookings
        $appointments = Appointment::with(['category'])
            ->where('status', 1)
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->take($limit)
            ->get();

        return $this->success(
            AppointmentResource::collection($appointments),
            'Featured appointments retrieved successfully'
        );
    }

    /**
     * Search appointments.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/search',
        summary: 'Search appointments',
        description: 'Search appointments by keyword',
        tags: ['Appointment - Browse']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'q',
        in: 'query',
        required: true,
        description: 'Search keyword',
        schema: new OA\Schema(type: 'string', minLength: 2)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Search results retrieved successfully'
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $query = $request->input('q');
        $perPage = min((int) $request->input('per_page', 15), 100);

        $appointments = Appointment::with(['category', 'subcategory'])
            ->where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success(
            new AppointmentCollection($appointments),
            'Search results retrieved successfully'
        );
    }

    /**
     * Get related appointments.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/{id}/related',
        summary: 'Related appointments',
        description: 'Get appointments related to the specified one',
        tags: ['Appointment - Browse']
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
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of appointments to return',
        schema: new OA\Schema(type: 'integer', default: 4, maximum: 10)
    )]
    #[OA\Response(
        response: 200,
        description: 'Related appointments retrieved successfully'
    )]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    public function related(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        $limit = min((int) $request->input('limit', 4), 10);

        // Get appointments from the same category, excluding the current one
        $related = Appointment::with(['category'])
            ->where('status', 1)
            ->where('id', '!=', $id)
            ->where(function ($q) use ($appointment) {
                $q->where('category_id', $appointment->category_id)
                    ->orWhere('subcategory_id', $appointment->subcategory_id);
            })
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();

        return $this->success(
            AppointmentResource::collection($related),
            'Related appointments retrieved successfully'
        );
    }
}
