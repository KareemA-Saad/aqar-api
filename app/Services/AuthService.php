<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AuthService handles all authentication business logic.
 *
 * Responsibilities:
 * - Token generation with scoped abilities
 * - User authentication (login/logout)
 * - Password reset flow
 * - Email verification
 * - Social login integration
 */
final class AuthService
{
    /**
     * Token abilities for different user types.
     */
    private const ADMIN_ABILITIES = [
        'admin:read',
        'admin:write',
        'admin:delete',
        'users:manage',
        'tenants:manage',
        'settings:manage',
    ];

    private const USER_ABILITIES = [
        'user:read',
        'user:write',
        'tenants:read',
        'tenants:write',
        'subscriptions:manage',
    ];

    private const TENANT_USER_ABILITIES = [
        'tenant:read',
        'tenant:write',
        'profile:manage',
    ];

    /**
     * Token expiration in minutes.
     */
    private const TOKEN_EXPIRATION_MINUTES = 60 * 24 * 7; // 7 days

    /**
     * Authenticate admin and generate token.
     *
     * @param string $credential Email or username
     * @param string $password
     * @return array{admin: Admin, token: string, expires_at: string}
     * @throws AuthenticationException
     */
    public function authenticateAdmin(string $credential, string $password): array
    {
        $admin = $this->findUserByCredential(Admin::class, $credential);

        if (!$admin || !Hash::check($password, $admin->password)) {
            Log::warning('Admin login failed', ['credential' => $credential]);
            throw new AuthenticationException('Invalid credentials');
        }

        $token = $this->createToken($admin, 'admin-token', self::ADMIN_ABILITIES);

        Log::info('Admin logged in', ['admin_id' => $admin->id]);

        return [
            'admin' => $admin,
            'token' => $token['token'],
            'expires_at' => $token['expires_at'],
        ];
    }

    /**
     * Authenticate user (landlord/tenant owner) and generate token.
     *
     * @param string $credential Email or username
     * @param string $password
     * @return array{user: User, token: string, expires_at: string}
     * @throws AuthenticationException
     */
    public function authenticateUser(string $credential, string $password): array
    {
        $user = $this->findUserByCredential(User::class, $credential);

        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('User login failed', ['credential' => $credential]);
            throw new AuthenticationException('Invalid credentials');
        }

        $token = $this->createToken($user, 'user-token', self::USER_ABILITIES);

        Log::info('User logged in', ['user_id' => $user->id]);

