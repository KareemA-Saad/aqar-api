<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CouponManage\Entities\ProductCoupon;
use Modules\CouponManage\Http\Resources\CouponResource;
use OpenApi\Attributes as OA;

/**
 * Admin Coupon Controller
 */
#[OA\Tag(name: 'Admin - Coupons', description: 'Coupon management endpoints')]
class CouponController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/coupons',
        summary: 'List all coupons',
        tags: ['Admin - Coupons'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1]))
        ],
        responses: [new OA\Response(response: 200, description: 'Coupons retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ProductCoupon::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $coupons = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Coupons retrieved successfully'),
            'data' => CouponResource::collection($coupons),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/coupons',
        summary: 'Create a new coupon',
        tags: ['Admin - Coupons'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'code', 'discount', 'discount_type'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Summer Sale'),
                    new OA\Property(property: 'code', type: 'string', example: 'SUMMER2024'),
                    new OA\Property(property: 'discount', type: 'number', example: 10),
                    new OA\Property(property: 'discount_type', type: 'string', enum: ['percentage', 'amount']),
                    new OA\Property(property: 'discount_on', type: 'string', enum: ['all', 'category', 'product']),
                    new OA\Property(property: 'discount_on_details', type: 'string'),
                    new OA\Property(property: 'expire_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1])
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Coupon created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:product_coupons,code',
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percentage,amount',
            'discount_on' => 'nullable|in:all,category,product',
            'discount_on_details' => 'nullable|string',
            'expire_date' => 'nullable|date|after_or_equal:today',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $data['status'] = $data['status'] ?? 1;
        $coupon = ProductCoupon::create($data);

        return response()->json([
            'success' => true,
            'message' => __('Coupon created successfully'),
            'data' => new CouponResource($coupon),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/coupons/{id}',
        summary: 'Get a specific coupon',
        tags: ['Admin - Coupons'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Coupon retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $coupon = ProductCoupon::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Coupon retrieved successfully'),
            'data' => new CouponResource($coupon),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/coupons/{id}',
        summary: 'Update a coupon',
        tags: ['Admin - Coupons'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Coupon updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = ProductCoupon::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:product_coupons,code,' . $id,
            'discount' => 'sometimes|numeric|min:0',
            'discount_type' => 'sometimes|in:percentage,amount',
            'discount_on' => 'nullable|in:all,category,product',
            'discount_on_details' => 'nullable|string',
            'expire_date' => 'nullable|date',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $coupon->update($data);

        return response()->json([
            'success' => true,
            'message' => __('Coupon updated successfully'),
            'data' => new CouponResource($coupon),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/coupons/{id}',
        summary: 'Delete a coupon',
        tags: ['Admin - Coupons'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Coupon deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        ProductCoupon::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Coupon deleted successfully'),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/coupons/{id}/toggle-status',
        summary: 'Toggle coupon status',
        tags: ['Admin - Coupons'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Status toggled')]
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $coupon = ProductCoupon::findOrFail($id);
        $coupon->update(['status' => $coupon->status ? 0 : 1]);

        return response()->json([
            'success' => true,
            'message' => __('Coupon status updated'),
            'data' => new CouponResource($coupon),
        ]);
    }
}
