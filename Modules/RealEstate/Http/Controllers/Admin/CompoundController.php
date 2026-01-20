<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Http\Requests\BulkActionRequest;
use Modules\RealEstate\Http\Requests\StoreCompoundRequest;
use Modules\RealEstate\Http\Requests\UpdateCompoundRequest;
use Modules\RealEstate\Services\CompoundService;
use Modules\RealEstate\Transformers\CompoundCollection;
use Modules\RealEstate\Transformers\CompoundResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Compounds', description: 'Compound/Project management endpoints')]
class CompoundController extends Controller
{
    public function __construct(
        protected CompoundService $compoundService
    ) {}

    /**
     * List all compounds with filters.
     */
    #[OA\Get(
        path: '/api/admin/realestate/compounds',
        summary: 'List all compounds',
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'filter[area_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[developer_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of compounds'),
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
        path: '/api/admin/realestate/compounds',
        summary: 'Create a new compound',
        tags: ['Admin - Compounds'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCompoundRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Compound created'),
            new OA\Response(response: 422, description: 'Validation error'),
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
        path: '/api/admin/realestate/compounds/{id}',
        summary: 'Get compound details',
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound details'),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function show(int $id): JsonResponse
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
        path: '/api/admin/realestate/compounds/{id}',
        summary: 'Update a compound',
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateCompoundRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Compound updated'),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function update(UpdateCompoundRequest $request, Compound $compound): JsonResponse
    {
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
        path: '/api/admin/realestate/compounds/{id}',
        summary: 'Delete a compound',
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound deleted'),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function destroy(Compound $compound): JsonResponse
    {
        $this->compoundService->deleteCompound($compound);
        
        return response()->json([
            'message' => 'Compound deleted successfully.',
        ]);
    }

    /**
     * Bulk action on compounds.
     */
    #[OA\Post(
        path: '/api/admin/realestate/compounds/bulk',
        summary: 'Perform bulk action on compounds',
        tags: ['Admin - Compounds'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkActionRequest')
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
        path: '/api/admin/realestate/compounds/statistics',
        summary: 'Get compound statistics',
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
        path: '/api/admin/realestate/compounds/{id}/update-prices',
        summary: 'Recalculate compound price range from properties',
        tags: ['Admin - Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Prices updated'),
        ]
    )]
    public function updatePrices(Compound $compound): JsonResponse
    {
        $this->compoundService->updatePriceStats($compound);
        
        return response()->json([
            'message' => 'Compound price stats updated successfully.',
            'data' => new CompoundResource($compound->fresh()),
        ]);
    }
}
