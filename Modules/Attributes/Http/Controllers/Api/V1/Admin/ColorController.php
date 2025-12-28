<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\Color;
use Modules\Attributes\Http\Resources\ColorResource;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Admin Color Controller
 */
#[OA\Tag(name: 'Admin - Colors', description: 'Color management endpoints')]
class ColorController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/colors',
        summary: 'List all colors',
        tags: ['Admin - Colors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Colors retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Color::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $colors = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Colors retrieved successfully'),
            'data' => ColorResource::collection($colors),
            'meta' => [
                'current_page' => $colors->currentPage(),
                'last_page' => $colors->lastPage(),
                'per_page' => $colors->perPage(),
                'total' => $colors->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/colors',
        summary: 'Create a new color',
        tags: ['Admin - Colors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'color_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Red'),
                    new OA\Property(property: 'color_code', type: 'string', example: '#FF0000'),
                    new OA\Property(property: 'slug', type: 'string')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Color created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color_code' => 'required|string|max:20',
            'slug' => 'nullable|string|max:255',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $color = Color::create($data);

        return response()->json([
            'success' => true,
            'message' => __('Color created successfully'),
            'data' => new ColorResource($color),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/colors/{id}',
        summary: 'Get a specific color',
        tags: ['Admin - Colors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Color retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $color = Color::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Color retrieved successfully'),
            'data' => new ColorResource($color),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/colors/{id}',
        summary: 'Update a color',
        tags: ['Admin - Colors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Color updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $color = Color::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color_code' => 'sometimes|string|max:20',
            'slug' => 'nullable|string|max:255',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $color->update($data);

        return response()->json([
            'success' => true,
            'message' => __('Color updated successfully'),
            'data' => new ColorResource($color),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/colors/{id}',
        summary: 'Delete a color',
        tags: ['Admin - Colors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Color deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        Color::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Color deleted successfully'),
        ]);
    }
}
