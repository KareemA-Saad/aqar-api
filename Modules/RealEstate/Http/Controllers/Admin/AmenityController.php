<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Amenity;
use Modules\RealEstate\Http\Requests\StoreAmenityRequest;
use Modules\RealEstate\Transformers\AmenityResource;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Amenities', description: 'Amenity management endpoints')]
class AmenityController extends Controller
{
    /**
     * List all amenities.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities',
        summary: 'List all amenities',
        tags: ['Admin - Amenities'],
        parameters: [
            new OA\Parameter(name: 'filter[category]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['compound', 'property', 'both'])),
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of amenities'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $amenities = QueryBuilder::for(Amenity::class)
            ->allowedFilters(['category', 'status'])
            ->allowedSorts(['order', 'name', 'created_at'])
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'data' => AmenityResource::collection($amenities),
        ]);
    }

    /**
     * Store a new amenity.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities',
        summary: 'Create a new amenity',
        tags: ['Admin - Amenities'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StoreAmenityRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Amenity created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        
        if (!isset($data['order'])) {
            $data['order'] = Amenity::max('order') + 1;
        }
        
        $amenity = Amenity::create($data);
        
        return response()->json([
            'message' => 'Amenity created successfully.',
            'data' => new AmenityResource($amenity),
        ], 201);
    }

    /**
     * Show a single amenity.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities/{id}',
        summary: 'Get amenity details',
        tags: ['Admin - Amenities'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Amenity details'),
            new OA\Response(response: 404, description: 'Amenity not found'),
        ]
    )]
    public function show(Amenity $amenity): JsonResponse
    {
        return response()->json([
            'data' => new AmenityResource($amenity),
        ]);
    }

    /**
     * Update an amenity.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities/{id}',
        summary: 'Update an amenity',
        tags: ['Admin - Amenities'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StoreAmenityRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Amenity updated'),
            new OA\Response(response: 404, description: 'Amenity not found'),
        ]
    )]
    public function update(StoreAmenityRequest $request, Amenity $amenity): JsonResponse
    {
        $amenity->update($request->validated());
        
        return response()->json([
            'message' => 'Amenity updated successfully.',
            'data' => new AmenityResource($amenity),
        ]);
    }

    /**
     * Delete an amenity.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities/{id}',
        summary: 'Delete an amenity',
        tags: ['Admin - Amenities'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Amenity deleted'),
            new OA\Response(response: 404, description: 'Amenity not found'),
        ]
    )]
    public function destroy(Amenity $amenity): JsonResponse
    {
        // Detach from properties and compounds first
        $amenity->properties()->detach();
        $amenity->compounds()->detach();
        
        $amenity->delete();
        
        return response()->json([
            'message' => 'Amenity deleted successfully.',
        ]);
    }

    /**
     * Reorder amenities.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities/reorder',
        summary: 'Reorder amenities',
        tags: ['Admin - Amenities'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Amenities reordered'),
        ]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:re_amenities,id',
        ]);
        
        foreach ($request->input('ids') as $index => $id) {
            Amenity::where('id', $id)->update(['order' => $index]);
        }
        
        return response()->json([
            'message' => 'Amenities reordered successfully.',
        ]);
    }

    /**
     * Get amenities for compounds.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities/for-compounds',
        summary: 'Get amenities available for compounds',
        tags: ['Admin - Amenities'],
        responses: [
            new OA\Response(response: 200, description: 'List of compound amenities'),
        ]
    )]
    public function forCompounds(): JsonResponse
    {
        $amenities = Amenity::forCompounds()->active()->orderBy('order')->get();
        
        return response()->json([
            'data' => AmenityResource::collection($amenities),
        ]);
    }

    /**
     * Get amenities for properties.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/amenities/for-properties',
        summary: 'Get amenities available for properties',
        tags: ['Admin - Amenities'],
        responses: [
            new OA\Response(response: 200, description: 'List of property amenities'),
        ]
    )]
    public function forProperties(): JsonResponse
    {
        $amenities = Amenity::forProperties()->active()->orderBy('order')->get();
        
        return response()->json([
            'data' => AmenityResource::collection($amenities),
        ]);
    }
}
