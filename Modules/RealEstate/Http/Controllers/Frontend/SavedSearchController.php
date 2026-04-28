<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\SavedSearchService;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

#[OA\Tag(name: 'Saved Searches', description: 'User saved search criteria with optional alerts')]
class SavedSearchController extends BaseController
{
    public function __construct(
        protected SavedSearchService $savedSearchService,
        protected PropertyService $propertyService
    ) {}

    /**
     * Get all saved searches for authenticated user.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches',
        summary: 'List saved searches',
        description: 'Get all saved searches for the authenticated user',
        tags: ['Saved Searches'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of saved searches',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'criteria', type: 'object'),
                                    new OA\Property(property: 'alerts_enabled', type: 'boolean'),
                                    new OA\Property(property: 'alert_frequency', type: 'string', enum: ['daily', 'weekly']),
                                    new OA\Property(property: 'last_alerted_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'last_matched_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'count', type: 'integer'),
                                new OA\Property(property: 'max_allowed', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Log::info('SavedSearchController: Fetching saved searches', ['user_id' => $userId]);

        $searches = $this->savedSearchService->getUserSearches($userId);

        return $this->success(
            $searches->toArray(),
            meta: [
                'count' => $searches->count(),
                'max_allowed' => SavedSearchService::MAX_SEARCHES_PER_USER,
                'can_add_more' => $searches->count() < SavedSearchService::MAX_SEARCHES_PER_USER,
            ]
        );
    }

    /**
     * Create a new saved search.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches',
        summary: 'Create saved search',
        description: 'Save a new property search with optional alert notifications',
        tags: ['Saved Searches'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'criteria'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Family Apartments in New Cairo'),
                    new OA\Property(
                        property: 'criteria',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'area_id', type: 'integer', nullable: true),
                            new OA\Property(property: 'property_type_id', type: 'integer', nullable: true),
                            new OA\Property(property: 'listing_type', type: 'string', enum: ['sale', 'rent'], nullable: true),
                            new OA\Property(property: 'price_min', type: 'number', nullable: true),
                            new OA\Property(property: 'price_max', type: 'number', nullable: true),
                            new OA\Property(property: 'bedrooms', type: 'integer', nullable: true),
                            new OA\Property(property: 'bedrooms_min', type: 'integer', nullable: true),
                            new OA\Property(property: 'bedrooms_max', type: 'integer', nullable: true),
                            new OA\Property(property: 'bathrooms', type: 'integer', nullable: true),
                            new OA\Property(property: 'bathrooms_min', type: 'integer', nullable: true),
                            new OA\Property(property: 'area_min', type: 'number', nullable: true),
                            new OA\Property(property: 'area_max', type: 'number', nullable: true),
                            new OA\Property(property: 'finishing', type: 'string', enum: ['finished', 'semi_finished', 'unfinished', 'furnished'], nullable: true),
                            new OA\Property(property: 'payment_option', type: 'string', enum: ['cash', 'installment', 'both'], nullable: true),
                            new OA\Property(property: 'is_featured', type: 'boolean', nullable: true),
                        ]
                    ),
                    new OA\Property(property: 'alerts_enabled', type: 'boolean', default: true),
                    new OA\Property(property: 'alert_frequency', type: 'string', enum: ['daily', 'weekly'], default: 'daily'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Saved search created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'match_count', type: 'integer', description: 'Current matching properties'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error or limit exceeded'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Log::info('SavedSearchController: Creating saved search', ['user_id' => $userId]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'criteria' => ['required', 'array'],
            'criteria.area_id' => ['nullable', 'integer', 'exists:re_areas,id'],
            'criteria.property_type_id' => ['nullable', 'integer', 'exists:re_property_types,id'],
            'criteria.listing_type' => ['nullable', 'string', Rule::in(['sale', 'rent'])],
            'criteria.price_min' => ['nullable', 'numeric', 'min:0'],
            'criteria.price_max' => ['nullable', 'numeric', 'min:0'],
            'criteria.bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bedrooms_min' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bedrooms_max' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bathrooms_min' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.area_min' => ['nullable', 'numeric', 'min:0'],
            'criteria.area_max' => ['nullable', 'numeric', 'min:0'],
            'criteria.finishing' => ['nullable', 'string', Rule::in(['finished', 'semi_finished', 'unfinished', 'furnished'])],
            'criteria.payment_option' => ['nullable', 'string', Rule::in(['cash', 'installment', 'both'])],
            'criteria.is_featured' => ['nullable', 'boolean'],
            'alerts_enabled' => ['nullable', 'boolean'],
            'alert_frequency' => ['nullable', 'string', Rule::in(['daily', 'weekly'])],
        ]);

        // Validate complex comparisons manually (gt: doesn't work with nested arrays)
        $criteria = $validated['criteria'] ?? [];
        if (isset($criteria['price_min']) && isset($criteria['price_max']) && $criteria['price_max'] <= $criteria['price_min']) {
            return $this->error('Price max must be greater than price min', status: 400);
        }
        if (isset($criteria['bedrooms_min']) && isset($criteria['bedrooms_max']) && $criteria['bedrooms_max'] < $criteria['bedrooms_min']) {
            return $this->error('Bedrooms max must be >= bedrooms min', status: 400);
        }
        if (isset($criteria['area_min']) && isset($criteria['area_max']) && $criteria['area_max'] <= $criteria['area_min']) {
            return $this->error('Area max must be greater than area min', status: 400);
        }

        try {
            $search = $this->savedSearchService->createSearch($userId, $validated);

            // Calculate match count for display
            $matchCount = $this->savedSearchService->getMatchCount($validated['criteria']);

            Log::info('SavedSearchController: Created saved search', [
                'id' => $search->id,
                'match_count' => $matchCount
            ]);

            return $this->success(
                $search->toArray(),
                message: 'Saved search created successfully',
                meta: ['match_count' => $matchCount],
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('SavedSearchController: Failed to create saved search', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->error($e->getMessage(), status: 400);
        }
    }

    /**
     * Get a specific saved search.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches/{id}',
        summary: 'Get saved search',
        description: 'Get details of a specific saved search',
        tags: ['Saved Searches'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saved search details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'match_count', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Saved search not found'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        Log::info('SavedSearchController: Fetching saved search', ['id' => $id, 'user_id' => $userId]);

        $search = $this->savedSearchService->getSearch($id, $userId);

        if (!$search) {
            return $this->error('Saved search not found', status: 404);
        }

        // Calculate current match count
        $matchCount = $this->savedSearchService->getMatchCount($search->criteria);

        return $this->success(
            $search->toArray(),
            meta: ['match_count' => $matchCount]
        );
    }

    /**
     * Update a saved search.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches/{id}',
        summary: 'Update saved search',
        description: 'Update an existing saved search',
        tags: ['Saved Searches'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'criteria', type: 'object', nullable: true),
                    new OA\Property(property: 'alerts_enabled', type: 'boolean', nullable: true),
                    new OA\Property(property: 'alert_frequency', type: 'string', enum: ['daily', 'weekly'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saved search updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'match_count', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Saved search not found'),
            new OA\Response(response: 400, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        Log::info('SavedSearchController: Updating saved search', ['id' => $id, 'user_id' => $userId]);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'criteria' => ['sometimes', 'required', 'array'],
            'criteria.area_id' => ['nullable', 'integer', 'exists:re_areas,id'],
            'criteria.property_type_id' => ['nullable', 'integer', 'exists:re_property_types,id'],
            'criteria.listing_type' => ['nullable', 'string', Rule::in(['sale', 'rent'])],
            'criteria.price_min' => ['nullable', 'numeric', 'min:0'],
            'criteria.price_max' => ['nullable', 'numeric', 'min:0'],
            'criteria.bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bedrooms_min' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bedrooms_max' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.bathrooms_min' => ['nullable', 'integer', 'min:0', 'max:20'],
            'criteria.area_min' => ['nullable', 'numeric', 'min:0'],
            'criteria.area_max' => ['nullable', 'numeric', 'min:0'],
            'criteria.finishing' => ['nullable', 'string', Rule::in(['finished', 'semi_finished', 'unfinished', 'furnished'])],
            'criteria.payment_option' => ['nullable', 'string', Rule::in(['cash', 'installment', 'both'])],
            'criteria.is_featured' => ['nullable', 'boolean'],
            'alerts_enabled' => ['sometimes', 'required', 'boolean'],
            'alert_frequency' => ['sometimes', 'required', 'string', Rule::in(['daily', 'weekly'])],
        ]);

        // Validate complex comparisons manually (gt: doesn't work with nested arrays)
        $criteria = $validated['criteria'] ?? [];
        if (isset($criteria['price_min']) && isset($criteria['price_max']) && $criteria['price_max'] <= $criteria['price_min']) {
            return $this->error('Price max must be greater than price min', status: 400);
        }
        if (isset($criteria['bedrooms_min']) && isset($criteria['bedrooms_max']) && $criteria['bedrooms_max'] < $criteria['bedrooms_min']) {
            return $this->error('Bedrooms max must be >= bedrooms min', status: 400);
        }
        if (isset($criteria['area_min']) && isset($criteria['area_max']) && $criteria['area_max'] <= $criteria['area_min']) {
            return $this->error('Area max must be greater than area min', status: 400);
        }

        try {
            $search = $this->savedSearchService->updateSearch($id, $userId, $validated);

            if (!$search) {
                return $this->error('Saved search not found', status: 404);
            }

            // Calculate match count
            $matchCount = $this->savedSearchService->getMatchCount($search->criteria);

            Log::info('SavedSearchController: Updated saved search', [
                'id' => $id,
                'match_count' => $matchCount
            ]);

            return $this->success(
                $search->toArray(),
                message: 'Saved search updated successfully',
                meta: ['match_count' => $matchCount]
            );
        } catch (\Exception $e) {
            Log::error('SavedSearchController: Failed to update saved search', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->error($e->getMessage(), status: 400);
        }
    }

    /**
     * Delete a saved search.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches/{id}',
        summary: 'Delete saved search',
        description: 'Delete a saved search',
        tags: ['Saved Searches'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saved search deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Saved search not found'),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        Log::info('SavedSearchController: Deleting saved search', ['id' => $id, 'user_id' => $userId]);

        $deleted = $this->savedSearchService->deleteSearch($id, $userId);

        if (!$deleted) {
            return $this->error('Saved search not found', status: 404);
        }

        Log::info('SavedSearchController: Deleted saved search', ['id' => $id]);

        return $this->success(
            null,
            message: 'Saved search deleted successfully'
        );
    }

    /**
     * Toggle alerts for a saved search.
     */
    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches/{id}/toggle-alerts',
        summary: 'Toggle alerts',
        description: 'Enable or disable alerts for a saved search',
        tags: ['Saved Searches'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alerts_enabled'],
                properties: [
                    new OA\Property(property: 'alerts_enabled', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Alerts toggled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Saved search not found'),
        ]
    )]
    public function toggleAlerts(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        Log::info('SavedSearchController: Toggling alerts', ['id' => $id, 'user_id' => $userId]);

        $validated = $request->validate([
            'alerts_enabled' => ['required', 'boolean'],
        ]);

        $search = $this->savedSearchService->toggleAlerts(
            $id,
            $userId,
            $validated['alerts_enabled']
        );

        if (!$search) {
            return $this->error('Saved search not found', status: 404);
        }

        $message = $validated['alerts_enabled'] ? 'Alerts enabled' : 'Alerts disabled';

        Log::info('SavedSearchController: Toggled alerts', [
            'id' => $id,
            'alerts_enabled' => $validated['alerts_enabled']
        ]);

        return $this->success(
            $search->toArray(),
            message: $message
        );
    }

    /**
     * Preview matching properties for a saved search.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/saved-searches/{id}/matches',
        summary: 'Preview matches',
        description: 'Get properties matching the saved search criteria (preview with limit)',
        tags: ['Saved Searches'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Matching properties',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_matches', type: 'integer'),
                                new OA\Property(property: 'showing', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Saved search not found'),
        ]
    )]
    public function matches(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = min((int) $request->query('limit', 10), 50);

        Log::info('SavedSearchController: Fetching matching properties', [
            'id' => $id,
            'user_id' => $userId,
            'limit' => $limit
        ]);

        $search = $this->savedSearchService->getSearch($id, $userId);

        if (!$search) {
            return $this->error('Saved search not found', status: 404);
        }

        // Get matching properties
        $properties = $this->savedSearchService->findMatchingProperties($search, $limit);
        $totalMatches = $this->savedSearchService->getMatchCount($search->criteria);

        Log::info('SavedSearchController: Found matching properties', [
            'id' => $id,
            'showing' => $properties->count(),
            'total_matches' => $totalMatches
        ]);

        return $this->success(
            PropertyResource::collection($properties),
            meta: [
                'total_matches' => $totalMatches,
                'showing' => $properties->count(),
            ]
        );
    }
}
