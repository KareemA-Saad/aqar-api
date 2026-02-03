<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ThemeResource;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Theme Controller
 *
 * Handles theme listing and retrieval for subscription selection.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Themes',
    description: 'Theme management and listing endpoints'
)]
class ThemeController extends BaseApiController
{
    /**
     * List all available themes.
     *
     * Returns a list of themes that can be selected during subscription.
     * Optionally filter by status and availability.
     */
    #[OA\Get(
        path: '/api/v1/themes',
        summary: 'List all themes',
        description: 'Get a list of all available themes for subscription selection. Can be filtered by status and availability.',
        tags: ['Themes'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by active/inactive status (1 for active, 0 for inactive)',
                required: false,
                schema: new OA\Schema(type: 'integer', enum: [0, 1])
            ),
            new OA\Parameter(
                name: 'available',
                in: 'query',
                description: 'Filter by availability (1 for available, 0 for unavailable)',
                required: false,
                schema: new OA\Schema(type: 'integer', enum: [0, 1])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page (default: 20, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Themes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Themes retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ThemeResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 2),
                                new OA\Property(property: 'per_page', type: 'integer', example: 20),
                                new OA\Property(property: 'to', type: 'integer', example: 20),
                                new OA\Property(property: 'total', type: 'integer', example: 25),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request parameters',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid parameters'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'status',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The status field must be 0 or 1.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', 'integer', 'in:0,1'],
            'available' => ['nullable', 'integer', 'in:0,1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Theme::query()->orderBy('title');

        // Apply filters
        if (isset($validated['status'])) {
            $query->where('status', (bool) $validated['status']);
        }

        if (isset($validated['available'])) {
            $query->where('is_available', (bool) $validated['available']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $themes = $query->paginate($perPage);

        return ThemeResource::collection($themes);
    }

    /**
     * Get a specific theme by ID or slug.
     *
     * Retrieve detailed information about a specific theme.
     */
    #[OA\Get(
        path: '/api/v1/themes/{identifier}',
        summary: 'Get theme by ID or slug',
        description: 'Retrieve detailed information about a specific theme using its ID or slug',
        tags: ['Themes'],
        parameters: [
            new OA\Parameter(
                name: 'identifier',
                in: 'path',
                description: 'Theme ID (integer) or slug (string)',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'ecommerce')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Theme retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Theme retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ThemeResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Theme not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Theme not found'),
                    ]
                )
            ),
        ]
    )]
    public function show(Request $request, string $identifier): JsonResponse
    {
        // Try to find by ID first, then by slug
        $theme = is_numeric($identifier)
            ? Theme::find($identifier)
            : Theme::where('slug', $identifier)->first();

        if (!$theme) {
            return $this->error('Theme not found', 404);
        }

        return $this->success(
            new ThemeResource($theme),
            'Theme retrieved successfully'
        );
    }

    /**
     * Get only active and available themes.
     *
     * Returns themes that are currently active and available for selection.
     * This is the recommended endpoint for subscription forms.
     */
    #[OA\Get(
        path: '/api/v1/themes/available',
        summary: 'List available themes',
        description: 'Get only active and available themes for subscription selection',
        tags: ['Themes'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page (default: 20, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Available themes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Available themes retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ThemeResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function available(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $themes = Theme::query()
            ->active()
            ->available()
            ->orderBy('title')
            ->paginate($perPage);

        return ThemeResource::collection($themes);
    }
}
