<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Requests\Subscription\CompleteSubscriptionRequest;
use App\Http\Requests\Subscription\InitiateSubscriptionRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Coupon;
use App\Models\PaymentGateway;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\User;
use App\Services\PricePlanService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * Subscription Controller
 *
 * Handles user subscription operations including initiation, completion,
 * renewal, upgrade, and cancellation.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Subscriptions',
    description: 'User subscription management endpoints (Guard: api_user)'
)]
final class SubscriptionController extends BaseApiController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly PricePlanService $pricePlanService,
    ) {}

    /**
     * Get authenticated user.
     */
    private function getUser(): ?User
    {
        return Auth::guard('api_user')->user();
    }

    /**
     * Get user's current active subscription.
     */
    #[OA\Get(
        path: '/api/v1/subscriptions/current',
        summary: 'Get current subscription',
        description: 'Get the authenticated user\'s currently active subscription',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Response(
        response: 200,
        description: 'Current subscription retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Current subscription retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubscriptionResource', nullable: true),
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
    public function current(): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $subscription = $this->subscriptionService->getCurrentSubscription($user);

        if ($subscription === null) {
            return $this->success(null, 'No active subscription found');
        }

        return $this->success(
            new SubscriptionResource($subscription),
            'Current subscription retrieved'
        );
    }

    /**
     * Get user's subscription history.
     */
    #[OA\Get(
        path: '/api/v1/subscriptions/history',
        summary: 'Get subscription history',
        description: 'Get paginated subscription history for the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Subscription history retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription history retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SubscriptionResource')
                ),
                new OA\Property(property: 'pagination', type: 'object'),
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
    public function history(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $perPage = min((int) $request->query('per_page', $this->perPage), 100);
        $history = $this->subscriptionService->getSubscriptionHistory($user, $perPage);

        return $this->paginated(
            $history,
            SubscriptionResource::class,
            'Subscription history retrieved'
        );
    }

    /**
     * Initiate a new subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/initiate',
        summary: 'Initiate subscription',
        description: 'Start the subscription flow. Returns payment options and a pending payment log.',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/InitiateSubscriptionRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Subscription initiated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription initiated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'payment_log', ref: '#/components/schemas/PaymentLogResource'),
                        new OA\Property(
                            property: 'pricing',
                            properties: [
                                new OA\Property(property: 'original_price', type: 'number', format: 'float', example: 99.99),
                                new OA\Property(property: 'discount', type: 'number', format: 'float', example: 10.00),
                                new OA\Property(property: 'final_price', type: 'number', format: 'float', example: 89.99),
                                new OA\Property(property: 'coupon_applied', type: 'boolean', example: true),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'payment_gateways',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Stripe'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'stripe'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'requires_payment', type: 'boolean', example: true),
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
    public function initiate(InitiateSubscriptionRequest $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        // Get the plan
        $plan = PricePlan::find($data['plan_id']);

        if ($plan === null || !$plan->status) {
            return $this->notFound('Plan not found or inactive');
        }

        // Validate coupon if provided
        $coupon = null;
        if (!empty($data['coupon_code'])) {
            $couponResult = $this->subscriptionService->validateCoupon(
                $data['coupon_code'],
                $data['plan_id'],
                $user->id
            );

            if (!$couponResult['valid']) {
                return $this->validationError(['coupon_code' => [$couponResult['message']]]);
            }

            $coupon = $couponResult['coupon'];
        }

        // Check if user already has an active subscription for this subdomain
        $existingLog = PaymentLog::where('user_id', $user->id)
            ->where('tenant_id', $data['subdomain'])
            ->where('payment_status', 1)
            ->first();

        if ($existingLog !== null) {
            return $this->error('You already have an active subscription for this subdomain', 409);
        }

        try {
            $paymentLog = $this->subscriptionService->initiateSubscription(
                $user,
                $plan,
                $data['subdomain'],
                $data['theme'],
                $coupon,
                $data['payment_gateway'],
                $data['is_trial']
            );

            // Get pricing information
            $pricing = $this->subscriptionService->calculatePrice($plan, $coupon);

            // Get available payment gateways
            $gateways = PaymentGateway::where('status', true)
                ->select('id', 'name')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => strtolower(str_replace(' ', '_', $g->name)),
                ]);

            $requiresPayment = $pricing['final_price'] > 0 && !$data['is_trial'];

            // If it's a trial or free plan, complete immediately
            if (!$requiresPayment) {
                $this->subscriptionService->completeSubscription(
                    $paymentLog,
                    'FREE_OR_TRIAL_' . $paymentLog->track,
                    'free'
                );
                $paymentLog->refresh();
            }

            return $this->created([
                'payment_log' => new PaymentLogResource($paymentLog->load(['package', 'tenant'])),
                'pricing' => $pricing,
                'payment_gateways' => $gateways,
                'requires_payment' => $requiresPayment,
            ], $requiresPayment ? 'Subscription initiated. Complete payment to activate.' : 'Subscription created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to initiate subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Complete a subscription payment.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/complete',
        summary: 'Complete subscription',
        description: 'Complete the subscription after successful payment',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CompleteSubscriptionRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Subscription completed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription completed successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'subscription', ref: '#/components/schemas/SubscriptionResource'),
                        new OA\Property(property: 'tenant_url', type: 'string', example: 'https://mystore.example.com'),
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
        response: 404,
        description: 'Payment log not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Payment log not found'),
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
    public function complete(CompleteSubscriptionRequest $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        $paymentLog = PaymentLog::where('id', $data['payment_log_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($paymentLog === null) {
            return $this->notFound('Payment log not found');
        }

        if ($paymentLog->payment_status === 1) {
            return $this->error('This subscription is already completed', 409);
        }

        try {
            $tenant = $this->subscriptionService->completeSubscription(
                $paymentLog,
                $data['transaction_id'],
                $data['payment_gateway']
            );

            $paymentLog->refresh();

            // Build tenant URL
            $domain = $tenant->primaryDomain?->domain ?? $tenant->id;
            $tenantUrl = 'https://' . $domain . '.' . config('app.domain', 'example.com');

            return $this->success([
                'subscription' => new SubscriptionResource($paymentLog->load(['package', 'tenant'])),
                'tenant_url' => $tenantUrl,
            ], 'Subscription completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to complete subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/{subscriptionId}/cancel',
        summary: 'Cancel subscription',
        description: 'Cancel an active subscription',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Parameter(
        name: 'subscriptionId',
        in: 'path',
        required: true,
        description: 'Subscription (Payment Log) ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CancelSubscriptionRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Subscription cancelled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription cancelled successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SubscriptionResource'),
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
        response: 404,
        description: 'Subscription not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription not found'),
            ]
        )
    )]
    public function cancel(CancelSubscriptionRequest $request, int $subscriptionId): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $paymentLog = PaymentLog::where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->first();

        if ($paymentLog === null) {
            return $this->notFound('Subscription not found');
        }

        if ($paymentLog->status === 'cancelled') {
            return $this->error('This subscription is already cancelled', 409);
        }

        try {
            $data = $request->validatedData();
            $this->subscriptionService->cancelSubscription($paymentLog, $data['reason']);
            $paymentLog->refresh();

            return $this->success(
                new SubscriptionResource($paymentLog->load(['package', 'tenant'])),
                'Subscription cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Renew an existing subscription.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/{subscriptionId}/renew',
        summary: 'Renew subscription',
        description: 'Create a renewal payment for an existing subscription',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\Parameter(
        name: 'subscriptionId',
        in: 'path',
        required: true,
        description: 'Subscription (Payment Log) ID to renew',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 201,
        description: 'Renewal initiated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Renewal initiated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'payment_log', ref: '#/components/schemas/PaymentLogResource'),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(
                            property: 'payment_gateways',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
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
        response: 404,
        description: 'Subscription not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Subscription not found'),
            ]
        )
    )]
    public function renew(int $subscriptionId): JsonResponse
    {
        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        $paymentLog = PaymentLog::where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->where('payment_status', 1)
            ->first();

        if ($paymentLog === null) {
            return $this->notFound('Subscription not found');
        }

        try {
            $renewalLog = $this->subscriptionService->renewSubscription($paymentLog);

            // Get available payment gateways
            $gateways = PaymentGateway::where('status', true)
                ->select('id', 'name')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => strtolower(str_replace(' ', '_', $g->name)),
                ]);

            return $this->created([
                'payment_log' => new PaymentLogResource($renewalLog->load(['package', 'tenant'])),
                'amount' => (float) $renewalLog->package_price,
                'payment_gateways' => $gateways,
            ], 'Renewal initiated. Complete payment to extend subscription.');
        } catch (\Exception $e) {
            return $this->error('Failed to initiate renewal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upgrade to a new plan.
     */
    #[OA\Post(
        path: '/api/v1/subscriptions/upgrade',
        summary: 'Upgrade subscription',
        description: 'Upgrade current subscription to a new plan (prorated pricing)',
        security: [['sanctum_user' => []]],
        tags: ['Subscriptions']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['plan_id'],
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', example: 2, description: 'New plan ID'),
                new OA\Property(property: 'coupon_code', type: 'string', example: 'UPGRADE20', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Upgrade initiated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Upgrade initiated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'payment_log', ref: '#/components/schemas/PaymentLogResource'),
                        new OA\Property(property: 'prorated_amount', type: 'number', format: 'float', example: 50.00),
                        new OA\Property(property: 'credit_remaining', type: 'number', format: 'float', example: 30.00),
                        new OA\Property(property: 'requires_payment', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'payment_gateways',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
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
        response: 404,
        description: 'No active subscription or plan not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'No active subscription found'),
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
    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $this->getUser();

        if ($user === null) {
            return $this->unauthorized();
        }

        // Get current active subscription
        $currentSubscription = $this->subscriptionService->getCurrentSubscription($user);

        if ($currentSubscription === null) {
            return $this->notFound('No active subscription found');
        }

        // Get new plan
        $newPlan = PricePlan::find($request->input('plan_id'));

        if ($newPlan === null || !$newPlan->status) {
            return $this->notFound('Plan not found or inactive');
        }

        // Check if it's actually an upgrade
        $currentPlan = PricePlan::find($currentSubscription->package_id);
        if ($currentPlan !== null && $newPlan->price <= $currentPlan->price) {
            return $this->error('Cannot upgrade to a plan with equal or lower price', 422);
        }

        // Validate coupon if provided
        $coupon = null;
        if (!empty($request->input('coupon_code'))) {
            $couponResult = $this->subscriptionService->validateCoupon(
                $request->input('coupon_code'),
                $newPlan->id,
                $user->id
            );

            if (!$couponResult['valid']) {
                return $this->validationError(['coupon_code' => [$couponResult['message']]]);
            }

            $coupon = $couponResult['coupon'];
        }

        try {
            $result = $this->subscriptionService->upgradeSubscription(
                $currentSubscription,
                $newPlan,
                $coupon
            );

            // Get available payment gateways
            $gateways = PaymentGateway::where('status', true)
                ->select('id', 'name')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => strtolower(str_replace(' ', '_', $g->name)),
                ]);

            $requiresPayment = $result['prorated_amount'] > 0;

            return $this->created([
                'payment_log' => new PaymentLogResource($result['payment_log']->load(['package', 'tenant'])),
                'prorated_amount' => $result['prorated_amount'],
                'credit_remaining' => $result['credit_remaining'],
                'requires_payment' => $requiresPayment,
                'payment_gateways' => $gateways,
            ], $requiresPayment ? 'Upgrade initiated. Complete payment to activate.' : 'Upgrade completed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to initiate upgrade: ' . $e->getMessage(), 500);
        }
    }
}
