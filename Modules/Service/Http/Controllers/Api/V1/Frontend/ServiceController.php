<?php

declare(strict_types=1);

namespace Modules\Service\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Service\Http\Resources\ServiceResource;
use Modules\Service\Http\Resources\ServiceCategoryResource;
use Modules\Service\Services\ServiceService;
use Modules\Service\Services\ServiceCategoryService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Services', description: 'Public service browsing endpoints')]
class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $serviceService,
        private readonly ServiceCategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/frontend/services',
        summary: 'Get published services',
        tags: ['Frontend - Services'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'price_plan', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Services retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ServiceResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'min_price', 'max_price', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $services = $this->serviceService->getPublishedServices($filters, $perPage);
        
        return response()->json(ServiceResource::collection($services));
    }

    #[OA\Get(
        path: '/api/v1/frontend/services/{slug}',
        summary: 'Get a specific service by slug',
        tags: ['Frontend - Services'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceResource'),
                        new OA\Property(
                            property: 'related',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ServiceResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Service not found'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $service = $this->serviceService->getServiceBySlug($slug);
        
        if (!$service) {
            return response()->json([
                'message' => 'Service not found',
            ], 404);
        }
        
        $related = $this->serviceService->getRelatedServices($service, 2);
        
        return response()->json([
            'data' => ServiceResource::make($service),
            'related' => ServiceResource::collection($related),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/services/category/{categoryId}',
        summary: 'Get services by category',
        tags: ['Frontend - Services'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Services retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ServiceResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function byCategory(int $categoryId, Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $services = $this->serviceService->getServicesByCategory($categoryId, $perPage);
        
        return response()->json(ServiceResource::collection($services));
    }

    #[OA\Get(
        path: '/api/v1/frontend/services/search/{query}',
        summary: 'Search services',
        tags: ['Frontend - Services'],
        parameters: [
            new OA\Parameter(name: 'query', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ServiceResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function search(string $query, Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $services = $this->serviceService->searchServices($query, $perPage);
        
        return response()->json(ServiceResource::collection($services));
    }

    #[OA\Get(
        path: '/api/v1/frontend/service-categories',
        summary: 'Get all active service categories',
        tags: ['Frontend - Services'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ServiceCategoryResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function categories(): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategories();
        
        return response()->json([
            'data' => ServiceCategoryResource::collection($categories),
        ]);
    }
}
