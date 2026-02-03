<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Module Limit Middleware
 *
 * Validates that the tenant has not exceeded their plan limit for a specific module
 * before allowing creation of new items.
 *
 * Usage:
 * - Route::middleware('limit:blog')->post('blogs', ...);
 * - Route::middleware('limit:product')->post('products', ...);
 *
 * This middleware only applies to POST (create) requests.
 * For other HTTP methods, the request passes through without limit checks.
 */
final class CheckModuleLimit
{
    public function __construct(
        private readonly PlanLimitService $limitService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param string $module The module to check limit for (e.g., 'blog', 'product')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // Only check limits for POST (create) requests
        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        // Get tenant from request context
        $tenant = $this->resolveTenant($request);

        if ($tenant === null) {
            // No tenant context - let other middleware handle this
            return $next($request);
        }

        // Check if module is supported
        if (!PlanLimitService::isModuleSupported($module)) {
            // Unknown module - allow through (fail open for non-configured modules)
            return $next($request);
        }

        // Check if tenant can create in this module
        if (!$this->limitService->canCreate($tenant, $module)) {
            return $this->limitExceededResponse($tenant, $module);
        }

        return $next($request);
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

        // Try to get from request attributes
        if ($request->attributes->has('tenant')) {
            return $request->attributes->get('tenant');
        }

        // Try to resolve from route parameter
        $tenantId = $request->route('tenant');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        return null;
    }

    /**
     * Return limit exceeded response.
     */
    private function limitExceededResponse(Tenant $tenant, string $module): Response
    {
        $data = $this->limitService->getLimitExceededData($tenant, $module);

        return response()->json([
            'success' => false,
            'message' => $data['message'],
            'error_code' => 'PLAN_LIMIT_EXCEEDED',
            'data' => [
                'module' => $data['module'],
                'limit' => $data['limit'],
                'current' => $data['current'],
                'upgrade_url' => config('app.frontend_url') . '/subscription/upgrade',
            ],
        ], 403);
    }
}
