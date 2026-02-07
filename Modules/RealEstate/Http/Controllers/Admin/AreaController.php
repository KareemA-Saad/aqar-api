<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Area;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Http\Requests\StoreAreaRequest;
use Modules\RealEstate\Http\Requests\UpdateAreaRequest;
use Modules\RealEstate\Services\AreaService;
use Modules\RealEstate\Transformers\AreaResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Areas', description: 'Area/Location management endpoints')]
class AreaController extends BaseController
{
    public function __construct(
        protected AreaService $areaService
    ) {}

    /**
     * List all areas with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas',
        summary: 'List all areas',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        parameters: [
            new OA\Parameter(name: 'filter[parent_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[type]', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of areas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_AreaResource')
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
    public function index(Request $request): JsonResponse
    {
        $areas = $this->areaService->getPaginatedAreas($request->all());
        
        return response()->json([
            'data' => AreaResource::collection($areas),
            'meta' => [
                'total' => $areas->total(),
                'per_page' => $areas->perPage(),
                'current_page' => $areas->currentPage(),
                'last_page' => $areas->lastPage(),
            ],
        ]);
    }

    /**
     * Get areas tree structure.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/tree',
        summary: 'Get areas as hierarchical tree',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Areas tree structure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_AreaResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function tree(): JsonResponse
    {
        $tree = $this->areaService->getAreasTree();
        
        return response()->json([
            'data' => AreaResource::collection($tree),
        ]);
    }

    /**
     * Store a new area.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas',
        summary: 'Create a new area',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StoreAreaRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Area created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Area created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_AreaResource'),
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
    public function store(StoreAreaRequest $request): JsonResponse
    {
        $area = $this->areaService->createArea($request->validated());
        
        return response()->json([
            'message' => 'Area created successfully.',
            'data' => new AreaResource($area),
        ], 201);
    }

    /**
     * Show a single area.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/{id}',
        summary: 'Get area details',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Area details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_AreaResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Area not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Area not found.'),
                    ]
                )
            ),
        ]
    )]
    public function show(int|string $id): JsonResponse
    {
        $area = $this->areaService->getArea($id);
        
        if (!$area) {
            return response()->json(['message' => 'Area not found.'], 404);
        }
        
        return response()->json([
            'data' => new AreaResource($area),
        ]);
    }

    /**
     * Update an area.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/{id}',
        summary: 'Update an area',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_UpdateAreaRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Area updated'),
            new OA\Response(response: 404, description: 'Area not found'),
        ]
    )]
    public function update(UpdateAreaRequest $request, int|string $id): JsonResponse
    {
        $area = Area::findOrFail((int) $id);
        
        try {
            $area = $this->areaService->updateArea($area, $request->validated());
            
            return response()->json([
                'message' => 'Area updated successfully.',
                'data' => new AreaResource($area),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete an area.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/{id}',
        summary: 'Delete an area',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Area deleted'),
            new OA\Response(response: 404, description: 'Area not found'),
            new OA\Response(response: 422, description: 'Cannot delete area with children'),
        ]
    )]
    public function destroy(int|string $id): JsonResponse
    {
        $area = Area::findOrFail((int) $id);
        
        try {
            $this->areaService->deleteArea($area);
            
            return response()->json([
                'message' => 'Area deleted successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reorder areas.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/reorder',
        summary: 'Reorder areas',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Areas reordered'),
        ]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:re_areas,id',
        ]);
        
        $this->areaService->reorderAreas($request->input('ids'));
        
        return response()->json([
            'message' => 'Areas reordered successfully.',
        ]);
    }

    /**
     * Get area statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/statistics',
        summary: 'Get area statistics',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        responses: [
            new OA\Response(response: 200, description: 'Area statistics'),
        ]
    )]
    public function statistics(): JsonResponse
    {
        return response()->json([
            'data' => $this->areaService->getStatistics(),
        ]);
    }

    /**
     * Get child areas.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/areas/{id}/children',
        summary: 'Get child areas',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Child areas'),
        ]
    )]
    public function children(int $id): JsonResponse
    {
        $children = $this->areaService->getChildAreas($id);
        
        return response()->json([
            'data' => AreaResource::collection($children),
        ]);
    }
}
