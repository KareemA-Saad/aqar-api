<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\Tag;
use Modules\Attributes\Http\Resources\TagResource;
use OpenApi\Attributes as OA;

/**
 * Admin Tag Controller
 */
#[OA\Tag(name: 'Admin - Tags', description: 'Tag management endpoints')]
class TagController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/tags',
        summary: 'List all tags',
        tags: ['Admin - Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Tags retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query();

        if ($request->filled('search')) {
            $query->where('tag_text', 'like', "%{$request->search}%");
        }

        $tags = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Tags retrieved successfully'),
            'data' => TagResource::collection($tags),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/tags',
        summary: 'Create a new tag',
        tags: ['Admin - Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tag_text'],
                properties: [
                    new OA\Property(property: 'tag_text', type: 'string', example: 'New Arrival')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Tag created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tag_text' => 'required|string|max:255',
        ]);

        $tag = Tag::create($data);

        return response()->json([
            'success' => true,
            'message' => __('Tag created successfully'),
            'data' => new TagResource($tag),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/tags/{id}',
        summary: 'Get a specific tag',
        tags: ['Admin - Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Tag retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Tag retrieved successfully'),
            'data' => new TagResource($tag),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/tags/{id}',
        summary: 'Update a tag',
        tags: ['Admin - Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Tag updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $data = $request->validate([
            'tag_text' => 'sometimes|string|max:255',
        ]);

        $tag->update($data);

        return response()->json([
            'success' => true,
            'message' => __('Tag updated successfully'),
            'data' => new TagResource($tag),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/tags/{id}',
        summary: 'Delete a tag',
        tags: ['Admin - Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Tag deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        Tag::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Tag deleted successfully'),
        ]);
    }
}
