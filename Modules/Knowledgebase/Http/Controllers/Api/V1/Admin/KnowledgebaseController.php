<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Knowledgebase\Http\Requests\BulkKnowledgebaseRequest;
use Modules\Knowledgebase\Http\Requests\StoreKnowledgebaseRequest;
use Modules\Knowledgebase\Http\Requests\UpdateKnowledgebaseRequest;
use Modules\Knowledgebase\Http\Resources\KnowledgebaseResource;
use Modules\Knowledgebase\Services\KnowledgebaseService;
use Modules\Knowledgebase\Entities\Knowledgebase;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Knowledgebase', description: 'Knowledgebase management endpoints')]
class KnowledgebaseController extends Controller
{
    public function __construct(
        private readonly KnowledgebaseService $knowledgebaseService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/knowledgebases',
        summary: 'Get paginated list of knowledgebase articles',
        tags: ['Admin - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'views', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase articles retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'status', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $knowledgebases = $this->knowledgebaseService->getKnowledgebases($filters, $perPage);
        
        return response()->json(KnowledgebaseResource::collection($knowledgebases));
    }

    #[OA\Post(
        path: '/api/v1/admin/knowledgebases',
        summary: 'Create a new knowledgebase article',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreKnowledgebaseRequest')
        ),
        tags: ['Admin - Knowledgebase'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Knowledgebase article created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreKnowledgebaseRequest $request): JsonResponse
    {
        $knowledgebase = $this->knowledgebaseService->createKnowledgebase($request->validated());
        
        return response()->json([
            'message' => 'Knowledgebase article created successfully',
            'data' => KnowledgebaseResource::make($knowledgebase),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/knowledgebases/{id}',
        summary: 'Get a specific knowledgebase article',
        tags: ['Admin - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase article retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Knowledgebase article not found'),
        ]
    )]
    public function show(Knowledgebase $knowledgebase): JsonResponse
    {
        $knowledgebase->load(['category', 'metainfo']);
        
        return response()->json([
            'data' => KnowledgebaseResource::make($knowledgebase),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/knowledgebases/{id}',
        summary: 'Update a knowledgebase article',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateKnowledgebaseRequest')
        ),
        tags: ['Admin - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase article updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Knowledgebase article not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateKnowledgebaseRequest $request, Knowledgebase $knowledgebase): JsonResponse
    {
        $knowledgebase = $this->knowledgebaseService->updateKnowledgebase($knowledgebase, $request->validated());
        
        return response()->json([
            'message' => 'Knowledgebase article updated successfully',
            'data' => KnowledgebaseResource::make($knowledgebase),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/knowledgebases/{id}',
        summary: 'Delete a knowledgebase article',
        tags: ['Admin - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase article deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Knowledgebase article not found'),
        ]
    )]
    public function destroy(Knowledgebase $knowledgebase): JsonResponse
    {
        $this->knowledgebaseService->deleteKnowledgebase($knowledgebase);
        
        return response()->json([
            'message' => 'Knowledgebase article deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/knowledgebases/{id}/clone',
        summary: 'Clone a knowledgebase article',
        tags: ['Admin - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Knowledgebase article cloned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Knowledgebase article not found'),
        ]
    )]
    public function clone(Knowledgebase $knowledgebase): JsonResponse
    {
        $clonedArticle = $this->knowledgebaseService->cloneKnowledgebase($knowledgebase);
        
        return response()->json([
            'message' => 'Knowledgebase article cloned successfully',
            'data' => KnowledgebaseResource::make($clonedArticle),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/admin/knowledgebases/bulk',
        summary: 'Perform bulk actions on knowledgebase articles',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkKnowledgebaseRequest')
        ),
        tags: ['Admin - Knowledgebase'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bulk action completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'processed', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAction(BulkKnowledgebaseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $knowledgebases = Knowledgebase::whereIn('id', $validated['ids'])->get();
        
        $processed = 0;
        
        foreach ($knowledgebases as $knowledgebase) {
            match ($validated['action']) {
                'delete' => $this->knowledgebaseService->deleteKnowledgebase($knowledgebase),
                'activate' => $knowledgebase->update(['status' => true]),
                'deactivate' => $knowledgebase->update(['status' => false]),
            };
            $processed++;
        }
        
        return response()->json([
            'message' => "Bulk action '{$validated['action']}' completed successfully",
            'processed' => $processed,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/knowledgebases/popular/top',
        summary: 'Get most viewed knowledgebase articles',
        tags: ['Admin - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular articles retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);
        $popular = $this->knowledgebaseService->getPopularKnowledgebases($limit);
        
        return response()->json([
            'data' => KnowledgebaseResource::collection($popular),
        ]);
    }
}
