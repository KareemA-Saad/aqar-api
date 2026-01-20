<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Developer;
use Modules\RealEstate\Http\Requests\StoreDeveloperRequest;
use Modules\RealEstate\Http\Requests\UpdateDeveloperRequest;
use Modules\RealEstate\Transformers\DeveloperResource;
use Spatie\QueryBuilder\QueryBuilder;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Developers', description: 'Developer management endpoints')]
class DeveloperController extends Controller
{
    /**
     * List all developers with filters.
     */
    #[OA\Get(
        path: '/api/admin/realestate/developers',
        summary: 'List all developers',
        tags: ['Admin - Developers'],
        parameters: [
            new OA\Parameter(name: 'filter[is_featured]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of developers'),
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
        path: '/api/admin/realestate/developers',
        summary: 'Create a new developer',
        tags: ['Admin - Developers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StoreDeveloperRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Developer created'),
            new OA\Response(response: 422, description: 'Validation error'),
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
        path: '/api/admin/realestate/developers/{id}',
        summary: 'Get developer details',
        tags: ['Admin - Developers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Developer details'),
            new OA\Response(response: 404, description: 'Developer not found'),
        ]
    )]
    public function show(Developer $developer): JsonResponse
    {
        $developer->loadCount(['compounds', 'properties']);
        
        return response()->json([
            'data' => new DeveloperResource($developer),
        ]);
    }

    /**
     * Update a developer.
     */
    #[OA\Put(
        path: '/api/admin/realestate/developers/{id}',
        summary: 'Update a developer',
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
    public function update(UpdateDeveloperRequest $request, Developer $developer): JsonResponse
    {
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
        path: '/api/admin/realestate/developers/{id}',
        summary: 'Delete a developer',
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
    public function destroy(Developer $developer): JsonResponse
    {
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
