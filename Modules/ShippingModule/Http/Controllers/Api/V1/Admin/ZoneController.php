<?php

declare(strict_types=1);

namespace Modules\ShippingModule\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ShippingModule\Entities\Zone;
use Modules\ShippingModule\Entities\ZoneRegion;
use Modules\ShippingModule\Http\Resources\ZoneResource;
use OpenApi\Attributes as OA;

/**
 * Admin Zone Controller
 */
#[OA\Tag(name: 'Admin - Shipping Zones', description: 'Shipping zone management')]
class ZoneController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/shipping/zones',
        summary: 'List all shipping zones',
        tags: ['Admin - Shipping Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Zones retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $zones = Zone::with('region')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Shipping zones retrieved successfully'),
            'data' => ZoneResource::collection($zones),
            'meta' => [
                'current_page' => $zones->currentPage(),
                'last_page' => $zones->lastPage(),
                'per_page' => $zones->perPage(),
                'total' => $zones->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/shipping/zones',
        summary: 'Create a new shipping zone',
        tags: ['Admin - Shipping Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'North America'),
                    new OA\Property(property: 'country', type: 'string', example: 'US'),
                    new OA\Property(property: 'state', type: 'string', example: 'CA')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Zone created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
        ]);

        $zone = Zone::create(['name' => $data['name']]);

        if (!empty($data['country']) || !empty($data['state'])) {
            ZoneRegion::create([
                'zone_id' => $zone->id,
                'country' => $data['country'] ?? null,
                'state' => $data['state'] ?? null,
            ]);
        }

        $zone->load('region');

        return response()->json([
            'success' => true,
            'message' => __('Shipping zone created successfully'),
            'data' => new ZoneResource($zone),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/shipping/zones/{id}',
        summary: 'Get a specific shipping zone',
        tags: ['Admin - Shipping Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Zone retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $zone = Zone::with('region')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Shipping zone retrieved successfully'),
            'data' => new ZoneResource($zone),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/shipping/zones/{id}',
        summary: 'Update a shipping zone',
        tags: ['Admin - Shipping Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Zone updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
        ]);

        if (isset($data['name'])) {
            $zone->update(['name' => $data['name']]);
        }

        if (isset($data['country']) || isset($data['state'])) {
            ZoneRegion::updateOrCreate(
                ['zone_id' => $zone->id],
                [
                    'country' => $data['country'] ?? $zone->region?->country,
                    'state' => $data['state'] ?? $zone->region?->state,
                ]
            );
        }

        $zone->load('region');

        return response()->json([
            'success' => true,
            'message' => __('Shipping zone updated successfully'),
            'data' => new ZoneResource($zone),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/shipping/zones/{id}',
        summary: 'Delete a shipping zone',
        tags: ['Admin - Shipping Zones'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Zone deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);
        ZoneRegion::where('zone_id', $zone->id)->delete();
        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => __('Shipping zone deleted successfully'),
        ]);
    }
}
