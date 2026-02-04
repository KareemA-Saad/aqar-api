<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Http\Requests\BulkActionRequest;
use Modules\RealEstate\Http\Requests\StorePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyRequest;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\PropertyCollection;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Properties', description: 'Property management endpoints')]
class PropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * List all properties with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties',
        summary: 'List all properties',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        parameters: [
            new OA\Parameter(name: 'filter[area_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[compound_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[property_type_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[purpose]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of properties',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            ref: '#/components/schemas/RE_PaginationMeta'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): PropertyCollection
    {
        $filters = array_merge($request->all(), ['admin' => true]);
        $properties = $this->propertyService->getPaginatedProperties($filters);
        
        return new PropertyCollection($properties);
    }

    /**
     * Store a new property.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties',
        summary: 'Create a new property',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StorePropertyRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Property created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_PropertyResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/RE_ValidationErrorResponse')
            ),
        ]
    )]
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->propertyService->createProperty($request->validated());
        
        return response()->json([
            'message' => 'Property created successfully.',
            'data' => new PropertyResource($property),
        ], 201);
    }

    /**
     * Show a single property.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{id}',
        summary: 'Get property details',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_PropertyResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Property not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property not found.'),
                    ]
                )
            ),
        ]
    )]
    public function show(int|string $id): JsonResponse
    {
        $property = $this->propertyService->getProperty($id);
        
        if (!$property) {
            return response()->json(['message' => 'Property not found.'], 404);
        }
        
        return response()->json([
            'data' => new PropertyResource($property),
        ]);
    }

    /**
     * Update a property.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{id}',
        summary: 'Update a property',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_UpdatePropertyRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_PropertyResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Property not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property not found.'),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdatePropertyRequest $request, int|string $id): JsonResponse
    {
        $property = Property::findOrFail((int) $id);
        $property = $this->propertyService->updateProperty($property, $request->validated());
        
        return response()->json([
            'message' => 'Property updated successfully.',
            'data' => new PropertyResource($property),
        ]);
    }

    /**
     * Delete a property.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{id}',
        summary: 'Delete a property',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property deleted successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Property not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property not found.'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(int|string $id): JsonResponse
    {
        $property = Property::findOrFail((int) $id);
        $this->propertyService->deleteProperty($property);
        
        return response()->json([
            'message' => 'Property deleted successfully.',
        ]);
    }

    /**
     * Bulk action on properties.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/bulk',
        summary: 'Perform bulk action on properties',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_BulkActionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk action completed'),
        ]
    )]
    public function bulk(BulkActionRequest $request): JsonResponse
    {
        $count = $this->propertyService->bulkAction(
            $request->input('ids'),
            $request->input('action')
        );
        
        return response()->json([
            'message' => "Bulk action completed. {$count} properties affected.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Get property statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/statistics',
        summary: 'Get property statistics',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Properties'],
        responses: [
            new OA\Response(response: 200, description: 'Property statistics'),
        ]
    )]
    public function statistics(): JsonResponse
    {
        return response()->json([
            'data' => $this->propertyService->getStatistics(),
        ]);
    }
}
