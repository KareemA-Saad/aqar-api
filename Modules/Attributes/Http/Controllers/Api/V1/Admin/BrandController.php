<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\Brand;
use Modules\Attributes\Http\Resources\BrandResource;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Admin Brand Controller
 */
#[OA\Tag(name: 'Admin - Brands', description: 'Brand management endpoints')]
class BrandController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/brands',
        summary: 'List all brands',
        tags: ['Admin - Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Brands retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Brand::with(['logo', 'banner']);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $brands = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Brands retrieved successfully'),
            'data' => BrandResource::collection($brands),
            'meta' => [
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'per_page' => $brands->perPage(),
                'total' => $brands->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/brands',
        summary: 'Create a new brand',
        tags: ['Admin - Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Apple'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'url', type: 'string'),
                    new OA\Property(property: 'image_id', type: 'integer'),
                    new OA\Property(property: 'banner_id', type: 'integer')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Brand created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'url' => 'nullable|url',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'banner_id' => 'nullable|exists:media_uploaders,id',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $brand = Brand::create($data);
        $brand->load(['logo', 'banner']);

        return response()->json([
            'success' => true,
            'message' => __('Brand created successfully'),
            'data' => new BrandResource($brand),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/brands/{id}',
        summary: 'Get a specific brand',
        tags: ['Admin - Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Brand retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $brand = Brand::with(['logo', 'banner'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Brand retrieved successfully'),
            'data' => new BrandResource($brand),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/brands/{id}',
        summary: 'Update a brand',
        tags: ['Admin - Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Brand updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'url' => 'nullable|url',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'banner_id' => 'nullable|exists:media_uploaders,id',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $brand->update($data);
        $brand->load(['logo', 'banner']);

        return response()->json([
            'success' => true,
            'message' => __('Brand updated successfully'),
            'data' => new BrandResource($brand),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/brands/{id}',
        summary: 'Delete a brand',
        tags: ['Admin - Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Brand deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        Brand::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Brand deleted successfully'),
        ]);
    }
}
