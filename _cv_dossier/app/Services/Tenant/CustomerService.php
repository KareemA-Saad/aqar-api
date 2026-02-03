<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Service class for managing tenant customers (frontend users).
 *
 * Handles customer CRUD operations, statistics, order history,
 * wishlist management, and address management.
 */
final class CustomerService
{
    /**
     * Get paginated list of customers with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCustomers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TenantUser::query();

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Email verified filter
        if (isset($filters['email_verified'])) {
            $query->where('email_verified', (bool) $filters['email_verified']);
        }

        // Status filter (if status column exists)
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new customer.
     *
     * @param array<string, mixed> $data
     * @return TenantUser
     */
    public function createCustomer(array $data): TenantUser
    {
        return DB::transaction(function () use ($data) {
            $customer = TenantUser::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'username' => $data['username'] ?? Str::slug($data['name']) . '-' . Str::random(4),
                'mobile' => $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'email_verified' => $data['email_verified'] ?? false,
            ]);

            return $customer;
        });
    }

    /**
     * Update a customer.
     *
     * @param TenantUser $customer
     * @param array<string, mixed> $data
     * @return TenantUser
     */
    public function updateCustomer(TenantUser $customer, array $data): TenantUser
    {
        return DB::transaction(function () use ($customer, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'username' => $data['username'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'email_verified' => $data['email_verified'] ?? null,
            ], fn($value) => $value !== null);

            $customer->update($updateData);

            return $customer->fresh();
        });
    }

    /**
     * Update customer password.
     *
     * @param TenantUser $customer
     * @param string $password
     * @return TenantUser
     */
    public function updatePassword(TenantUser $customer, string $password): TenantUser
    {
        $customer->update([
            'password' => Hash::make($password),
        ]);

        return $customer;
    }

    /**
     * Delete a customer.
     *
     * @param TenantUser $customer
     * @return bool
     */
    public function deleteCustomer(TenantUser $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            // Revoke all tokens
            $customer->tokens()->delete();

            // Delete related data (addresses, wishlist, etc.)
            if (method_exists($customer, 'addresses')) {
                $customer->addresses()->delete();
            }
            if (method_exists($customer, 'wishlist')) {
                $customer->wishlist()->delete();
            }

            return $customer->delete();
        });
    }

    /**
     * Toggle customer status (active/inactive).
     *
     * @param TenantUser $customer
     * @return TenantUser
     */
    public function toggleStatus(TenantUser $customer): TenantUser
    {
        // If status column doesn't exist, toggle email_verified as a proxy
        if (!DB::getSchemaBuilder()->hasColumn('users', 'status')) {
            $customer->update([
                'email_verified' => !$customer->email_verified,
            ]);
        } else {
            $customer->update([
                'status' => $customer->status === 'active' ? 'inactive' : 'active',
            ]);
        }

        return $customer->fresh();
    }

    /**
     * Get customer statistics for admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $totalCustomers = TenantUser::count();
        $verifiedCustomers = TenantUser::where('email_verified', true)->count();
        $newCustomersThisMonth = TenantUser::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newCustomersToday = TenantUser::whereDate('created_at', today())->count();

        return [
            'total_customers' => $totalCustomers,
            'verified_customers' => $verifiedCustomers,
            'unverified_customers' => $totalCustomers - $verifiedCustomers,
            'new_this_month' => $newCustomersThisMonth,
            'new_today' => $newCustomersToday,
            'verification_rate' => $totalCustomers > 0 
                ? round(($verifiedCustomers / $totalCustomers) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get customer order history.
     *
     * @param TenantUser $customer
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOrderHistory(TenantUser $customer, int $perPage = 15): LengthAwarePaginator
    {
        // Check if orders table exists and has user_id relation
        if (!DB::getSchemaBuilder()->hasTable('product_orders')) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [], 0, $perPage, 1
            );
        }

        return DB::table('product_orders')
            ->where('user_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get customer dashboard statistics.
     *
     * @param TenantUser $customer
     * @return array<string, mixed>
     */
    public function getCustomerDashboard(TenantUser $customer): array
    {
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'completed_orders' => 0,
            'total_spent' => 0,
            'wishlist_count' => 0,
            'addresses_count' => 0,
            'support_tickets' => 0,
        ];

        // Orders stats
        if (DB::getSchemaBuilder()->hasTable('product_orders')) {
            $orderStats = DB::table('product_orders')
                ->where('user_id', $customer->id)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN payment_status = 0 THEN 1 ELSE 0 END) as pending')
                ->selectRaw('SUM(CASE WHEN payment_status = 1 THEN 1 ELSE 0 END) as completed')
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_spent')
                ->first();

            $stats['total_orders'] = (int) ($orderStats->total ?? 0);
            $stats['pending_orders'] = (int) ($orderStats->pending ?? 0);
            $stats['completed_orders'] = (int) ($orderStats->completed ?? 0);
            $stats['total_spent'] = (float) ($orderStats->total_spent ?? 0);
        }

        // Wishlist count
        if (DB::getSchemaBuilder()->hasTable('wishlists')) {
            $stats['wishlist_count'] = DB::table('wishlists')
                ->where('user_id', $customer->id)
                ->count();
        }

        // Addresses count
        if (DB::getSchemaBuilder()->hasTable('customer_addresses')) {
            $stats['addresses_count'] = DB::table('customer_addresses')
                ->where('user_id', $customer->id)
                ->count();
        }

        // Support tickets count
        if (DB::getSchemaBuilder()->hasTable('support_tickets')) {
            $stats['support_tickets'] = DB::table('support_tickets')
                ->where('user_id', $customer->id)
                ->count();
        }

        return $stats;
    }

    /**
     * Get customer wishlist items.
     *
     * @param TenantUser $customer
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWishlist(TenantUser $customer, int $perPage = 15): LengthAwarePaginator
    {
        if (!DB::getSchemaBuilder()->hasTable('wishlists')) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [], 0, $perPage, 1
            );
        }

        return DB::table('wishlists')
            ->where('user_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Add item to wishlist.
     *
     * @param TenantUser $customer
     * @param int $productId
     * @return bool
     */
    public function addToWishlist(TenantUser $customer, int $productId): bool
    {
        if (!DB::getSchemaBuilder()->hasTable('wishlists')) {
            return false;
        }

        // Check if already exists
        $exists = DB::table('wishlists')
            ->where('user_id', $customer->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::table('wishlists')->insert([
            'user_id' => $customer->id,
            'product_id' => $productId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Remove item from wishlist.
     *
     * @param TenantUser $customer
     * @param int $productId
     * @return bool
     */
    public function removeFromWishlist(TenantUser $customer, int $productId): bool
    {
        if (!DB::getSchemaBuilder()->hasTable('wishlists')) {
            return false;
        }

        return DB::table('wishlists')
            ->where('user_id', $customer->id)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    /**
     * Clear customer wishlist.
     *
     * @param TenantUser $customer
     * @return int Number of items removed
     */
    public function clearWishlist(TenantUser $customer): int
    {
        if (!DB::getSchemaBuilder()->hasTable('wishlists')) {
            return 0;
        }

        return DB::table('wishlists')
            ->where('user_id', $customer->id)
            ->delete();
    }

    /**
     * Get customer addresses.
     *
     * @param TenantUser $customer
     * @return Collection
     */
    public function getAddresses(TenantUser $customer): Collection
    {
        if (!DB::getSchemaBuilder()->hasTable('customer_addresses')) {
            return new Collection();
        }

        return DB::table('customer_addresses')
            ->where('user_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Add customer address.
     *
     * @param TenantUser $customer
     * @param array<string, mixed> $data
     * @return object
     */
    public function addAddress(TenantUser $customer, array $data): object
    {
        $isDefault = $data['is_default'] ?? false;

        // If this is default, remove default from others
        if ($isDefault && DB::getSchemaBuilder()->hasTable('customer_addresses')) {
            DB::table('customer_addresses')
                ->where('user_id', $customer->id)
                ->update(['is_default' => false]);
        }

        // Check if this is the first address - make it default
        $addressCount = DB::table('customer_addresses')
            ->where('user_id', $customer->id)
            ->count();

        if ($addressCount === 0) {
            $isDefault = true;
        }

        $addressId = DB::table('customer_addresses')->insertGetId([
            'user_id' => $customer->id,
            'name' => $data['name'] ?? $customer->name,
            'phone' => $data['phone'] ?? $customer->mobile,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'],
            'is_default' => $isDefault,
            'type' => $data['type'] ?? 'shipping',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('customer_addresses')->find($addressId);
    }

    /**
     * Update customer address.
     *
     * @param TenantUser $customer
     * @param int $addressId
     * @param array<string, mixed> $data
     * @return object|null
     */
    public function updateAddress(TenantUser $customer, int $addressId, array $data): ?object
    {
        $address = DB::table('customer_addresses')
            ->where('id', $addressId)
            ->where('user_id', $customer->id)
            ->first();

        if (!$address) {
            return null;
        }

        // Handle default address change
        if (!empty($data['is_default']) && $data['is_default']) {
            DB::table('customer_addresses')
                ->where('user_id', $customer->id)
                ->where('id', '!=', $addressId)
                ->update(['is_default' => false]);
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address_line_1' => $data['address_line_1'] ?? null,
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'is_default' => $data['is_default'] ?? null,
            'type' => $data['type'] ?? null,
            'updated_at' => now(),
        ], fn($value) => $value !== null);

        DB::table('customer_addresses')
            ->where('id', $addressId)
            ->update($updateData);

        return DB::table('customer_addresses')->find($addressId);
    }

    /**
     * Delete customer address.
     *
     * @param TenantUser $customer
     * @param int $addressId
     * @return bool
     */
    public function deleteAddress(TenantUser $customer, int $addressId): bool
    {
        $address = DB::table('customer_addresses')
            ->where('id', $addressId)
            ->where('user_id', $customer->id)
            ->first();

        if (!$address) {
            return false;
        }

        $wasDefault = $address->is_default ?? false;

        $deleted = DB::table('customer_addresses')
            ->where('id', $addressId)
            ->where('user_id', $customer->id)
            ->delete() > 0;

        // If deleted address was default, set another as default
        if ($deleted && $wasDefault) {
            $firstAddress = DB::table('customer_addresses')
                ->where('user_id', $customer->id)
                ->first();

            if ($firstAddress) {
                DB::table('customer_addresses')
                    ->where('id', $firstAddress->id)
                    ->update(['is_default' => true]);
            }
        }

        return $deleted;
    }

    /**
     * Set address as default.
     *
     * @param TenantUser $customer
     * @param int $addressId
     * @return bool
     */
    public function setDefaultAddress(TenantUser $customer, int $addressId): bool
    {
        $address = DB::table('customer_addresses')
            ->where('id', $addressId)
            ->where('user_id', $customer->id)
            ->first();

        if (!$address) {
            return false;
        }

        // Remove default from all others
        DB::table('customer_addresses')
            ->where('user_id', $customer->id)
            ->update(['is_default' => false]);

        // Set this one as default
        DB::table('customer_addresses')
            ->where('id', $addressId)
            ->update(['is_default' => true]);

        return true;
    }

    /**
     * Export customers to CSV data.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function exportCustomers(array $filters = []): array
    {
        $query = TenantUser::query();

        // Apply same filters as getCustomers
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['email_verified'])) {
            $query->where('email_verified', (bool) $filters['email_verified']);
        }

        $customers = $query->orderBy('created_at', 'desc')->get();

        return $customers->map(fn($customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'username' => $customer->username,
            'mobile' => $customer->mobile,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'country' => $customer->country,
            'email_verified' => $customer->email_verified ? 'Yes' : 'No',
            'created_at' => $customer->created_at?->toDateTimeString(),
        ])->toArray();
    }

    /**
     * Resend email verification to customer.
     *
     * @param TenantUser $customer
     * @return string Verification token
     */
    public function generateVerificationToken(TenantUser $customer): string
    {
        $token = Str::random(64);

        $customer->update([
            'email_verify_token' => $token,
        ]);

        return $token;
    }
}
