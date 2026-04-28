<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Log;

#[OA\Tag(name: 'Property Comparison', description: 'Session-based property comparison')]
class PropertyComparisonController extends BaseController
{
    private const MAX_COMPARISON_ITEMS = 4;
    private const SESSION_KEY = 're_property_comparison';

    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * Get current comparison list.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/comparison',
        summary: 'Get comparison list',
        description: 'Get list of properties currently in comparison session',
        tags: ['Property Comparison'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comparison list with property details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'count', type: 'integer'),
                                new OA\Property(property: 'max_items', type: 'integer'),
                                new OA\Property(property: 'can_add_more', type: 'boolean'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        Log::info('Property comparison: fetching list', [
            'session_key' => self::SESSION_KEY,
            'ip' => $request->ip(),
        ]);

        $propertyIds = $this->getComparisonIds();
        $resources = [];

        if (!empty($propertyIds)) {
            $properties = Property::whereIn('id', $propertyIds)
                ->active()
                ->with(['compound.area', 'compound.developer', 'propertyType', 'primaryImage', 'amenities'])
                ->get();

            $resources = PropertyResource::collection($properties);

            Log::info('Property comparison: loaded properties', [
                'count' => $properties->count(),
            ]);
        }

        return $this->success(
            $resources,
            meta: [
                'count' => count($propertyIds),
                'max_items' => self::MAX_COMPARISON_ITEMS,
                'can_add_more' => count($propertyIds) < self::MAX_COMPARISON_ITEMS,
            ]
        );
    }

    /**
     * Add property to comparison.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/comparison/{property}',
        summary: 'Add property to comparison',
        description: 'Add a property to the comparison list (max 4 properties)',
        tags: ['Property Comparison'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property added to comparison',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_PropertyResource'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Comparison list is full or property already added',
            ),
            new OA\Response(
                response: 404,
                description: 'Property not found',
            ),
        ]
    )]
    public function add(Request $request, int $property): JsonResponse
    {
        Log::info('Property comparison: adding property', [
            'property_id' => $property,
            'ip' => $request->ip(),
        ]);

        // Fetch property
        $propertyModel = $this->propertyService->getProperty($property);
        
        if (!$propertyModel || !$propertyModel->is_published) {
            Log::warning('Property comparison: property not found or not published', [
                'property_id' => $property,
            ]);
            return $this->error('Property not found', 404);
        }

        // Get current comparison IDs
        $comparisonIds = $this->getComparisonIds();

        // Check if property already in comparison
        if (in_array($property, $comparisonIds)) {
            Log::info('Property comparison: property already in list', [
                'property_id' => $property,
            ]);
            return $this->error('Property already in comparison', 400);
        }

        // Check if comparison is full
        if (count($comparisonIds) >= self::MAX_COMPARISON_ITEMS) {
            Log::warning('Property comparison: list full', [
                'property_id' => $property,
                'current_count' => count($comparisonIds),
                'max_items' => self::MAX_COMPARISON_ITEMS,
            ]);
            return $this->error('Comparison list is full (max 4 properties)', 400);
        }

        // Add to comparison
        $comparisonIds[] = $property;
        session([self::SESSION_KEY => $comparisonIds]);

        Log::info('Property comparison: property added successfully', [
            'property_id' => $property,
            'total_in_comparison' => count($comparisonIds),
        ]);

        return $this->success(
            new PropertyResource($propertyModel),
            'Property added to comparison',
            meta: [
                'count' => count($comparisonIds),
                'max_items' => self::MAX_COMPARISON_ITEMS,
                'can_add_more' => count($comparisonIds) < self::MAX_COMPARISON_ITEMS,
            ]
        );
    }

    /**
     * Remove property from comparison.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/realestate/comparison/{property}',
        summary: 'Remove property from comparison',
        description: 'Remove a specific property from the comparison list',
        tags: ['Property Comparison'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property removed from comparison',
            ),
            new OA\Response(
                response: 404,
                description: 'Property not in comparison',
            ),
        ]
    )]
    public function remove(Request $request, int $property): JsonResponse
    {
        Log::info('Property comparison: removing property', [
            'property_id' => $property,
            'ip' => $request->ip(),
        ]);

        $comparisonIds = $this->getComparisonIds();

        if (!in_array($property, $comparisonIds)) {
            Log::warning('Property comparison: property not in list', [
                'property_id' => $property,
            ]);
            return $this->error('Property not in comparison', 404);
        }

        // Remove from comparison
        $comparisonIds = array_filter($comparisonIds, fn($id) => $id !== $property);
        session([self::SESSION_KEY => array_values($comparisonIds)]);

        Log::info('Property comparison: property removed successfully', [
            'property_id' => $property,
            'remaining_count' => count($comparisonIds),
        ]);

        return $this->success(
            null,
            'Property removed from comparison',
            meta: [
                'count' => count($comparisonIds),
            ]
        );
    }

    /**
     * Clear entire comparison list.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/realestate/comparison',
        summary: 'Clear comparison list',
        description: 'Clear all properties from the comparison list',
        tags: ['Property Comparison'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comparison list cleared',
            ),
        ]
    )]
    public function clear(Request $request): JsonResponse
    {
        Log::info('Property comparison: clearing list', [
            'ip' => $request->ip(),
        ]);

        session([self::SESSION_KEY => []]);

        return $this->success(
            null,
            'Comparison list cleared',
            meta: ['count' => 0]
        );
    }

    /**
     * Get comparison IDs from session.
     */
    private function getComparisonIds(): array
    {
        return session(self::SESSION_KEY, []) ?: [];
    }
}
