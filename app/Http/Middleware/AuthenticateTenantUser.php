<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate Tenant User Middleware
 *
 * Validates tenant user Sanctum token and requires tenant context.
 * The tenant context can be provided via:
 * - Route parameter: /tenant/{tenant}/...
 * - Header: X-Tenant-ID
 * - Token name: tenant-{id}-token
 *
 * Returns JSON 401 response if invalid or 400 if tenant context missing.
 *
 * Usage: Route::middleware('auth.tenant_user')
 */
final class AuthenticateTenantUser
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param string ...$abilities Optional required abilities
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->unauthorizedResponse('Unauthenticated. Please login.');
        }

        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if (!$token) {
            return $this->unauthorizedResponse('Invalid authentication token.');
        }

        // Check token expiration
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return $this->unauthorizedResponse('Token has expired. Please login again.');
        }

        // Extract and validate tenant context
        $tenantId = $this->extractTenantId($request, $token);

        if (!$tenantId) {
            return $this->badRequestResponse('Tenant context is required.');
        }

        // Validate token has access to this tenant
        if (!$this->tokenHasTenantAccess($token, $tenantId)) {
            return $this->forbiddenResponse('Token does not have access to this tenant.');
        }

        // Validate tenant exists
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return $this->notFoundResponse('Tenant not found.');
        }

        // Check required abilities
        if (!empty($abilities)) {
            $hasRequiredAbility = false;

            foreach ($abilities as $ability) {
                if ($token->can($ability) || $token->can('tenant-user:basic-access')) {
                    $hasRequiredAbility = true;
                    break;
                }
            }

            if (!$hasRequiredAbility) {
                return $this->forbiddenResponse(
                    'Insufficient permissions.',
                    $abilities
                );
            }
        }

        // Update last used timestamp
        $token->forceFill(['last_used_at' => now()])->save();

        // Set context in request attributes
        $request->attributes->set('tenant_user', $user);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }

    /**
     * Extract tenant ID from request or token.
     *
     * @param Request $request
     * @param PersonalAccessToken $token
     * @return string|null
     */
    private function extractTenantId(Request $request, PersonalAccessToken $token): ?string
    {
        // Priority 1: Route parameter
        $tenantId = $request->route('tenant');
        if ($tenantId) {
            return (string) $tenantId;
        }

        // Priority 2: Header
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return $tenantId;
        }

        // Priority 3: Token name (tenant-{id}-token)
        if (preg_match('/^tenant-(.+)-token$/', $token->name, $matches)) {
            return $matches[1];
        }

        // Priority 4: Token abilities (tenant:{id})
        foreach ($token->abilities as $ability) {
            if (preg_match('/^tenant:(.+)$/', $ability, $matches)) {
                return $matches[1];
            }
        }

        // Priority 5: Subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            return $parts[0];
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
        if (in_array("tenant:{$tenantId}", $token->abilities)) {
            return true;
        }

        // Check token name matches tenant
        if ($token->name === "tenant-{$tenantId}-token") {
            return true;
        }

        return false;
    }

    /**
     * Return unauthorized JSON response.
     *
     * @param string $message
     * @return Response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'TENANT_USER_UNAUTHENTICATED',
        ], 401);
    }

    /**
     * Return forbidden JSON response.
     *
     * @param string $message
     * @param array<string> $requiredAbilities
     * @return Response
     */
    private function forbiddenResponse(string $message, array $requiredAbilities = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => 'TENANT_USER_FORBIDDEN',
        ];

        if (!empty($requiredAbilities)) {
            $response['required_abilities'] = $requiredAbilities;
        }

        return response()->json($response, 403);
    }

    /**
     * Return bad request JSON response.
     *
     * @param string $message
     * @return Response
     */
    private function badRequestResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'TENANT_CONTEXT_REQUIRED',
        ], 400);
    }

    /**
     * Return not found JSON response.
     *
     * @param string $message
     * @return Response
     */
    private function notFoundResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'TENANT_NOT_FOUND',
        ], 404);
    }
}

