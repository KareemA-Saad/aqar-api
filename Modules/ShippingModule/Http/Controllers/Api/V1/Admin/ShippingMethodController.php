<?php

declare(strict_types=1);

namespace Modules\ShippingModule\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ShippingModule\Entities\ShippingMethod;
use Modules\ShippingModule\Entities\ShippingMethodOption;
use Modules\ShippingModule\Http\Resources\ShippingMethodResource;
use OpenApi\Attributes as OA;

/**
 * Admin Shipping Method Controller
 */
#[OA\Tag(name: 'Admin - Shipping Methods', description: 'Shipping method management')]
class ShippingMethodController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/shipping/methods',
        summary: 'List all shipping methods',
        tags: ['Admin - Shipping Methods'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'zone_id', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [new OA\Response(response: 200, description: 'Methods retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ShippingMethod::with(['options', 'zone']);

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $methods = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Shipping methods retrieved successfully'),
            'data' => ShippingMethodResource::collection($methods),
            'meta' => [
                'current_page' => $methods->currentPage(),
                'last_page' => $methods->lastPage(),
                'per_page' => $methods->perPage(),
                'total' => $methods->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/shipping/methods',
        summary: 'Create a new shipping method',
        tags: ['Admin - Shipping Methods'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'zone_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Standard Shipping'),
                    new OA\Property(property: 'zone_id', type: 'integer'),
                    new OA\Property(property: 'is_default', type: 'boolean'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'cost', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'status', type: 'integer', example: 1),
                    new OA\Property(property: 'tax_status', type: 'string'),
                    new OA\Property(property: 'minimum_order_amount', type: 'number')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Method created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => 'required|exists:zones,id',
            'is_default' => 'nullable|boolean',
            'title' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
            'tax_status' => 'nullable|string',
            'coupon' => 'nullable|string',
            'setting_preset' => 'nullable|string',
            'minimum_order_amount' => 'nullable|numeric|min:0',
        ]);

        $method = ShippingMethod::create([
            'name' => $data['name'],
            'zone_id' => $data['zone_id'],
            'is_default' => $data['is_default'] ?? false,
        ]);

        ShippingMethodOption::create([
            'shipping_method_id' => $method->id,
            'title' => $data['title'] ?? $data['name'],
            'cost' => $data['cost'] ?? 0,
            'status' => $data['status'] ?? 1,
            'tax_status' => $data['tax_status'] ?? null,
            'coupon' => $data['coupon'] ?? null,
            'setting_preset' => $data['setting_preset'] ?? null,
            'minimum_order_amount' => $data['minimum_order_amount'] ?? 0,
        ]);

        $method->load(['options', 'zone']);

        return response()->json([
            'success' => true,
            'message' => __('Shipping method created successfully'),
            'data' => new ShippingMethodResource($method),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/shipping/methods/{id}',
        summary: 'Get a specific shipping method',
        tags: ['Admin - Shipping Methods'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Method retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $method = ShippingMethod::with(['options', 'zone'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Shipping method retrieved successfully'),
            'data' => new ShippingMethodResource($method),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/shipping/methods/{id}',
        summary: 'Update a shipping method',
        tags: ['Admin - Shipping Methods'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Method updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'zone_id' => 'sometimes|exists:zones,id',
            'is_default' => 'nullable|boolean',
            'title' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|in:0,1',
            'tax_status' => 'nullable|string',
            'coupon' => 'nullable|string',
            'setting_preset' => 'nullable|string',
            'minimum_order_amount' => 'nullable|numeric|min:0',
        ]);

        $method->update(array_filter([
            'name' => $data['name'] ?? null,
            'zone_id' => $data['zone_id'] ?? null,
            'is_default' => $data['is_default'] ?? null,
        ], fn($v) => $v !== null));

        $optionData = array_filter([
            'title' => $data['title'] ?? null,
            'cost' => $data['cost'] ?? null,
            'status' => $data['status'] ?? null,
            'tax_status' => $data['tax_status'] ?? null,
            'coupon' => $data['coupon'] ?? null,
            'setting_preset' => $data['setting_preset'] ?? null,
            'minimum_order_amount' => $data['minimum_order_amount'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($optionData)) {
            ShippingMethodOption::updateOrCreate(
                ['shipping_method_id' => $method->id],
                $optionData
            );
        }

        $method->load(['options', 'zone']);

        return response()->json([
            'success' => true,
            'message' => __('Shipping method updated successfully'),
            'data' => new ShippingMethodResource($method),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/shipping/methods/{id}',
        summary: 'Delete a shipping method',
        tags: ['Admin - Shipping Methods'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Method deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);
        ShippingMethodOption::where('shipping_method_id', $method->id)->delete();
        $method->delete();

        return response()->json([
            'success' => true,
            'message' => __('Shipping method deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/shipping/methods/{id}/set-default',
        summary: 'Set shipping method as default',
        tags: ['Admin - Shipping Methods'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Default method set')]
    )]
    public function setDefault(int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);

        // Remove default from other methods in same zone
        ShippingMethod::where('zone_id', $method->zone_id)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        $method->update(['is_default' => true]);
        $method->load(['options', 'zone']);

        return response()->json([
            'success' => true,
            'message' => __('Shipping method set as default'),
            'data' => new ShippingMethodResource($method),
        ]);
    }
}
