<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Amenity;
use Modules\RealEstate\Transformers\AmenityResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Amenities', description: 'Public amenity listing endpoints')]
class AmenityController extends Controller
{
    /**
     * List active amenities.
     */
    #[OA\Get(
        path: '/api/realestate/amenities',
        summary: 'List amenities',
        tags: ['Amenities'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string', enum: ['compound', 'property', 'both'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of amenities'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Amenity::active()->orderBy('order');
        
        if ($request->has('category')) {
            $category = $request->input('category');
            if ($category === 'compound') {
                $query->forCompounds();
            } elseif ($category === 'property') {
                $query->forProperties();
            }
        }
        
        $amenities = $query->get();
        
        return response()->json([
            'data' => AmenityResource::collection($amenities),
        ]);
    }
}