        return [
            'user' => $user,
            'token' => $token['token'],
            'expires_at' => $token['expires_at'],
        ];
    }

    /**
     * Authenticate tenant user and generate token with tenant context.
     *
     * @param string $credential Email or username
     * @param string $password
     * @param string $tenantId The tenant context
     * @return array{user: TenantUser, token: string, expires_at: string, tenant_id: string}
     * @throws AuthenticationException
     */
    public function authenticateTenantUser(string $credential, string $password, string $tenantId): array
    {
        $user = $this->findUserByCredential(TenantUser::class, $credential);

        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('Tenant user login failed', [
                'credential' => $credential,
                'tenant_id' => $tenantId,
            ]);
            throw new AuthenticationException('Invalid credentials');
        }

        // Include tenant_id in token name for context resolution
        $tokenName = "tenant-{$tenantId}-token";
        $abilities = array_merge(self::TENANT_USER_ABILITIES, ["tenant:{$tenantId}"]);

        $token = $this->createToken($user, $tokenName, $abilities);

        Log::info('Tenant user logged in', [
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
        ]);

        return [
            'user' => $user,
            'token' => $token['token'],
            'expires_at' => $token['expires_at'],
            'tenant_id' => $tenantId,
        ];
    }

    /**
     * Register a new user (landlord/tenant owner).
     *
     * @param array{name: string, email: string, password: string, username?: string, mobile?: string, company?: string, country?: string, city?: string} $data
     * @return array{user: User, token: string, expires_at: string}
     */
    public function registerUser(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'username' => $data['username'] ?? $this->generateUsername($data['email']),
                'mobile' => $data['mobile'] ?? null,
                'company' => $data['company'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'email_verified' => false,
                'email_verify_token' => $this->generateVerificationToken(),
            ]);

            $token = $this->createToken($user, 'user-token', self::USER_ABILITIES);

            Log::info('User registered', ['user_id' => $user->id]);

            return [
                'user' => $user,
                'token' => $token['token'],
                'expires_at' => $token['expires_at'],
            ];
        });
    }

    /**
     * Register a new tenant user.
     *
     * @param array{name: string, email: string, password: string, username?: string, mobile?: string} $data
     * @param string $tenantId
     * @return array{user: TenantUser, token: string, expires_at: string, tenant_id: string}
     */
    public function registerTenantUser(array $data, string $tenantId): array
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $user = TenantUser::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'username' => $data['username'] ?? $this->generateUsername($data['email']),
                'mobile' => $data['mobile'] ?? null,
                'email_verified' => false,
                'email_verify_token' => $this->generateVerificationToken(),
            ]);

            $tokenName = "tenant-{$tenantId}-token";
            $abilities = array_merge(self::TENANT_USER_ABILITIES, ["tenant:{$tenantId}"]);
            $token = $this->createToken($user, $tokenName, $abilities);

            Log::info('Tenant user registered', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ]);

            return [
                'user' => $user,
                'token' => $token['token'],
                'expires_at' => $token['expires_at'],
                'tenant_id' => $tenantId,
            ];
        });
    }

    /**
     * Handle social login/registration.
     *
     * @param string $provider 'google' or 'facebook'
     * @param array{id: string, name: string, email: string} $socialUser
     * @return array{user: User, token: string, expires_at: string, is_new: bool}
     */
    public function socialLogin(string $provider, array $socialUser): array
    {
        return DB::transaction(function () use ($provider, $socialUser) {
            $providerIdColumn = "{$provider}_id";
            $isNew = false;

            // Try to find existing user by email first
            $user = User::where('email', $socialUser['email'])->first();

            if ($user) {
                // Update provider ID if not set
                if (empty($user->{$providerIdColumn})) {
                    $user->update([$providerIdColumn => $socialUser['id']]);
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser['name'],
                    'email' => $socialUser['email'],
                    'username' => "{$provider}_" . $this->generateUsername($socialUser['email']),
                    $providerIdColumn => $socialUser['id'],
                    'email_verified' => true, // Social accounts are pre-verified
                    'password' => Hash::make(Str::random(32)), // Random password
                ]);
                $isNew = true;
            }

            $token = $this->createToken($user, 'user-token', self::USER_ABILITIES);

            Log::info('Social login', [
                'provider' => $provider,
                'user_id' => $user->id,
                'is_new' => $isNew,
            ]);

            return [
                'user' => $user,
                'token' => $token['token'],
                'expires_at' => $token['expires_at'],
                'is_new' => $isNew,
            ];
        });
    }

    /**
     * Revoke current token (logout).
     *
     * @param Authenticatable $user
     * @return void
     */
    public function logout(Authenticatable $user): void
    {
        /** @var \Laravel\Sanctum\HasApiTokens $user */
        $user->currentAccessToken()?->delete();

        Log::info('User logged out', ['user_id' => $user->getAuthIdentifier()]);
    }

    /**
     * Revoke all tokens for user.
     *
     * @param Authenticatable $user
     * @return void
     */
    public function logoutAll(Authenticatable $user): void
    {
        /** @var \Laravel\Sanctum\HasApiTokens $user */
        $user->tokens()->delete();

        Log::info('All tokens revoked', ['user_id' => $user->getAuthIdentifier()]);
    }

    /**
     * Refresh token - revoke current and issue new.
     *
     * @param Authenticatable $user
     * @param string $tokenName
     * @param array<string> $abilities
     * @return array{token: string, expires_at: string}
     */
    public function refreshToken(Authenticatable $user, string $tokenName, array $abilities): array
    {
        /** @var \Laravel\Sanctum\HasApiTokens $user */
        $user->currentAccessToken()?->delete();

        return $this->createToken($user, $tokenName, $abilities);
    }

    /**
     * Generate password reset token.
     *
     * @param string $table Password reset table name
     * @param string $email
     * @return string The reset token
     */
    public function createPasswordResetToken(string $table, string $email): string
    {
        $token = Str::random(64);

        DB::table($table)->where('email', $email)->delete();
        DB::table($table)->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        Log::info('Password reset token created', ['email' => $email]);

        return $token;
    }

    /**
     * Verify and consume password reset token.
     *
     * @param string $table Password reset table name
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function verifyPasswordResetToken(string $table, string $email, string $token): bool
    {
        $record = DB::table($table)
            ->where('email', $email)
            ->first();

        if (!$record) {
            return false;
        }

        // Check token expiration (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table($table)->where('email', $email)->delete();
            return false;
        }

        return Hash::check($token, $record->token);
    }

    /**
     * Reset password for user.
     *
     * @param class-string<Authenticatable> $modelClass
     * @param string $email
     * @param string $newPassword
     * @return Authenticatable
     */
    public function resetPassword(string $modelClass, string $email, string $newPassword): Authenticatable
    {
        return DB::transaction(function () use ($modelClass, $email, $newPassword) {
            /** @var Authenticatable $user */
            $user = $modelClass::where('email', $email)->firstOrFail();
            $user->update(['password' => Hash::make($newPassword)]);

            // Revoke all tokens for security
            /** @var \Laravel\Sanctum\HasApiTokens $user */
            $user->tokens()->delete();

            Log::info('Password reset completed', [
                'model' => $modelClass,
                'email' => $email,
            ]);

            return $user;
        });
    }

    /**
     * Verify email with token.
     *
     * @param class-string<Authenticatable> $modelClass
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function verifyEmail(string $modelClass, int $userId, string $token): bool
    {
        /** @var Authenticatable|null $user */
        $user = $modelClass::where('id', $userId)
            ->where('email_verify_token', $token)
            ->first();

        if (!$user) {
            return false;
        }

        $user->update([
            'email_verified' => true,
            'email_verify_token' => null,
        ]);

        Log::info('Email verified', [
            'model' => $modelClass,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Regenerate email verification token.
     *
     * @param Authenticatable $user
     * @return string New token
     */
    public function regenerateVerificationToken(Authenticatable $user): string
    {
        $token = $this->generateVerificationToken();
        $user->update(['email_verify_token' => $token]);

        return $token;
    }

    /**
     * Create API token for user.
     *
     * @param Authenticatable $user
     * @param string $name
     * @param array<string> $abilities
     * @return array{token: string, expires_at: string}
     */
    private function createToken(Authenticatable $user, string $name, array $abilities): array
    {
        $expiresAt = now()->addMinutes(self::TOKEN_EXPIRATION_MINUTES);

        /** @var \Laravel\Sanctum\HasApiTokens $user */
        $token = $user->createToken($name, $abilities, $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    /**
     * Find user by email or username.
     *
     * @param class-string $modelClass
     * @param string $credential
     * @return Authenticatable|null
     */
    private function findUserByCredential(string $modelClass, string $credential): ?Authenticatable
    {
        $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return $modelClass::where($field, $credential)->first();
    }

    /**
     * Generate username from email.
     *
     * @param string $email
     * @return string
     */
    private function generateUsername(string $email): string
    {
        $base = explode('@', $email)[0];
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);

        return $base . '_' . Str::random(4);
    }

    /**
     * Generate email verification token.
     *
     * @return string
     */
    private function generateVerificationToken(): string
    {
        return Str::random(6); // 6-character code for easy input
    }

    /**
     * Get token abilities for admin.
     *
     * @return array<string>
     */
    public function getAdminAbilities(): array
    {
        return self::ADMIN_ABILITIES;
    }

    /**
     * Get token abilities for user.
     *
     * @return array<string>
     */
    public function getUserAbilities(): array
    {
        return self::USER_ABILITIES;
    }

    /**
     * Get token abilities for tenant user.
     *
     * @param string|null $tenantId
     * @return array<string>
     */
    public function getTenantUserAbilities(?string $tenantId = null): array
    {
        $abilities = self::TENANT_USER_ABILITIES;

        if ($tenantId) {
            $abilities[] = "tenant:{$tenantId}";
        }

        return $abilities;
    }
}

