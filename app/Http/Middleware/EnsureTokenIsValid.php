<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Token Is Valid Middleware
 *
 * Validates Sanctum token beyond standard authentication:
 * - Checks token expiration
 * - Validates token abilities for the current route
 * - Provides detailed error messages
 */
final class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param string ...$abilities Required abilities for this route
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

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

        // Check token expiration
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
                'error_code' => 'TOKEN_EXPIRED',
            ], 401);
        }

        // Check abilities if specified
        if (!empty($abilities)) {
            foreach ($abilities as $ability) {
                if (!$token->can($ability)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient permissions',
                        'error_code' => 'TOKEN_INSUFFICIENT_ABILITIES',
                        'required_ability' => $ability,
                    ], 403);
                }
            }
        }

        // Update last used timestamp
        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}

