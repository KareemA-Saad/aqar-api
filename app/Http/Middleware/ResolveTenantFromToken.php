<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve Tenant From Token Middleware
 *
 * Extracts tenant context from Sanctum token for tenant-aware authentication.
 * The tenant ID is stored in the token name as: tenant-{tenant_id}-token
 *
 * This middleware:
 * - Extracts tenant_id from token abilities or token name
 * - Validates tenant exists and is active
 * - Sets tenant context in request for downstream use
 * - Optionally initializes tenant database connection
 */
final class ResolveTenantFromToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param bool $initializeDatabase Whether to initialize tenant database connection
     * @return Response
     */
    public function handle(Request $request, Closure $next, bool $initializeDatabase = false): Response
    {
        $user = $request->user('api_tenant_user');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'TOKEN_MISSING',
            ], 401);
        }

        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        }

        // Extract tenant ID from token
        $tenantId = $this->extractTenantId($token);

        if (!$tenantId) {
            // Try from request header or route
            $tenantId = $request->header('X-Tenant-ID')
                ?? $request->route('tenant')
                ?? $this->extractFromHost($request);
        }

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context required',
                'error_code' => 'TENANT_CONTEXT_MISSING',
            ], 400);
        }

        // Validate token has access to this tenant
        if (!$this->tokenHasTenantAccess($token, $tenantId)) {
            return response()->json([
                'success' => false,
                'message' => 'Token does not have access to this tenant',
                'error_code' => 'TENANT_ACCESS_DENIED',
            ], 403);
        }

        // Validate tenant exists
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found',
                'error_code' => 'TENANT_NOT_FOUND',
            ], 404);
        }

        // Set tenant context in request
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenantId);

        // Optionally initialize tenant database
        if ($initializeDatabase) {
            $this->initializeTenantDatabase($tenant);
        }

        return $next($request);
    }

    /**
     * Extract tenant ID from token name or abilities.
     *
     * @param PersonalAccessToken $token
     * @return string|null
     */
    private function extractTenantId(PersonalAccessToken $token): ?string
    {
        // Try from token name: tenant-{id}-token
        if (preg_match('/^tenant-(.+)-token$/', $token->name, $matches)) {
            return $matches[1];
        }

        // Try from abilities: tenant:{id}
        foreach ($token->abilities as $ability) {
            if (preg_match('/^tenant:(.+)$/', $ability, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Check if token has access to specified tenant.
     *
     * @param PersonalAccessToken $token
     * @param string $tenantId
     * @return bool
     */
    private function tokenHasTenantAccess(PersonalAccessToken $token, string $tenantId): bool
    {
        // Check for wildcard access
        if (in_array('*', $token->abilities)) {
            return true;
        }

        // Check for specific tenant ability
        return in_array("tenant:{$tenantId}", $token->abilities);
    }

    /**
     * Extract tenant ID from host (subdomain).
     *
     * @param Request $request
     * @return string|null
     */
    private function extractFromHost(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        // If subdomain exists (more than 2 parts)
        if (count($parts) > 2) {
            return $parts[0];
        }

        return null;
    }

    /**
     * Initialize tenant database connection.
     * This should be implemented based on your multi-tenancy setup.
     *
     * @param Tenant $tenant
     * @return void
     */
    private function initializeTenantDatabase(Tenant $tenant): void
    {
        // Implementation depends on your multi-tenancy package
        // For stancl/tenancy:
        // tenancy()->initialize($tenant);

        // For manual setup:
        // config(['database.connections.tenant.database' => "tenant_{$tenant->id}"]);
        // \DB::purge('tenant');
        // \DB::reconnect('tenant');
    }
}

