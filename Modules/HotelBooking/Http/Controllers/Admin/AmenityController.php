<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\StoreAmenityRequest;
use Modules\HotelBooking\Http\Resources\AmenityResource;
use Modules\HotelBooking\Services\AmenityService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Amenity Management', description: 'Amenity management endpoints for administrators')]
class AmenityController extends Controller
{
    protected AmenityService $amenityService;

    public function __construct(AmenityService $amenityService)
    {
        $this->amenityService = $amenityService;
    }

    #[OA\Get(
        path: '/api/v1/admin/amenities',
        summary: 'List all amenities',
        description: 'Get paginated list of all amenities',
        tags: ['Admin Amenity Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Amenities list retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $amenities = $this->amenityService->getAmenities($request->all());

        return response()->json([
            'success' => true,
            'data' => AmenityResource::collection($amenities),
            'meta' => [
                'current_page' => $amenities->currentPage(),
                'last_page' => $amenities->lastPage(),
                'per_page' => $amenities->perPage(),
                'total' => $amenities->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/amenities',
        summary: 'Create amenity',
        description: 'Create a new amenity',
        tags: ['Admin Amenity Management'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreAmenityRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Amenity created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $amenity = $this->amenityService->createAmenity($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Amenity created successfully.'),
            'data' => new AmenityResource($amenity),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/amenities/{id}',
        summary: 'Get amenity details',
        description: 'Get detailed information about a specific amenity',
        tags: ['Admin Amenity Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Amenity details retrieved'),
            new OA\Response(response: 404, description: 'Amenity not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $amenity = $this->amenityService->getAmenity($id);

        if (!$amenity) {
            return response()->json([
                'success' => false,
                'message' => __('Amenity not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AmenityResource($amenity),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/amenities/{id}',
        summary: 'Update amenity',
        description: 'Update an existing amenity',
        tags: ['Admin Amenity Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreAmenityRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Amenity updated successfully'),
            new OA\Response(response: 404, description: 'Amenity not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(StoreAmenityRequest $request, int $id): JsonResponse
    {
        $amenity = $this->amenityService->updateAmenity($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Amenity updated successfully.'),
            'data' => new AmenityResource($amenity),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/amenities/{id}',
        summary: 'Delete amenity',
        description: 'Delete an amenity',
        tags: ['Admin Amenity Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Amenity deleted successfully'),
            new OA\Response(response: 404, description: 'Amenity not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->amenityService->deleteAmenity($id);

        return response()->json([
            'success' => true,
            'message' => __('Amenity deleted successfully.'),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/admin/amenities/{id}/toggle-status',
        summary: 'Toggle amenity status',
        description: 'Toggle the active/inactive status of an amenity',
        tags: ['Admin Amenity Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Amenity status toggled'),
            new OA\Response(response: 404, description: 'Amenity not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $amenity = $this->amenityService->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => __('Amenity status updated successfully.'),
            'data' => [
                'id' => $amenity->id,
                'status' => $amenity->status,
            ],
        ]);
    }
}
