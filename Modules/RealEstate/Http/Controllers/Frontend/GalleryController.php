<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Transformers\PropertyImageResource;
use Modules\RealEstate\Transformers\CompoundImageResource;
use OpenApi\Attributes as OA;

/**
 * Gallery Controller
 * 
 * Frontend controller for fetching property and compound image galleries.
 */
#[OA\Tag(name: 'Frontend - Gallery', description: 'Public image gallery endpoints')]
class GalleryController extends Controller
{
    /**
     * Get property image gallery.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/gallery/properties/{property}',
        summary: 'Get property image gallery',
        description: 'Fetch all images for a property with multiple thumbnail sizes',
        tags: ['Frontend - Gallery'],
        parameters: [
            new OA\Parameter(
                name: 'property',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property gallery retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyImageResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Property not found'),
        ]
    )]
    public function propertyGallery(int $property): JsonResponse
    {
        $property = Property::with(['images' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->findOrFail($property);

        return response()->json([
            'data' => PropertyImageResource::collection($property->images),
        ]);
    }

    /**
     * Get compound image gallery.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/gallery/compounds/{compound}',
        summary: 'Get compound image gallery',
        description: 'Fetch all images for a compound (filtered by type if needed)',
        tags: ['Frontend - Gallery'],
        parameters: [
            new OA\Parameter(
                name: 'compound',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['gallery', 'master_plan', 'unit_plan']),
                description: 'Filter by image type'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Compound gallery retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_CompoundImageResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function compoundGallery(int $compound): JsonResponse
    {
        $compound = Compound::with(['images' => function ($query) {
            if (request()->has('type')) {
                $query->where('type', request('type'));
            }
            $query->orderBy('order', 'asc');
        }])->findOrFail($compound);

        return response()->json([
            'data' => CompoundImageResource::collection($compound->images),
        ]);
    }
}
