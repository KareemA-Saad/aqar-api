<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\CancellationPolicy;
use Modules\HotelBooking\Entities\CancellationPolicyTier;

class CancellationPolicyService
{
    /**
     * Get all cancellation policies.
     */
    public function getPolicies(array $filters = []): LengthAwarePaginator
    {
        $query = CancellationPolicy::with('tiers')
            ->orderBy('name');

        if (!empty($filters['is_active'])) {
            $query->where('is_active', true);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get active policies only.
     */
    public function getActivePolicies(): Collection
    {
        return CancellationPolicy::with('tiers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a single policy.
     */
    public function getPolicy(int $id): ?CancellationPolicy
    {
        return CancellationPolicy::with('tiers')->find($id);
    }

    /**
     * Create a new cancellation policy with tiers.
     */
    public function createPolicy(array $data): CancellationPolicy
    {
        return DB::transaction(function () use ($data) {
            $policy = CancellationPolicy::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_refundable' => $data['is_refundable'] ?? true,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create tiers
            if (!empty($data['tiers'])) {
                foreach ($data['tiers'] as $tierData) {
                    $policy->tiers()->create([
                        'hours_before_checkin' => $tierData['hours_before_checkin'],
                        'refund_percentage' => $tierData['refund_percentage'],
                    ]);
                }
            }

            return $policy->fresh('tiers');
        });
    }

    /**
     * Update a cancellation policy.
     */
    public function updatePolicy(int $id, array $data): CancellationPolicy
    {
        return DB::transaction(function () use ($id, $data) {
            $policy = CancellationPolicy::findOrFail($id);

            $policy->update([
                'name' => $data['name'] ?? $policy->name,
                'description' => $data['description'] ?? $policy->description,
                'is_refundable' => $data['is_refundable'] ?? $policy->is_refundable,
                'is_active' => $data['is_active'] ?? $policy->is_active,
            ]);

            // Update tiers if provided
            if (isset($data['tiers'])) {
                // Delete existing tiers
                $policy->tiers()->delete();

                // Create new tiers
                foreach ($data['tiers'] as $tierData) {
                    $policy->tiers()->create([
                        'hours_before_checkin' => $tierData['hours_before_checkin'],
                        'refund_percentage' => $tierData['refund_percentage'],
                    ]);
                }
            }

            return $policy->fresh('tiers');
        });
    }

    /**
     * Delete a cancellation policy.
     */
    public function deletePolicy(int $id): bool
    {
        $policy = CancellationPolicy::findOrFail($id);

        // Check if policy is in use
        $inUseCount = \Modules\HotelBooking\Entities\Hotel::where('cancellation_policy_id', $id)->count();
        $inUseCount += \Modules\HotelBooking\Entities\BookingInformation::where('cancellation_policy_id', $id)->count();

        if ($inUseCount > 0) {
            throw new \Exception("Cannot delete policy that is in use by {$inUseCount} hotels/bookings.");
        }

        return DB::transaction(function () use ($policy) {
            $policy->tiers()->delete();
            return $policy->delete();
        });
    }

    /**
     * Toggle policy status.
     */
    public function toggleStatus(int $id): CancellationPolicy
    {
        $policy = CancellationPolicy::findOrFail($id);
        $policy->update(['is_active' => !$policy->is_active]);
        return $policy->fresh();
    }

    /**
     * Clone a policy.
     */
    public function clonePolicy(int $id, string $newName): CancellationPolicy
    {
        $originalPolicy = CancellationPolicy::with('tiers')->findOrFail($id);

        return DB::transaction(function () use ($originalPolicy, $newName) {
            $newPolicy = CancellationPolicy::create([
                'name' => $newName,
                'description' => $originalPolicy->description,
                'is_refundable' => $originalPolicy->is_refundable,
                'is_active' => false, // Start as inactive
            ]);

            // Clone tiers
            foreach ($originalPolicy->tiers as $tier) {
                $newPolicy->tiers()->create([
                    'hours_before_checkin' => $tier->hours_before_checkin,
                    'refund_percentage' => $tier->refund_percentage,
                ]);
            }

            return $newPolicy->fresh('tiers');
        });
    }

    /**
     * Get default policies (seeder helper).
     */
    public static function getDefaultPolicies(): array
    {
        return [
            [
                'name' => 'Flexible',
                'description' => 'Full refund if cancelled 24 hours before check-in',
                'is_refundable' => true,
                'tiers' => [
                    ['hours_before_checkin' => 24, 'refund_percentage' => 100],
                    ['hours_before_checkin' => 0, 'refund_percentage' => 0],
                ],
            ],
            [
                'name' => 'Moderate',
                'description' => 'Full refund 5 days before, 50% if cancelled 24 hours before',
                'is_refundable' => true,
                'tiers' => [
                    ['hours_before_checkin' => 120, 'refund_percentage' => 100], // 5 days
                    ['hours_before_checkin' => 24, 'refund_percentage' => 50],
                    ['hours_before_checkin' => 0, 'refund_percentage' => 0],
                ],
            ],
            [
                'name' => 'Strict',
                'description' => '50% refund if cancelled 7 days before, no refund otherwise',
                'is_refundable' => true,
                'tiers' => [
                    ['hours_before_checkin' => 168, 'refund_percentage' => 50], // 7 days
                    ['hours_before_checkin' => 0, 'refund_percentage' => 0],
                ],
            ],
            [
                'name' => 'Non-Refundable',
                'description' => 'No refunds for any cancellation',
                'is_refundable' => false,
                'tiers' => [],
            ],
            [
                'name' => 'Super Flexible',
                'description' => 'Full refund anytime before check-in',
                'is_refundable' => true,
                'tiers' => [
                    ['hours_before_checkin' => 0, 'refund_percentage' => 100],
                ],
            ],
        ];
    }

    /**
     * Seed default policies.
     */
    public function seedDefaultPolicies(): int
    {
        $count = 0;

        foreach (self::getDefaultPolicies() as $policyData) {
            // Check if policy with same name exists
            if (CancellationPolicy::where('name', $policyData['name'])->exists()) {
                continue;
            }

            $this->createPolicy($policyData);
            $count++;
        }

        return $count;
    }

    /**
     * Get usage statistics for a policy.
     */
    public function getPolicyUsageStats(int $id): array
    {
        $hotelCount = \Modules\HotelBooking\Entities\Hotel::where('cancellation_policy_id', $id)->count();
        $bookingCount = \Modules\HotelBooking\Entities\BookingInformation::where('cancellation_policy_id', $id)->count();

        return [
            'hotels_using' => $hotelCount,
            'bookings_using' => $bookingCount,
            'total_usage' => $hotelCount + $bookingCount,
        ];
    }
}
