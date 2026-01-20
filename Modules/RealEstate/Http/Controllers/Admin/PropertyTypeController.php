<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\PropertyType;
use Modules\RealEstate\Http\Requests\StorePropertyTypeRequest;
use Modules\RealEstate\Transformers\PropertyTypeResource;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Property Types', description: 'Property type management endpoints')]
class PropertyTypeController extends Controller
{
    /**
     * List all property types.
     */
    #[OA\Get(
        path: '/api/admin/realestate/property-types',
        summary: 'List all property types',
        tags: ['Admin - Property Types'],
        parameters: [
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of property types'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $types = QueryBuilder::for(PropertyType::class)
            ->allowedFilters(['status'])
            ->allowedSorts(['order', 'name', 'created_at'])
            ->withCount('properties')
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'data' => PropertyTypeResource::collection($types),
        ]);
    }

    /**
     * Store a new property type.
     */
    #[OA\Post(
        path: '/api/admin/realestate/property-types',
        summary: 'Create a new property type',
        tags: ['Admin - Property Types'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePropertyTypeRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Property type created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePropertyTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        
        if (!isset($data['order'])) {
            $data['order'] = PropertyType::max('order') + 1;
        }
        
        $type = PropertyType::create($data);
        
        return response()->json([
            'message' => 'Property type created successfully.',
            'data' => new PropertyTypeResource($type),
        ], 201);
    }

    /**
     * Show a single property type.
     */
    #[OA\Get(
        path: '/api/admin/realestate/property-types/{id}',
        summary: 'Get property type details',
        tags: ['Admin - Property Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Property type details'),
            new OA\Response(response: 404, description: 'Property type not found'),
        ]
    )]
    public function show(PropertyType $propertyType): JsonResponse
    {
        $propertyType->loadCount('properties');
        
        return response()->json([
            'data' => new PropertyTypeResource($propertyType),
        ]);
    }

    /**
     * Update a property type.
     */
    #[OA\Put(
        path: '/api/admin/realestate/property-types/{id}',
        summary: 'Update a property type',
        tags: ['Admin - Property Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePropertyTypeRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Property type updated'),
            new OA\Response(response: 404, description: 'Property type not found'),
        ]
    )]
    public function update(StorePropertyTypeRequest $request, PropertyType $propertyType): JsonResponse
    {
        $propertyType->update($request->validated());
        
        return response()->json([
            'message' => 'Property type updated successfully.',
            'data' => new PropertyTypeResource($propertyType),
        ]);
    }

    /**
     * Delete a property type.
     */
    #[OA\Delete(
        path: '/api/admin/realestate/property-types/{id}',
        summary: 'Delete a property type',
        tags: ['Admin - Property Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Property type deleted'),
            new OA\Response(response: 404, description: 'Property type not found'),
            new OA\Response(response: 422, description: 'Cannot delete property type with properties'),
        ]
    )]
    public function destroy(PropertyType $propertyType): JsonResponse
    {
        if ($propertyType->properties()->exists()) {
            return response()->json([
                'message' => 'Cannot delete property type with associated properties.',
            ], 422);
        }
        
        $propertyType->delete();
        
        return response()->json([
            'message' => 'Property type deleted successfully.',
        ]);
    }

    /**
     * Reorder property types.
     */
    #[OA\Post(
        path: '/api/admin/realestate/property-types/reorder',
        summary: 'Reorder property types',
        tags: ['Admin - Property Types'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Property types reordered'),
        ]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:re_property_types,id',
        ]);
        
        foreach ($request->input('ids') as $index => $id) {
            PropertyType::where('id', $id)->update(['order' => $index]);
        }
        
        return response()->json([
            'message' => 'Property types reordered successfully.',
        ]);
    }
}
