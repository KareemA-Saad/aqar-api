<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Package Expiry Middleware
 *
 * Checks if the tenant's subscription package is expired.
 * Returns 402 (Payment Required) with expiry details if expired.
 * Allows a configurable grace period for renewals.
 *
 * Usage: Route::middleware('package.active')
 * Usage with grace: Route::middleware('package.active:7') // 7 days grace
 */
final class CheckPackageExpiry
{
    /**
     * Default grace period in days.
     */
    private const DEFAULT_GRACE_DAYS = 3;

    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param int|null $graceDays Grace period days (optional)
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?int $graceDays = null): Response
    {
        // Get current tenant
        $tenant = $this->getCurrentTenant($request);

        if (!$tenant) {
            // No tenant context - let EnsureTenantContext handle this
            return $next($request);
        }

        // Get the latest payment log
        $paymentLog = $tenant->paymentLog;

        if (!$paymentLog) {
            return $this->noSubscriptionResponse();
        }

        // Check expiration
        $expireDate = $paymentLog->expire_date;

        // Lifetime plans have no expiration
        if ($expireDate === null) {
            return $next($request);
        }

        $gracePeriod = $graceDays ?? self::DEFAULT_GRACE_DAYS;
        $now = Carbon::now();
        $expireDateCarbon = Carbon::parse($expireDate);

        // Check if expired
        if ($expireDateCarbon->isPast()) {
            // Check if within grace period
            $daysPastExpiry = $expireDateCarbon->diffInDays($now);

            if ($daysPastExpiry <= $gracePeriod) {
                // Within grace period - allow access but warn
                return $this->addExpiryWarningToResponse(
                    $next($request),
                    $expireDateCarbon,
                    $gracePeriod - $daysPastExpiry,
                    true
                );
            }

            // Fully expired
            return $this->expiredResponse($expireDateCarbon, $paymentLog);
        }

        // Not expired - check if expiring soon (within 7 days)
        $daysUntilExpiry = $now->diffInDays($expireDateCarbon, false);

        if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
            return $this->addExpiryWarningToResponse(
                $next($request),
                $expireDateCarbon,
                (int) $daysUntilExpiry,
                false
            );
        }

        return $next($request);
    }

    /**
     * Get current tenant from request or tenancy.
     *
     * @param Request $request
     * @return Tenant|null
     */
    private function getCurrentTenant(Request $request): ?Tenant
    {
        // Try from request attributes first
        $tenant = $request->attributes->get('tenant');

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        // Try from tenancy
        if ($this->tenancy->initialized && $this->tenancy->tenant instanceof Tenant) {
            return $this->tenancy->tenant;
        }

        return null;
    }

    /**
     * Return no subscription response.
     *
     * @return Response
     */
    private function noSubscriptionResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'No active subscription found. Please purchase a plan to continue.',
            'error_code' => 'NO_SUBSCRIPTION',
            'action_required' => 'purchase_plan',
        ], 402);
    }

    /**
     * Return expired subscription response.
     *
     * @param Carbon $expireDate
     * @param mixed $paymentLog
     * @return Response
     */
    private function expiredResponse(Carbon $expireDate, $paymentLog): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Your subscription has expired. Please renew to continue accessing this resource.',
            'error_code' => 'SUBSCRIPTION_EXPIRED',
            'details' => [
                'expired_at' => $expireDate->toISOString(),
                'days_expired' => (int) $expireDate->diffInDays(Carbon::now()),
                'package_name' => $paymentLog->package_name ?? null,
                'package_id' => $paymentLog->package_id ?? null,
            ],
            'action_required' => 'renew_subscription',
        ], 402);
    }

    /**
     * Add expiry warning headers to response.
     *
     * @param Response $response
     * @param Carbon $expireDate
     * @param int $daysRemaining
     * @param bool $inGracePeriod
     * @return Response
     */
    private function addExpiryWarningToResponse(
        Response $response,
        Carbon $expireDate,
        int $daysRemaining,
        bool $inGracePeriod
    ): Response {
        $response->headers->set('X-Subscription-Expires', $expireDate->toISOString());
        $response->headers->set('X-Subscription-Days-Remaining', (string) max(0, $daysRemaining));

        if ($inGracePeriod) {
            $response->headers->set('X-Subscription-Grace-Period', 'true');
            $response->headers->set('X-Subscription-Status', 'grace-period');
        } else {
            $response->headers->set('X-Subscription-Status', 'expiring-soon');
        }

        return $response;
    }
}

