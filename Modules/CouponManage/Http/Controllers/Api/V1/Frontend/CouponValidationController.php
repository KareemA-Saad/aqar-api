<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CouponManage\Entities\ProductCoupon;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

/**
 * Frontend Coupon Validation Controller
 */
#[OA\Tag(name: 'Coupons', description: 'Coupon validation for checkout')]
class CouponValidationController extends Controller
{
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/coupons/validate',
        summary: 'Validate a coupon code',
        tags: ['Coupons'],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'SUMMER2024'),
                    new OA\Property(property: 'cart_total', type: 'number', example: 99.99)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Coupon validation result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'valid', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'discount', type: 'number'),
                        new OA\Property(property: 'discount_type', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'cart_total' => 'nullable|numeric|min:0',
        ]);

        $coupon = ProductCoupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => true,
                'valid' => false,
                'message' => __('Invalid coupon code'),
            ]);
        }

        if (!$coupon->status) {
            return response()->json([
                'success' => true,
                'valid' => false,
                'message' => __('This coupon is inactive'),
            ]);
        }

        if ($coupon->expire_date && Carbon::parse($coupon->expire_date)->isPast()) {
            return response()->json([
                'success' => true,
                'valid' => false,
                'message' => __('This coupon has expired'),
            ]);
        }

        $cartTotal = $request->input('cart_total', 0);
        $discountAmount = $coupon->discount_type === 'percentage'
            ? ($cartTotal * $coupon->discount / 100)
            : $coupon->discount;

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => __('Coupon applied successfully'),
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'title' => $coupon->title,
                'discount' => $coupon->discount,
                'discount_type' => $coupon->discount_type,
                'discount_on' => $coupon->discount_on,
            ],
            'discount_amount' => round($discountAmount, 2),
        ]);
    }
}
