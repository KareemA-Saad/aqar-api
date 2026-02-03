<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Tenant Context Middleware
 *
 * Verifies that the request has a valid tenant context.
 * Returns 403 Forbidden if accessing tenant route without proper context.
 *
 * This middleware should be applied after authentication and tenant resolution.
 *
 * Usage: Route::middleware('tenant.context')
 */
final class EnsureTenantContext
{
    public function __construct(
        private readonly Tenancy $tenancy,
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
        // Check if tenancy is initialized
        if (!$this->tenancy->initialized) {
            return $this->noContextResponse();
        }

        // Get current tenant
        $tenant = $this->tenancy->tenant;

        if (!$tenant instanceof Tenant) {
            return $this->noContextResponse();
        }

        // Verify tenant is valid
        if (!$this->isValidTenant($tenant)) {
            return $this->invalidTenantResponse($tenant->id);
        }

        // Store tenant in request attributes for easy access
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        return $next($request);
    }

    /**
     * Check if tenant is valid.
     *
     * @param Tenant $tenant
     * @return bool
     */
    private function isValidTenant(Tenant $tenant): bool
    {
        // Tenant must have a user (owner)
        if (empty($tenant->user_id)) {
            return false;
        }

        // Tenant must exist in database
        if (!Tenant::find($tenant->id)) {
            return false;
        }

        return true;
    }

    /**
     * Return no context response.
     *
     * @return Response
     */
    private function noContextResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant context is required to access this resource. Please provide a valid tenant ID via X-Tenant-ID header or use a tenant-scoped token.',
            'error_code' => 'TENANT_CONTEXT_REQUIRED',
            'help' => [
                'methods' => [
                    'Use X-Tenant-ID header with your tenant ID',
                    'Switch to tenant context using POST /api/v1/tenants/{id}/switch to get a tenant-scoped token',
                    'Include tenant in the route: /api/v1/tenant/{tenant_id}/...',
                ],
            ],
        ], 403);
    }

    /**
     * Return invalid tenant response.
     *
     * @param string $tenantId
     * @return Response
     */
    private function invalidTenantResponse(string $tenantId): Response
    {
        return response()->json([
            'success' => false,
            'message' => "Invalid tenant context: {$tenantId}",
            'error_code' => 'TENANT_INVALID',
        ], 403);
    }
}

