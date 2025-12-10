<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate Admin Middleware
 *
 * Validates admin Sanctum token and ensures the user is an admin.
 * Returns JSON 401 response if invalid.
 *
 * Usage: Route::middleware('auth.admin')
 */
final class AuthenticateAdmin
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
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorizedResponse('Unauthenticated. Admin access required.');
        }

        /** @var PersonalAccessToken|null $token */
        $token = $admin->currentAccessToken();

        if (!$token) {
            return $this->unauthorizedResponse('Invalid authentication token.');
        }

        // Check token expiration
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return $this->unauthorizedResponse('Token has expired. Please login again.');
        }

        // Check for admin:full-access or specific abilities
        if (!empty($abilities)) {
            $hasRequiredAbility = false;

            // Admin with full-access can do anything
            if ($token->can('admin:full-access')) {
                $hasRequiredAbility = true;
            } else {
                // Check each required ability
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

        // Set admin in request for easy access
        $request->attributes->set('admin', $admin);

        return $next($request);
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
            'error_code' => 'ADMIN_UNAUTHENTICATED',
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
            'error_code' => 'ADMIN_FORBIDDEN',
            'required_abilities' => $requiredAbilities,
        ], 403);
    }
}

