<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CouponManage\Http\Requests\ValidateCouponRequest;
use Modules\CouponManage\Http\Resources\CouponResource;
use Modules\CouponManage\Services\CouponService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Coupon', description: 'Public coupon endpoints')]
class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/frontend/coupons/active',
        summary: 'Get active non-expired coupons',
        tags: ['Frontend - Coupon'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Active coupons retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CouponManageResource')),
                    ]
                )
            ),
        ]
    )]
    public function activeCoupons(): JsonResponse
    {
        $coupons = $this->couponService->getActiveCoupons();
        
        return response()->json([
            'success' => true,
            'data' => CouponResource::collection($coupons),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/coupons/validate',
        summary: 'Validate coupon code',
        tags: ['Frontend - Coupon'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ValidateCouponRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Coupon validation result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Coupon is valid'),
                        new OA\Property(property: 'coupon', ref: '#/components/schemas/CouponManageResource'),
                    ]
                )
            ),
        ]
    )]
    public function validateCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $result = $this->couponService->validateCoupon($request->validated()['code']);
        
        if ($result['valid']) {
            $result['coupon'] = new CouponResource($result['coupon']);
        }
        
        return response()->json(array_merge(['success' => true], $result));
    }

    #[OA\Get(
        path: '/api/v1/frontend/coupons/{code}',
        summary: 'Get coupon by code',
        tags: ['Frontend - Coupon'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Coupon retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/CouponManageResource')
            ),
            new OA\Response(response: 404, description: 'Coupon not found'),
        ]
    )]
    public function showByCode(string $code): JsonResponse
    {
        $coupon = $this->couponService->getCouponByCode($code);
        
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CouponResource($coupon),
        ]);
    }
}
