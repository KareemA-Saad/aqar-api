<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate Tenant Admin Middleware
 *
 * Custom authentication middleware for tenant admin tokens.
 * 
 * This middleware handles the special case where:
 * - Token is stored in the TENANT database (not central)
 * - Tenancy must be initialized BEFORE token lookup
 * - The authenticated user is an Admin model from tenant DB
 *
 * Usage: Replace 'auth:api_tenant_admin' with 'auth.tenant_admin'
 *
 * Flow:
 * 1. Tenancy should already be initialized by 'tenancy.token' middleware
 * 2. Extract bearer token from request
 * 3. Look up token in TENANT's personal_access_tokens table
 * 4. Validate token and authenticate Admin
 */
final class AuthenticateTenantAdmin
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

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
        Log::debug('AuthenticateTenantAdmin: Starting authentication', [
            'tenancy_initialized' => $this->tenancy->initialized,
            'tenant_id' => $this->tenancy->tenant?->id,
            'current_connection' => DB::getDefaultConnection(),
            'current_database' => DB::connection()->getDatabaseName(),
        ]);

        // Ensure tenancy is initialized
        if (!$this->tenancy->initialized) {
            Log::warning('AuthenticateTenantAdmin: Tenancy not initialized');
            return $this->errorResponse(
                'Tenant context required. Initialize tenancy first.',
                'TENANT_CONTEXT_REQUIRED',
                403
            );
        }

        // Get bearer token
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            Log::debug('AuthenticateTenantAdmin: No bearer token provided');
            return $this->unauthorizedResponse('Authentication required. Please provide a valid token.');
        }

        // Parse the token (format: {id}|{token})
        $tokenParts = explode('|', $bearerToken, 2);
        
        if (count($tokenParts) !== 2) {
            Log::debug('AuthenticateTenantAdmin: Invalid token format');
            return $this->unauthorizedResponse('Invalid token format.');
        }

        [$tokenId, $plainToken] = $tokenParts;

        // Hash the plain token to compare with stored hash
        $hashedToken = hash('sha256', $plainToken);

        Log::debug('AuthenticateTenantAdmin: Looking up token', [
            'token_id' => $tokenId,
            'hash_prefix' => substr($hashedToken, 0, 10) . '...',
        ]);

        // Look up token in TENANT database
        // The tenancy should have already switched the connection
        $accessToken = PersonalAccessToken::where('id', $tokenId)
            ->where('token', $hashedToken)
            ->first();

        if (!$accessToken) {
            Log::debug('AuthenticateTenantAdmin: Token not found in database', [
                'database' => DB::connection()->getDatabaseName(),
            ]);
            return $this->unauthorizedResponse('Invalid authentication token.');
        }

        Log::debug('AuthenticateTenantAdmin: Token found', [
            'token_id' => $accessToken->id,
            'token_name' => $accessToken->name,
            'tokenable_type' => $accessToken->tokenable_type,
            'tokenable_id' => $accessToken->tokenable_id,
        ]);

        // Check token expiration
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            Log::debug('AuthenticateTenantAdmin: Token expired');
            $accessToken->delete();
            return $this->unauthorizedResponse('Token has expired. Please switch to tenant again.');
        }

        // Validate tokenable type is Admin
        if ($accessToken->tokenable_type !== Admin::class) {
            Log::warning('AuthenticateTenantAdmin: Token is not for Admin model', [
                'expected' => Admin::class,
                'actual' => $accessToken->tokenable_type,
            ]);
            return $this->unauthorizedResponse('Invalid token type. Expected tenant admin token.');
        }

        // Get the Admin from tenant database
        $admin = Admin::find($accessToken->tokenable_id);

        if (!$admin) {
            Log::warning('AuthenticateTenantAdmin: Admin not found', [
                'admin_id' => $accessToken->tokenable_id,
            ]);
            return $this->unauthorizedResponse('Admin account not found.');
        }

        Log::debug('AuthenticateTenantAdmin: Admin authenticated', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
        ]);

        // Check required abilities
        if (!empty($abilities)) {
            $hasRequiredAbility = false;
            $tokenAbilities = $accessToken->abilities ?? [];

            foreach ($abilities as $ability) {
                if (in_array('*', $tokenAbilities) || in_array($ability, $tokenAbilities)) {
                    $hasRequiredAbility = true;
                    break;
                }
            }

            if (!$hasRequiredAbility) {
                Log::debug('AuthenticateTenantAdmin: Missing required abilities', [
                    'required' => $abilities,
                    'has' => $tokenAbilities,
                ]);
                return $this->forbiddenResponse('Insufficient permissions.', $abilities);
            }
        }

        // Update last used timestamp
        $accessToken->forceFill(['last_used_at' => now()])->save();

        // Set the admin as the authenticated user for this request
        // This makes auth()->user() return the Admin
        auth()->setUser($admin);
        
        // Also set the token on the admin so currentAccessToken() works
        $admin->withAccessToken($accessToken);

        // Store in request attributes for easy access
        $request->attributes->set('tenant_admin', $admin);
        $request->attributes->set('access_token', $accessToken);

        Log::info('AuthenticateTenantAdmin: Authentication successful', [
            'tenant_id' => $this->tenancy->tenant->id,
            'admin_id' => $admin->id,
        ]);

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
            'error_code' => 'UNAUTHENTICATED',
        ], 401);
    }

    /**
     * Return forbidden JSON response.
     *
     * @param string $message
     * @param array $requiredAbilities
     * @return Response
     */
    private function forbiddenResponse(string $message, array $requiredAbilities = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => 'FORBIDDEN',
        ];

        if (!empty($requiredAbilities)) {
            $response['required_abilities'] = $requiredAbilities;
        }

        return response()->json($response, 403);
    }

    /**
     * Return generic error response.
     *
     * @param string $message
     * @param string $errorCode
     * @param int $status
     * @return Response
     */
    private function errorResponse(string $message, string $errorCode, int $status): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ], $status);
    }
}
