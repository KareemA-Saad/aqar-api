<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\PricePlanCollection;
use App\Http\Resources\PricePlanResource;
use App\Services\PricePlanService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * Public Plan Controller
 *
 * Handles public-facing price plan operations.
 * These endpoints are accessible without authentication or with user authentication.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Price Plans (Public)',
    description: 'Public endpoints for viewing pricing plans and comparing features'
)]
final class PlanController extends BaseApiController
{
    public function __construct(
        private readonly PricePlanService $pricePlanService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Get all active price plans.
     */
    #[OA\Get(
        path: '/api/v1/plans',
        summary: 'List active plans',
        description: 'Get all active price plans with features for public display',
        tags: ['Price Plans (Public)']
    )]
    #[OA\Response(
        response: 200,
        description: 'Active plans retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Plans retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PricePlanResource')
                        ),
                        new OA\Property(
                            property: 'summary',
                            properties: [
                                new OA\Property(property: 'total_plans', type: 'integer', example: 5),
                                new OA\Property(property: 'active_plans', type: 'integer', example: 4),
                                new OA\Property(property: 'has_trial_plans', type: 'integer', example: 2),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function index(): JsonResponse
    {
        $plans = $this->pricePlanService->getActivePlans();

        return $this->success(
            new PricePlanCollection($plans),
            'Plans retrieved successfully'
        );
    }

    /**
     * Get plan details by slug.
     */
    #[OA\Get(
        path: '/api/v1/plans/{slug}',
        summary: 'Get plan details',
        description: 'Get details of a specific plan by slug for purchase page',
        tags: ['Price Plans (Public)']
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        description: 'Plan slug (URL-friendly title)',
        schema: new OA\Schema(type: 'string', example: 'premium-plan')
    )]
    #[OA\Response(
        response: 200,
        description: 'Plan retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Plan retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/PricePlanResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Plan not found'),
            ]
        )
    )]
    public function show(string $slug): JsonResponse
    {
        // Try to find by ID first (for numeric slugs)
        if (is_numeric($slug)) {
            $plan = $this->pricePlanService->getPlanById((int) $slug);
            if ($plan !== null && !$plan->status) {
                $plan = null; // Don't show inactive plans
            }
        } else {
            $plan = $this->pricePlanService->getPlanBySlug($slug);
        }

        if ($plan === null) {
            return $this->notFound('Plan not found');
        }

        return $this->success(
            new PricePlanResource($plan),
            'Plan retrieved successfully'
        );
    }

    /**
     * Get comparison matrix of all active plans.
     */
    #[OA\Get(
        path: '/api/v1/plans/compare',
        summary: 'Compare all plans',
        description: 'Get a comparison matrix of all active plans with feature availability',
        tags: ['Price Plans (Public)']
    )]
    #[OA\Response(
        response: 200,
        description: 'Plan comparison retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Plan comparison retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'plans',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Premium Plan'),
                                    new OA\Property(property: 'subtitle', type: 'string', example: 'Best for businesses'),
                                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                    new OA\Property(property: 'type', type: 'integer', example: 0),
                                    new OA\Property(property: 'type_label', type: 'string', example: 'Monthly'),
                                    new OA\Property(property: 'has_trial', type: 'boolean', example: true),
                                    new OA\Property(property: 'trial_days', type: 'integer', example: 14),
                                    new OA\Property(property: 'permissions', type: 'object'),
                                    new OA\Property(
                                        property: 'features',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'name', type: 'string', example: 'eCommerce'),
                                                new OA\Property(property: 'included', type: 'boolean', example: true),
                                            ],
                                            type: 'object'
                                        )
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'feature_names',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['eCommerce', 'Blog', 'Analytics']
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function compare(): JsonResponse
    {
        $comparison = $this->pricePlanService->getComparisonMatrix();

        return $this->success($comparison, 'Plan comparison retrieved successfully');
    }

    /**
     * Validate a coupon code.
     */
    #[OA\Post(
        path: '/api/v1/plans/check-coupon',
        summary: 'Validate coupon',
        description: 'Check if a coupon code is valid for a plan',
        security: [['sanctum_user' => []]],
        tags: ['Price Plans (Public)']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code', 'plan_id'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'SAVE20', description: 'Coupon code'),
                new OA\Property(property: 'plan_id', type: 'integer', example: 1, description: 'Plan ID'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Coupon validation result',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Coupon is valid'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean', example: true),
                        new OA\Property(property: 'discount', type: 'number', format: 'float', example: 20.00),
                        new OA\Property(property: 'discount_type', type: 'string', example: 'percentage'),
                        new OA\Property(property: 'original_price', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(property: 'final_price', type: 'number', format: 'float', example: 79.99),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function checkCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
        ]);

        $userId = Auth::guard('api_user')->id();

        if ($userId === null) {
            return $this->unauthorized('Please log in to check coupon');
        }

        $result = $this->subscriptionService->validateCoupon(
            $request->input('code'),
            (int) $request->input('plan_id'),
            (int) $userId
        );

        if (!$result['valid']) {
            return $this->success([
                'valid' => false,
                'message' => $result['message'],
                'discount' => 0,
            ], $result['message']);
        }

        $plan = $this->pricePlanService->getPlanById((int) $request->input('plan_id'));

        return $this->success([
            'valid' => true,
            'discount' => $result['discount'],
            'discount_type' => $result['coupon']->discount_type,
            'discount_amount' => (float) $result['coupon']->discount_amount,
            'original_price' => (float) $plan->price,
            'final_price' => max(0, (float) $plan->price - $result['discount']),
        ], 'Coupon is valid');
    }
}
