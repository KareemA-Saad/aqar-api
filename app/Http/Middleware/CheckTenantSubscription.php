<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Tenant Subscription Middleware
 *
 * Validates that the tenant has an active subscription before allowing access.
 * This middleware should be applied after tenant context is resolved.
 *
 * Returns:
 * - 402 Payment Required: When subscription is expired
 * - 403 Forbidden: When tenant is suspended
 */
final class CheckTenantSubscription
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get tenant from request (set by previous middleware)
        $tenant = $this->resolveTenant($request);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context not found',
                'error_code' => 'TENANT_NOT_FOUND',
            ], 404);
        }

        // Check subscription status
        $status = $this->subscriptionService->getSubscriptionStatus($tenant);

        // Handle different statuses
        return match ($status) {
            SubscriptionService::STATUS_SUSPENDED => $this->suspendedResponse($tenant),
            SubscriptionService::STATUS_EXPIRED => $this->expiredResponse($tenant),
            default => $next($request),
        };
    }

    /**
     * Resolve tenant from request.
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        // Try to get from tenancy()
        if (function_exists('tenancy') && tenancy()->tenant) {
            return tenancy()->tenant;
        }

        // Try to get from request attributes (set by previous middleware)
        if ($request->attributes->has('tenant')) {
            return $request->attributes->get('tenant');
        }

        // Try to resolve from route parameter
        $tenantId = $request->route('tenant');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        // Try from header
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        return null;
    }

    /**
     * Response for suspended tenants.
     */
    private function suspendedResponse(Tenant $tenant): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'This tenant has been suspended.',
            'error_code' => 'TENANT_SUSPENDED',
            'data' => [
                'tenant_id' => $tenant->id,
                'status' => 'suspended',
                'suspended_at' => $tenant->suspended_at?->toISOString(),
                'reason' => $tenant->suspension_reason ?? 'Contact administrator for more information.',
            ],
        ], 403);
    }

    /**
     * Response for expired subscriptions.
     */
    private function expiredResponse(Tenant $tenant): Response
    {
        $paymentLog = $tenant->paymentLog;
        $expiredAt = null;

        if ($paymentLog) {
            $expiredAt = $paymentLog->status === 'trial'
                ? $paymentLog->trial_expire_date?->toISOString()
                : $paymentLog->expire_date?->toISOString();
        }

        return response()->json([
            'success' => false,
            'message' => 'Subscription has expired. Please renew to continue using the service.',
            'error_code' => 'SUBSCRIPTION_EXPIRED',
            'data' => [
                'tenant_id' => $tenant->id,
                'status' => 'expired',
                'expired_at' => $expiredAt,
                'renewal_url' => config('app.frontend_url') . '/subscription/renew',
            ],
        ], 402);
    }
}
