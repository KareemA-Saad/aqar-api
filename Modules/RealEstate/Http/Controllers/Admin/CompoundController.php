<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Http\Requests\BulkActionRequest;
use Modules\RealEstate\Http\Requests\StoreCompoundRequest;
use Modules\RealEstate\Http\Requests\UpdateCompoundRequest;
use Modules\RealEstate\Services\CompoundService;
use Modules\RealEstate\Transformers\CompoundCollection;
use Modules\RealEstate\Transformers\CompoundResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Compounds', description: 'Compound/Project management endpoints')]
class CompoundController extends BaseController
{
    public function __construct(
        protected CompoundService $compoundService
    ) {}

    /**
     * List all compounds with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds',
        summary: 'List all compounds',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'filter[area_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[developer_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of compounds',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_CompoundResource')
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
    public function index(Request $request): CompoundCollection
    {
        $compounds = $this->compoundService->getPaginatedCompounds($request->all());
        
        return new CompoundCollection($compounds);
    }

    /**
     * Store a new compound.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds',
        summary: 'Create a new compound',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StoreCompoundRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Compound created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_CompoundResource'),
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
    public function store(StoreCompoundRequest $request): JsonResponse
    {
        $compound = $this->compoundService->createCompound($request->validated());
        
        return response()->json([
            'message' => 'Compound created successfully.',
            'data' => new CompoundResource($compound),
        ], 201);
    }

    /**
     * Show a single compound.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/{id}',
        summary: 'Get compound details',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Compound details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_CompoundResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Compound not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound not found.'),
                    ]
                )
            ),
        ]
    )]
    public function show(int|string $id): JsonResponse
    {
        $compound = $this->compoundService->getCompound($id);
        
        if (!$compound) {
            return response()->json(['message' => 'Compound not found.'], 404);
        }
        
        return response()->json([
            'data' => new CompoundResource($compound),
        ]);
    }

    /**
     * Update a compound.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/{id}',
        summary: 'Update a compound',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_UpdateCompoundRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Compound updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_CompoundResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Compound not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound not found.'),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdateCompoundRequest $request, int|string $id): JsonResponse
    {
        $compound = Compound::findOrFail((int) $id);
        $compound = $this->compoundService->updateCompound($compound, $request->validated());
        
        return response()->json([
            'message' => 'Compound updated successfully.',
            'data' => new CompoundResource($compound),
        ]);
    }

    /**
     * Delete a compound.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/{id}',
        summary: 'Delete a compound',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Compound deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound deleted successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Compound not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound not found.'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(int|string $id): JsonResponse
    {
        $compound = Compound::findOrFail((int) $id);
        $this->compoundService->deleteCompound($compound);
        
        return response()->json([
            'message' => 'Compound deleted successfully.',
        ]);
    }

    /**
     * Bulk action on compounds.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/bulk',
        summary: 'Perform bulk action on compounds',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
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
        $count = $this->compoundService->bulkAction(
            $request->input('ids'),
            $request->input('action')
        );
        
        return response()->json([
            'message' => "Bulk action completed. {$count} compounds affected.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Get compound statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/statistics',
        summary: 'Get compound statistics',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        responses: [
            new OA\Response(response: 200, description: 'Compound statistics'),
        ]
    )]
    public function statistics(): JsonResponse
    {
        return response()->json([
            'data' => $this->compoundService->getStatistics(),
        ]);
    }

    /**
     * Update compound price stats from properties.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/{id}/update-prices',
        summary: 'Recalculate compound price range from properties',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Prices updated'),
        ]
    )]
    public function updatePrices(int|string $id): JsonResponse
    {
        $compound = Compound::findOrFail((int) $id);
        $this->compoundService->updatePriceStats($compound);
        
        return response()->json([
            'message' => 'Compound price stats updated successfully.',
            'data' => new CompoundResource($compound->fresh()),
        ]);
    }
}
