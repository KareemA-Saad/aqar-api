<?php

declare(strict_types=1);

namespace Modules\Service\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Service\Http\Requests\BulkServiceRequest;
use Modules\Service\Http\Requests\StoreServiceRequest;
use Modules\Service\Http\Requests\UpdateServiceRequest;
use Modules\Service\Http\Resources\ServiceResource;
use Modules\Service\Services\ServiceService;
use Modules\Service\Entities\Service;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Services', description: 'Service management endpoints')]
class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $serviceService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/services',
        summary: 'Get paginated list of services',
        tags: ['Admin - Services'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'price_plan', 'created_at', 'updated_at'])),
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
        $filters = $request->only(['search', 'category_id', 'status', 'min_price', 'max_price', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $services = $this->serviceService->getServices($filters, $perPage);
        
        return response()->json(ServiceResource::collection($services));
    }

    #[OA\Post(
        path: '/api/v1/admin/services',
        summary: 'Create a new service',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreServiceRequest')
        ),
        tags: ['Admin - Services'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Service created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->serviceService->createService($request->validated());
        
        return response()->json([
            'message' => 'Service created successfully',
            'data' => ServiceResource::make($service),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/services/{id}',
        summary: 'Get a specific service',
        tags: ['Admin - Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Service not found'),
        ]
    )]
    public function show(Service $service): JsonResponse
    {
        $service->load(['category', 'metainfo']);
        
        return response()->json([
            'data' => ServiceResource::make($service),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/services/{id}',
        summary: 'Update a service',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateServiceRequest')
        ),
        tags: ['Admin - Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Service not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service = $this->serviceService->updateService($service, $request->validated());
        
        return response()->json([
            'message' => 'Service updated successfully',
            'data' => ServiceResource::make($service),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/services/{id}',
        summary: 'Delete a service',
        tags: ['Admin - Services'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Service not found'),
        ]
    )]
    public function destroy(Service $service): JsonResponse
    {
        $this->serviceService->deleteService($service);
        
        return response()->json([
            'message' => 'Service deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/services/bulk',
        summary: 'Perform bulk actions on services',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkServiceRequest')
        ),
        tags: ['Admin - Services'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bulk action completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'processed', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAction(BulkServiceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $services = Service::whereIn('id', $validated['ids'])->get();
        
        $processed = 0;
        
        foreach ($services as $service) {
            match ($validated['action']) {
                'delete' => $this->serviceService->deleteService($service),
                'activate' => $service->update(['status' => true]),
                'deactivate' => $service->update(['status' => false]),
            };
            $processed++;
        }
        
        return response()->json([
            'message' => "Bulk action '{$validated['action']}' completed successfully",
            'processed' => $processed,
        ]);
    }
}
