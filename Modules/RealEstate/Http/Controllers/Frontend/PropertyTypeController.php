<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RealEstate\Entities\PropertyType;
use Modules\RealEstate\Transformers\PropertyTypeResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Property Types', description: 'Public property type listing endpoints')]
class PropertyTypeController extends Controller
{
    /**
     * List active property types.
     */
    #[OA\Get(
        path: '/api/realestate/property-types',
        summary: 'List property types',
        tags: ['Property Types'],
        responses: [
            new OA\Response(response: 200, description: 'List of property types'),
        ]
    )]
    public function index(): JsonResponse
    {
        $types = PropertyType::active()
            ->withCount('properties')
            ->orderBy('order')
            ->get();
        
        return response()->json([
            'data' => PropertyTypeResource::collection($types),
        ]);
    }

    /**
     * Show a single property type.
     */
    #[OA\Get(
        path: '/api/realestate/property-types/{slug}',
        summary: 'Get property type details',
        tags: ['Property Types'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Property type details'),
            new OA\Response(response: 404, description: 'Property type not found'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $type = PropertyType::where('slug', $slug)
            ->active()
            ->withCount('properties')
            ->first();
        
        if (!$type) {
            return response()->json(['message' => 'Property type not found.'], 404);
        }
        
        return response()->json([
            'data' => new PropertyTypeResource($type),
        ]);
    }
}
