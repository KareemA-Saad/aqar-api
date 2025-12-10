<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate User Middleware
 *
 * Validates user Sanctum token and loads tenant context if user has tenants.
 * Returns JSON 401 response if invalid.
 *
 * Usage: Route::middleware('auth.user')
 */
final class AuthenticateUser
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
        $user = auth('api_user')->user();

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

        // Check required abilities
        if (!empty($abilities)) {
            $hasRequiredAbility = false;

            // Check for wildcard access
            if ($token->can('*')) {
                $hasRequiredAbility = true;
            } else {
                foreach ($abilities as $ability) {
                    if ($token->can($ability)) {
                        $hasRequiredAbility = true;
                        break;
                    }
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

        // Set user in request attributes
        $request->attributes->set('user', $user);

        // Load tenant context if user has tenants
        $this->loadTenantContext($request, $user);

        return $next($request);
    }

    /**
     * Load tenant context if user has tenants.
     *
     * @param Request $request
     * @param \App\Models\User $user
     * @return void
     */
    private function loadTenantContext(Request $request, $user): void
    {
        // Check if a specific tenant is requested via header or route
        $requestedTenantId = $request->header('X-Tenant-ID') ?? $request->route('tenant');

        if ($requestedTenantId) {
            // Validate user has access to this tenant
            $tenant = Tenant::where('id', $requestedTenantId)
                ->where('user_id', $user->id)
                ->first();

            if ($tenant) {
                $request->attributes->set('tenant', $tenant);
                $request->attributes->set('tenant_id', $tenant->id);
            }
        } else {
            // Load user's first/default tenant if they have one
            $defaultTenant = $user->tenants()->first();

            if ($defaultTenant) {
                $request->attributes->set('tenant', $defaultTenant);
                $request->attributes->set('tenant_id', $defaultTenant->id);
            }
        }

        // Also set tenant list for easy access
        $request->attributes->set('user_tenants', $user->tenants);
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
            'error_code' => 'USER_UNAUTHENTICATED',
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
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'USER_FORBIDDEN',
            'required_abilities' => $requiredAbilities,
        ], 403);
    }
}

