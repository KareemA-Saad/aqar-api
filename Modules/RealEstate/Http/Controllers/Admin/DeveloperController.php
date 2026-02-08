<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Developer;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Http\Requests\StoreDeveloperRequest;
use Modules\RealEstate\Http\Requests\UpdateDeveloperRequest;
use Modules\RealEstate\Transformers\DeveloperResource;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Developers', description: 'Developer management endpoints')]
class DeveloperController extends BaseController
{
    /**
     * List all developers with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/developers',
        summary: 'List all developers',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Developers'],
        parameters: [
            new OA\Parameter(name: 'filter[is_featured]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of developers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_DeveloperResource')
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
        $developers = QueryBuilder::for(Developer::class)
            ->allowedFilters(['is_featured', 'status'])
            ->allowedSorts(['created_at', 'name'])
            ->withCount(['compounds', 'properties'])
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'data' => DeveloperResource::collection($developers),
            'meta' => [
                'total' => $developers->total(),
                'per_page' => $developers->perPage(),
                'current_page' => $developers->currentPage(),
                'last_page' => $developers->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new developer.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/developers',
        summary: 'Create a new developer',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Developers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StoreDeveloperRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Developer created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Developer created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_DeveloperResource'),
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
    public function store(StoreDeveloperRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        
        $developer = Developer::create($data);
        
        return response()->json([
            'message' => 'Developer created successfully.',
            'data' => new DeveloperResource($developer),
        ], 201);
    }

    /**
     * Show a single developer.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/developers/{id}',
        summary: 'Get developer details',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Developers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Developer details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_DeveloperResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Developer not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Developer not found.'),
                    ]
                )
            ),
        ]
    )]
    public function show(int|string $id): JsonResponse
    {
        $developer = Developer::withCount(['compounds', 'properties'])->findOrFail((int) $id);
        
        return response()->json([
            'data' => new DeveloperResource($developer),
        ]);
    }

    /**
     * Update a developer.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/developers/{id}',
        summary: 'Update a developer',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Developers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_UpdateDeveloperRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Developer updated'),
            new OA\Response(response: 404, description: 'Developer not found'),
        ]
    )]
    public function update(UpdateDeveloperRequest $request, int|string $id): JsonResponse
    {
        $developer = Developer::findOrFail((int) $id);
        $developer->update($request->validated());
        
        return response()->json([
            'message' => 'Developer updated successfully.',
            'data' => new DeveloperResource($developer),
        ]);
    }

    /**
     * Delete a developer.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/developers/{id}',
        summary: 'Delete a developer',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Developers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Developer deleted'),
            new OA\Response(response: 404, description: 'Developer not found'),
            new OA\Response(response: 422, description: 'Cannot delete developer with compounds'),
        ]
    )]
    public function destroy(int|string $id): JsonResponse
    {
        $developer = Developer::findOrFail((int) $id);
        
        if ($developer->compounds()->exists()) {
            return response()->json([
                'message' => 'Cannot delete developer with associated compounds.',
            ], 422);
        }
        
        $developer->delete();
        
        return response()->json([
            'message' => 'Developer deleted successfully.',
        ]);
    }
}
