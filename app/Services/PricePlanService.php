<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanFeature;
use App\Models\PricePlan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PricePlanService
 *
 * Handles price plan management operations.
 */
final class PricePlanService
{
    /**
     * Get all price plans for admin with pagination.
     *
     * @param array<string, mixed> $filters
     */
    public function getPlansForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = PricePlan::with('planFeatures')
            ->orderByDesc('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get all active price plans for public display.
     */
    public function getActivePlans(): Collection
    {
        return PricePlan::with('planFeatures')
            ->where('status', true)
            ->orderBy('price')
            ->get();
    }

    /**
     * Get a single plan by ID.
     */
    public function getPlanById(int $id): ?PricePlan
    {
        return PricePlan::with('planFeatures')->find($id);
    }

    /**
     * Get a single plan by slug (using title as slug).
     */
    public function getPlanBySlug(string $slug): ?PricePlan
    {
        // Convert slug to search pattern
        $search = str_replace('-', ' ', $slug);

        return PricePlan::with('planFeatures')
            ->where('status', true)
            ->where('title', 'like', "%{$search}%")
            ->first();
    }

    /**
     * Create a new price plan.
     *
     * @param array<string, mixed> $data
     */
    public function createPlan(array $data): PricePlan
    {
        return DB::transaction(function () use ($data) {
            $features = $data['features'] ?? [];
            unset($data['features']);

            $plan = PricePlan::create($data);

            if (!empty($features)) {
                $this->syncFeatures($plan, $features);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * Update an existing price plan.
     *
     * @param array<string, mixed> $data
     */
    public function updatePlan(PricePlan $plan, array $data): PricePlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $features = $data['features'] ?? null;
            unset($data['features']);

            $plan->update($data);

            if ($features !== null) {
                $this->syncFeatures($plan, $features);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * Delete a price plan.
     */
    public function deletePlan(PricePlan $plan): bool
    {
        return DB::transaction(function () use ($plan) {
            // Delete associated features
            $plan->planFeatures()->delete();

            return $plan->delete();
        });
    }

    /**
     * Toggle plan status.
     */
    public function toggleStatus(PricePlan $plan): PricePlan
    {
        $plan->update([
            'status' => !$plan->status,
        ]);

        return $plan;
    }

    /**
     * Reorder plan features.
     *
     * @param array<int, int> $featureIds
     */
    public function reorderFeatures(PricePlan $plan, array $featureIds): PricePlan
    {
        return DB::transaction(function () use ($plan, $featureIds) {
            foreach ($featureIds as $order => $featureId) {
                PlanFeature::where('id', $featureId)
                    ->where('plan_id', $plan->id)
                    ->update(['order' => $order + 1]);
            }

            return $plan->load('planFeatures');
        });
    }

    /**
     * Get comparison matrix for all active plans.
     *
     * @return array<string, mixed>
     */
    public function getComparisonMatrix(): array
    {
        $plans = $this->getActivePlans();

        // Collect all unique features
        $allFeatures = [];
        foreach ($plans as $plan) {
            foreach ($plan->planFeatures as $feature) {
                $allFeatures[$feature->feature_name] = true;
            }
        }

        $featureNames = array_keys($allFeatures);

        // Build comparison matrix
        $matrix = [];
        foreach ($plans as $plan) {
            $planFeatures = $plan->planFeatures->pluck('feature_name')->toArray();

            $matrix[] = [
                'id' => $plan->id,
                'title' => $plan->title,
                'subtitle' => $plan->subtitle,
                'price' => (float) $plan->price,
                'type' => $plan->type,
                'type_label' => $this->getTypeLabel($plan->type),
                'has_trial' => (bool) ($plan->has_trial ?? ($plan->free_trial > 0)),
                'trial_days' => (int) ($plan->trial_days ?? $plan->free_trial ?? 0),
                'permissions' => [
                    'page' => $plan->page_permission_feature,
                    'blog' => $plan->blog_permission_feature,
                    'product' => $plan->product_permission_feature,
                    'portfolio' => $plan->portfolio_permission_feature,
                    'storage' => $plan->storage_permission_feature,
                    'appointment' => $plan->appointment_permission_feature,
                ],
                'features' => array_map(
                    fn ($name) => [
                        'name' => $name,
                        'included' => in_array($name, $planFeatures),
                    ],
                    $featureNames
                ),
            ];
        }

        return [
            'plans' => $matrix,
            'feature_names' => $featureNames,
        ];
    }

    /**
     * Sync features for a plan.
     *
     * @param array<int, array<string, mixed>> $features
     */
    private function syncFeatures(PricePlan $plan, array $features): void
    {
        // Get existing feature IDs
        $existingIds = $plan->planFeatures()->pluck('id')->toArray();
        $newIds = [];

        foreach ($features as $index => $feature) {
            $featureId = $feature['id'] ?? null;

            if ($featureId !== null && in_array($featureId, $existingIds)) {
                // Update existing
                PlanFeature::where('id', $featureId)->update([
                    'feature_name' => $feature['feature_name'],
                    'status' => (bool) ($feature['status'] ?? true),
                    'order' => $index + 1,
                ]);
                $newIds[] = $featureId;
            } else {
                // Create new
                $newFeature = PlanFeature::create([
                    'plan_id' => $plan->id,
                    'feature_name' => $feature['feature_name'],
                    'status' => (bool) ($feature['status'] ?? true),
                    'order' => $index + 1,
                ]);
                $newIds[] = $newFeature->id;
            }
        }

        // Delete removed features
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            PlanFeature::whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Get type label.
     */
    private function getTypeLabel(int $type): string
    {
        return match ($type) {
            0 => 'Monthly',
            1 => 'Yearly',
            2 => 'Lifetime',
            default => 'Unknown',
        };
    }
}
