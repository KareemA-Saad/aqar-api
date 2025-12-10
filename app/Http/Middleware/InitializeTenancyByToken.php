<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize Tenancy By Token Middleware
 *
 * Resolves tenant context from multiple sources (in priority order):
 * 1. Sanctum token abilities (tenant:{id})
 * 2. X-Tenant-ID header
 * 3. Route parameter
 * 4. Query parameter
 *
 * Once identified, initializes the tenancy context for database switching.
 *
 * Usage: Route::middleware('tenancy.token')
 */
final class InitializeTenancyByToken
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param bool $optional If true, allows request to proceed without tenant
     * @return Response
     */
    public function handle(Request $request, Closure $next, bool $optional = false): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if (!$tenantId) {
            if ($optional) {
                return $next($request);
            }

            return $this->tenantRequiredResponse();
        }

        $tenant = $this->findTenant($tenantId);

        if (!$tenant) {
            if ($optional) {
                return $next($request);
            }

            return $this->tenantNotFoundResponse($tenantId);
        }

        // Validate user has access to this tenant (if authenticated)
        if (!$this->userHasAccessToTenant($request, $tenant)) {
            return $this->accessDeniedResponse($tenantId);
        }

        // Initialize tenancy
        $this->initializeTenancy($tenant);

        // Store tenant in request attributes for easy access
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        return $next($request);
    }

    /**
     * Resolve tenant ID from request using configured resolvers.
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveTenantId(Request $request): ?string
    {
        $resolvers = config('tenancy.identification.resolvers', ['token', 'header', 'route', 'query']);

        foreach ($resolvers as $resolver) {
            $tenantId = match ($resolver) {
                'token' => $this->resolveFromToken($request),
                'header' => $this->resolveFromHeader($request),
                'route' => $this->resolveFromRoute($request),
                'query' => $this->resolveFromQuery($request),
                default => null,
            };

            if ($tenantId) {
                return $tenantId;
            }
        }

        return null;
    }

    /**
     * Extract tenant ID from Sanctum token abilities.
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveFromToken(Request $request): ?string
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if (!$token) {
            return null;
        }

        // Check token name pattern: tenant-{id}-token
        if (preg_match('/^tenant-(.+)-token$/', $token->name, $matches)) {
            return $matches[1];
        }

        // Check token abilities: tenant:{id}
        foreach ($token->abilities as $ability) {
            if (preg_match('/^tenant:(.+)$/', $ability, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract tenant ID from request header.
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveFromHeader(Request $request): ?string
    {
        $headerName = config('tenancy.identification.header', 'X-Tenant-ID');
        $tenantId = $request->header($headerName);

        return $tenantId ? (string) $tenantId : null;
    }

    /**
     * Extract tenant ID from route parameter.
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveFromRoute(Request $request): ?string
    {
        $paramName = config('tenancy.identification.route_parameter', 'tenant');
        $tenantId = $request->route($paramName);

        return $tenantId ? (string) $tenantId : null;
    }

    /**
     * Extract tenant ID from query parameter.
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveFromQuery(Request $request): ?string
    {
        $paramName = config('tenancy.identification.query_parameter', 'tenant');
        $tenantId = $request->query($paramName);

        return $tenantId ? (string) $tenantId : null;
    }

    /**
     * Find tenant by ID.
     *
     * @param string $tenantId
     * @return Tenant|null
     */
    private function findTenant(string $tenantId): ?Tenant
    {
        return Tenant::find($tenantId);
    }

    /**
     * Check if authenticated user has access to tenant.
     *
     * @param Request $request
     * @param Tenant $tenant
     * @return bool
     */
    private function userHasAccessToTenant(Request $request, Tenant $tenant): bool
    {
        $user = $request->user();

        // No user = guest access (might be allowed for public tenant endpoints)
        if (!$user) {
            return true;
        }

        // Check if user owns the tenant (for api_user guard)
        if ($user instanceof \App\Models\User) {
            return $tenant->user_id === $user->id;
        }

        // Admin has access to all tenants
        if ($user instanceof \App\Models\Admin) {
            return true;
        }

        // Tenant user - check token has tenant ability
        if ($user instanceof \App\Models\TenantUser) {
            $token = $user->currentAccessToken();

            if (!$token) {
                return false;
            }

            // Check for wildcard or specific tenant ability
            return in_array('*', $token->abilities)
                || in_array("tenant:{$tenant->id}", $token->abilities);
        }

        return false;
    }

    /**
     * Initialize tenancy context.
     *
     * @param Tenant $tenant
     * @return void
     */
    private function initializeTenancy(Tenant $tenant): void
    {
        $this->tenancy->initialize($tenant);
    }

    /**
     * Return tenant required response.
     *
     * @return Response
     */
    private function tenantRequiredResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant context is required. Provide tenant ID via X-Tenant-ID header, route parameter, or token.',
            'error_code' => 'TENANT_REQUIRED',
        ], 400);
    }

    /**
     * Return tenant not found response.
     *
     * @param string $tenantId
     * @return Response
     */
    private function tenantNotFoundResponse(string $tenantId): Response
    {
        return response()->json([
            'success' => false,
            'message' => "Tenant not found: {$tenantId}",
            'error_code' => 'TENANT_NOT_FOUND',
        ], 404);
    }

    /**
     * Return access denied response.
     *
     * @param string $tenantId
     * @return Response
     */
    private function accessDeniedResponse(string $tenantId): Response
    {
        return response()->json([
            'success' => false,
            'message' => "Access denied to tenant: {$tenantId}",
            'error_code' => 'TENANT_ACCESS_DENIED',
        ], 403);
    }
}

