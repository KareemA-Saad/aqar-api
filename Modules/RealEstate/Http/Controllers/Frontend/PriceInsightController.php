<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Services\PriceInsightService;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Log;

#[OA\Tag(name: 'Price Insight', description: 'Market price analysis and insights')]
class PriceInsightController extends BaseController
{
    public function __construct(
        protected PropertyService $propertyService,
        protected PriceInsightService $priceInsightService
    ) {}

    /**
     * Get market price insight for a property.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/price-insight/{property}',
        summary: 'Get market price insight',
        description: 'Get market analysis and price comparison for a property based on comparable listings',
        tags: ['Price Insight'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Market insight calculated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'available', type: 'boolean'),
                            new OA\Property(property: 'property', type: 'object'),
                            new OA\Property(property: 'market_insight', type: 'object', properties: [
                                new OA\Property(property: 'area_name', type: 'string'),
                                new OA\Property(property: 'comparable_properties_count', type: 'integer'),
                                new OA\Property(property: 'market_statistics', type: 'object'),
                                new OA\Property(property: 'position_analysis', type: 'object'),
                                new OA\Property(property: 'recommendation', type: 'object'),
                            ]),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Property not found',
            ),
        ]
    )]
    public function show(Request $request, int $property): JsonResponse
    {
        Log::info('Price insight: requesting insight for property', [
            'property_id' => $property,
            'ip' => $request->ip(),
        ]);

        // Fetch property
        $propertyModel = $this->propertyService->getProperty($property);
        
        if (!$propertyModel || !$propertyModel->is_published) {
            Log::warning('Price insight: property not found or not published', [
                'property_id' => $property,
            ]);
            return $this->error('Property not found', 404);
        }

        // Get market insight
        $insight = $this->priceInsightService->getPropertyInsight($propertyModel);

        if (isset($insight['error'])) {
            Log::info('Price insight: insufficient data', [
                'property_id' => $property,
                'error' => $insight['error'],
            ]);
            // Return partial response with error message
            return $this->success([
                'available' => false,
                'property' => [
                    'id' => $propertyModel->id,
                    'title' => $propertyModel->title,
                    'price' => (float) $propertyModel->price,
                ],
                'error' => $insight['error'] ?? null,
            ]);
        }

        Log::info('Price insight: insight generated', [
            'property_id' => $property,
            'comparables' => $insight['market_insight']['comparable_properties_count'],
            'position' => $insight['market_insight']['position_analysis']['position'],
        ]);

        return $this->success($insight);
    }

    /**
     * Get market price insight for multiple properties (comparison).
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/price-insight/bulk',
        summary: 'Get price insights for multiple properties',
        description: 'Get market insights for up to 5 properties at once',
        tags: ['Price Insight'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['property_ids'],
                properties: [
                    new OA\Property(property: 'property_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Market insights retrieved',
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request - too many properties',
            ),
        ]
    )]
    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_ids' => 'required|array|min:1|max:5',
            'property_ids.*' => 'integer|exists:re_properties,id',
        ]);

        Log::info('Price insight: bulk request', [
            'count' => count($validated['property_ids']),
            'ip' => $request->ip(),
        ]);

        $insights = [];

        foreach ($validated['property_ids'] as $propertyId) {
            $propertyModel = $this->propertyService->getProperty($propertyId);
            
            if (!$propertyModel || !$propertyModel->is_published) {
                continue;
            }

            $insight = $this->priceInsightService->getPropertyInsight($propertyModel);
            $insights[] = $insight;
        }

        Log::info('Price insight: bulk processing complete', [
            'count' => count($insights),
        ]);

        return $this->success([
            'insights' => $insights,
            'count' => count($insights),
        ]);
    }
}
