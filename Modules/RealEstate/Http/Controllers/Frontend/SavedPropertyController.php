<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Saved Properties', description: 'User saved/favorite properties management. All endpoints require authentication.')]
class SavedPropertyController extends Controller
{
    /**
     * Get user's saved properties.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/saved-properties',
        summary: 'Get saved properties',
        description: 'Get paginated list of properties saved by the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Saved Properties'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saved properties list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/RE_PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $savedProperties = $user->savedProperties()
            ->with(['area', 'propertyType', 'primaryImage'])
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'data' => PropertyResource::collection($savedProperties),
            'meta' => [
                'total' => $savedProperties->total(),
                'per_page' => $savedProperties->perPage(),
                'current_page' => $savedProperties->currentPage(),
                'last_page' => $savedProperties->lastPage(),
            ],
        ]);
    }

    /**
     * Save a property to favorites.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/saved-properties/{property}',
        summary: 'Save property to favorites',
        description: 'Add a property to the authenticated user\'s favorites list',
        security: [['sanctum' => []]],
        tags: ['Saved Properties'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, description: 'Property ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property saved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property saved to favorites.'),
                        new OA\Property(property: 'saved', type: 'boolean', example: true),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Property not found'),
        ]
    )]
    public function store(Request $request, int $property): JsonResponse
    {
        $user = $request->user();
        
        // Check if property exists and is active
        $propertyModel = Property::active()->find($property);
        
        if (!$propertyModel) {
            return response()->json(['message' => 'Property not found.'], 404);
        }
        
        // Check if already saved
        if ($user->savedProperties()->where('property_id', $property)->exists()) {
            return response()->json([
                'message' => 'Property is already saved.',
                'saved' => true,
            ]);
        }
        
        // Save property
        $user->savedProperties()->attach($property);
        
        return response()->json([
            'message' => 'Property saved to favorites.',
            'saved' => true,
        ]);
    }

    /**
     * Remove a property from favorites.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/realestate/saved-properties/{property}',
        summary: 'Remove property from favorites',
        security: [['sanctum' => []]],
        tags: ['Saved Properties'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Property removed'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(Request $request, int $property): JsonResponse
    {
        $user = $request->user();
        
        $user->savedProperties()->detach($property);
        
        return response()->json([
            'message' => 'Property removed from favorites.',
            'saved' => false,
        ]);
    }

    /**
     * Toggle save status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/saved-properties/{property}/toggle',
        summary: 'Toggle property save status',
        security: [['sanctum' => []]],
        tags: ['Saved Properties'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Save status toggled'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Property not found'),
        ]
    )]
    public function toggle(Request $request, int $property): JsonResponse
    {
        $user = $request->user();
        
        // Check if property exists and is active
        $propertyModel = Property::active()->find($property);
        
        if (!$propertyModel) {
            return response()->json(['message' => 'Property not found.'], 404);
        }
        
        $saved = $user->savedProperties()->where('property_id', $property)->exists();
        
        if ($saved) {
            $user->savedProperties()->detach($property);
            return response()->json([
                'message' => 'Property removed from favorites.',
                'saved' => false,
            ]);
        } else {
            $user->savedProperties()->attach($property);
            return response()->json([
                'message' => 'Property saved to favorites.',
                'saved' => true,
            ]);
        }
    }

    /**
     * Check if property is saved.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/saved-properties/{property}/check',
        summary: 'Check if property is saved',
        security: [['sanctum' => []]],
        tags: ['Saved Properties'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Save status'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function check(Request $request, int $property): JsonResponse
    {
        $user = $request->user();
        
        $saved = $user->savedProperties()->where('property_id', $property)->exists();
        
        return response()->json([
            'saved' => $saved,
        ]);
    }

    /**
     * Check multiple properties save status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/saved-properties/check-multiple',
        summary: 'Check multiple properties save status',
        security: [['sanctum' => []]],
        tags: ['Saved Properties'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'property_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Save statuses'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function checkMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'property_ids' => 'required|array',
            'property_ids.*' => 'integer',
        ]);
        
        $user = $request->user();
        $propertyIds = $request->input('property_ids');
        
        $savedIds = $user->savedProperties()
            ->whereIn('property_id', $propertyIds)
            ->pluck('property_id')
            ->toArray();
        
        $result = [];
        foreach ($propertyIds as $id) {
            $result[$id] = in_array($id, $savedIds);
        }
        
        return response()->json([
            'data' => $result,
        ]);
    }
}
