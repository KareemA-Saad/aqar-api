<?php

declare(strict_types=1);

namespace Modules\CouponManage\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\CouponManage\Entities\ProductCoupon;
use Carbon\Carbon;

class CouponService
{
    /**
     * Get paginated coupons with filters
     */
    public function getCoupons(array $filters = []): LengthAwarePaginator
    {
        $query = ProductCoupon::query();

        // Search by title or code
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by code
        if (!empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        // Filter by discount type
        if (!empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by expiration status
        if (isset($filters['expired'])) {
            if ($filters['expired']) {
                $query->where('expire_date', '<', now());
            } else {
                $query->where(function ($q) {
                    $q->where('expire_date', '>=', now())
                        ->orWhereNull('expire_date');
                });
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get active, non-expired coupons
     */
    public function getActiveCoupons(): Collection
    {
        return ProductCoupon::where('status', 'publish')
            ->where(function ($query) {
                $query->where('expire_date', '>=', now())
                    ->orWhereNull('expire_date');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get coupon by ID
     */
    public function getCouponById(int $id): ?ProductCoupon
    {
        return ProductCoupon::find($id);
    }

    /**
     * Get coupon by code
     */
    public function getCouponByCode(string $code): ?ProductCoupon
    {
        return ProductCoupon::where('code', strtoupper($code))->first();
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(string $code): array
    {
        $coupon = $this->getCouponByCode($code);

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code',
            ];
        }

        if ($coupon->status !== 'publish') {
            return [
                'valid' => false,
                'message' => 'Coupon is not active',
            ];
        }

        if ($coupon->expire_date && Carbon::parse($coupon->expire_date)->isPast()) {
            return [
                'valid' => false,
                'message' => 'Coupon has expired',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Coupon is valid',
            'coupon' => $coupon,
        ];
    }

    /**
     * Create coupon
     */
    public function createCoupon(array $data): ProductCoupon
    {
        $data['code'] = strtoupper($data['code']);
        
        return ProductCoupon::create($data);
    }

    /**
     * Update coupon
     */
    public function updateCoupon(int $id, array $data): bool
    {
        $coupon = ProductCoupon::find($id);
        if (!$coupon) {
            return false;
        }

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        return $coupon->update($data);
    }

    /**
     * Delete coupon
     */
    public function deleteCoupon(int $id): bool
    {
        $coupon = ProductCoupon::find($id);
        if (!$coupon) {
            return false;
        }

        return $coupon->delete();
    }

    /**
     * Bulk delete coupons
     */
    public function bulkDelete(array $couponIds): int
    {
        return ProductCoupon::whereIn('id', $couponIds)->delete();
    }

    /**
     * Bulk activate coupons
     */
    public function bulkActivate(array $couponIds): int
    {
        return ProductCoupon::whereIn('id', $couponIds)->update(['status' => 'publish']);
    }

    /**
     * Bulk deactivate coupons
     */
    public function bulkDeactivate(array $couponIds): int
    {
        return ProductCoupon::whereIn('id', $couponIds)->update(['status' => 'draft']);
    }

    /**
     * Get expired coupons
     */
    public function getExpiredCoupons(int $limit = 10): Collection
    {
        return ProductCoupon::whereNotNull('expire_date')
            ->where('expire_date', '<', now())
            ->orderBy('expire_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get coupons expiring soon
     */
    public function getExpiringSoonCoupons(int $days = 7, int $limit = 10): Collection
    {
        $expiryDate = now()->addDays($days);
        
        return ProductCoupon::whereNotNull('expire_date')
            ->whereBetween('expire_date', [now(), $expiryDate])
            ->where('status', 'publish')
            ->orderBy('expire_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate unique coupon code
     */
    public function generateUniqueCouponCode(string $prefix = 'COUPON', int $length = 8): string
    {
        do {
            $code = strtoupper($prefix . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
            $exists = ProductCoupon::where('code', $code)->exists();
        } while ($exists);

        return $code;
    }

    /**
     * Get coupon statistics
     */
    public function getStatistics(): array
    {
        $total = ProductCoupon::count();
        $active = ProductCoupon::where('status', 'publish')->count();
        $expired = ProductCoupon::whereNotNull('expire_date')
            ->where('expire_date', '<', now())
            ->count();
        
        return [
            'total_coupons' => $total,
            'active_coupons' => $active,
            'draft_coupons' => ProductCoupon::where('status', 'draft')->count(),
            'expired_coupons' => $expired,
            'valid_coupons' => $active - $expired,
            'percentage_coupons' => ProductCoupon::where('discount_type', 'percentage')->count(),
            'fixed_coupons' => ProductCoupon::where('discount_type', 'fixed')->count(),
        ];
    }
}
