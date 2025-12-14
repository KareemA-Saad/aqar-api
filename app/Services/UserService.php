<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * User Service
 *
 * Handles all user-related business logic including:
 * - User CRUD operations
 * - User dashboard statistics
 * - Profile management
 * - Tenant creation for users
 */
final class UserService
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * Get paginated list of users with filters.
     *
     * @param array{search?: string, has_subdomain?: bool, email_verified?: bool, per_page?: int} $filters
     * @return LengthAwarePaginator
     */
    public function getUserList(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount(['tenants', 'supportTickets'])
            ->with(['latestPaymentLog']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Has subdomain filter
        if (isset($filters['has_subdomain'])) {
            $query->where('has_subdomain', $filters['has_subdomain']);
        }

        // Email verified filter
        if (isset($filters['email_verified'])) {
            $query->where('email_verified', $filters['email_verified']);
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get user by ID with all relationships.
     *
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User
    {
        return User::with([
            'tenants' => fn ($q) => $q->with(['domains', 'paymentLog']),
            'paymentLogs' => fn ($q) => $q->with('package')->latest()->limit(20),
            'supportTickets' => fn ($q) => $q->with('department')->latest()->limit(10),
        ])
            ->withCount(['tenants', 'supportTickets', 'paymentLogs'])
            ->find($id);
    }

    /**
     * Update user details (admin action).
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'image' => $data['image'] ?? null,
        ], fn ($v) => $v !== null);

        // Handle email_verified separately as it can be false
        if (isset($data['email_verified'])) {
            $updateData['email_verified'] = $data['email_verified'];
        }

        $user->update($updateData);

        Log::info('User updated by admin', ['user_id' => $user->id]);

        return $user->fresh();
    }

    /**
     * Deactivate user (soft action - revoke tokens).
     *
     * @param User $user
     * @return bool
     */
    public function deactivateUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Revoke all tokens
            $user->tokens()->delete();

            Log::info('User deactivated', ['user_id' => $user->id]);

            return true;
        });
    }

    /**
     * Delete user and all associated data.
     *
     * @param User $user
     * @return bool
     */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $userId = $user->id;

            // Delete all tenants
            foreach ($user->tenants as $tenant) {
                $this->tenantService->deleteTenant($tenant);
            }

            // Revoke all tokens
            $user->tokens()->delete();

            // Delete user
            $user->delete();

            Log::info('User deleted', ['user_id' => $userId]);

            return true;
        });
    }

    /**
     * Generate impersonation token for user.
     *
     * @param User $user
     * @return array{token: string, expires_at: string}
     */
    public function generateImpersonationToken(User $user): array
    {
        $expiresAt = now()->addHours(2);

        $token = $user->createToken(
            name: 'admin-impersonation',
            abilities: ['user:read', 'user:write', 'tenants:read', 'tenants:write', 'impersonated'],
            expiresAt: $expiresAt
        );

        Log::info('Impersonation token generated', ['user_id' => $user->id]);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    /**
     * Get user's payment history.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaymentHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return PaymentLog::where('user_id', $user->id)
            ->with(['package', 'tenant'])
            ->orderByDesc('created_at')
            ->paginate(min($perPage, 100));
    }

    /**
     * Get user dashboard statistics.
     *
     * @param User $user
     * @return array{tenants_count: int, active_packages: int, support_tickets_count: int, open_tickets_count: int, total_spent: float, recent_payments: Collection}
     */
    public function getUserDashboardStats(User $user): array
    {
        $tenantsCount = $user->tenants()->count();

        $activePackages = PaymentLog::where('user_id', $user->id)
            ->where('payment_status', 1)
            ->where(function ($q) {
                $q->whereNull('expire_date')
                    ->orWhere('expire_date', '>', now());
            })
            ->count();

        $supportTicketsCount = $user->supportTickets()->count();
        $openTicketsCount = $user->supportTickets()->where('status', 0)->count();

        $totalSpent = PaymentLog::where('user_id', $user->id)
            ->where('payment_status', 1)
            ->sum('package_price');

        $recentPayments = PaymentLog::where('user_id', $user->id)
            ->with(['package', 'tenant'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'tenants_count' => $tenantsCount,
            'active_packages' => $activePackages,
            'support_tickets_count' => $supportTicketsCount,
            'open_tickets_count' => $openTicketsCount,
            'total_spent' => (float) $totalSpent,
            'recent_payments' => $recentPayments,
        ];
    }

    /**
     * Update user profile (self-service).
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? $user->mobile,
            'company' => $data['company'] ?? $user->company,
            'address' => $data['address'] ?? $user->address,
            'city' => $data['city'] ?? $user->city,
            'state' => $data['state'] ?? $user->state,
            'country' => $data['country'] ?? $user->country,
            'image' => $data['image'] ?? $user->image,
        ]);

        Log::info('User profile updated', ['user_id' => $user->id]);

        return $user->fresh();
    }

    /**
     * Change user password.
     *
     * @param User $user
     * @param string $oldPassword
     * @param string $newPassword
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        Log::info('User password changed', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Get user's tenants with status.
     *
     * @param User $user
     * @return Collection<int, Tenant>
     */
    public function getUserTenants(User $user): Collection
    {
        return $user->tenants()
            ->with(['domains', 'paymentLog'])
            ->get();
    }

    /**
     * Get user's support tickets.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserSupportTickets(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return SupportTicket::where('user_id', $user->id)
            ->with(['department', 'admin'])
            ->orderByDesc('created_at')
            ->paginate(min($perPage, 100));
    }

    /**
     * Create a tenant for user.
     *
     * @param User $user
     * @param PricePlan $plan
     * @param array{subdomain: string, theme?: ?string, theme_code?: ?string} $data
     * @return Tenant
     */
    public function createTenantForUser(User $user, PricePlan $plan, array $data): Tenant
    {
        return $this->tenantService->createTenant(
            user: $user,
            plan: $plan,
            data: $data,
            async: true
        );
    }
}

