<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\Size;
use Modules\Attributes\Http\Resources\SizeResource;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Admin Size Controller
 */
#[OA\Tag(name: 'Admin - Sizes', description: 'Size management endpoints')]
class SizeController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/sizes',
        summary: 'List all sizes',
        tags: ['Admin - Sizes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Sizes retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Size::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $sizes = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Sizes retrieved successfully'),
            'data' => SizeResource::collection($sizes),
            'meta' => [
                'current_page' => $sizes->currentPage(),
                'last_page' => $sizes->lastPage(),
                'per_page' => $sizes->perPage(),
                'total' => $sizes->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/sizes',
        summary: 'Create a new size',
        tags: ['Admin - Sizes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'size_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Large'),
                    new OA\Property(property: 'size_code', type: 'string', example: 'L'),
                    new OA\Property(property: 'slug', type: 'string')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Size created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'size_code' => 'required|string|max:20',
            'slug' => 'nullable|string|max:255',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $size = Size::create($data);

        return response()->json([
            'success' => true,
            'message' => __('Size created successfully'),
            'data' => new SizeResource($size),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/sizes/{id}',
        summary: 'Get a specific size',
        tags: ['Admin - Sizes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Size retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $size = Size::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Size retrieved successfully'),
            'data' => new SizeResource($size),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/sizes/{id}',
        summary: 'Update a size',
        tags: ['Admin - Sizes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Size updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $size = Size::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'size_code' => 'sometimes|string|max:20',
            'slug' => 'nullable|string|max:255',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $size->update($data);

        return response()->json([
            'success' => true,
            'message' => __('Size updated successfully'),
            'data' => new SizeResource($size),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/sizes/{id}',
        summary: 'Delete a size',
        tags: ['Admin - Sizes'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Size deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        Size::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Size deleted successfully'),
        ]);
    }
}
