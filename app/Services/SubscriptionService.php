<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TenantRegisterEvent;
use App\Models\Coupon;
use App\Models\CouponLog;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SubscriptionService
 *
 * Handles subscription-related business logic including pricing calculations,
 * subscription initiation, completion, renewal, and cancellation.
 */
final class SubscriptionService
{
    /**
     * Plan type constants.
     */
    public const TYPE_MONTHLY = 0;
    public const TYPE_YEARLY = 1;
    public const TYPE_LIFETIME = 2;

    /**
     * Payment status constants.
     */
    public const PAYMENT_STATUS_PENDING = 0;
    public const PAYMENT_STATUS_COMPLETE = 1;

    /**
     * Calculate the final price for a plan with optional coupon.
     *
     * @return array{
     *     original_price: float,
     *     discount: float,
     *     final_price: float,
     *     coupon_applied: bool,
     *     coupon_code: string|null
     * }
     */
    public function calculatePrice(PricePlan $plan, ?Coupon $coupon = null): array
    {
        $originalPrice = (float) $plan->price;
        $discount = 0.0;
        $couponApplied = false;
        $couponCode = null;

        if ($coupon !== null && $coupon->isValid()) {
            $discount = $coupon->calculateDiscount($originalPrice);
            $couponApplied = true;
            $couponCode = $coupon->code;
        }

        $finalPrice = max(0, $originalPrice - $discount);

        return [
            'original_price' => $originalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'coupon_applied' => $couponApplied,
            'coupon_code' => $couponCode,
        ];
    }

    /**
     * Validate a coupon for a user and plan.
     *
     * @return array{
     *     valid: bool,
     *     message: string,
     *     coupon: Coupon|null,
     *     discount: float
     * }
     */
    public function validateCoupon(string $code, int $planId, int $userId): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon === null) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        if (!$coupon->isValid()) {
            return [
                'valid' => false,
                'message' => $coupon->expire_date?->isPast()
                    ? 'Coupon has expired.'
                    : 'Coupon is not active.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        if ($coupon->hasReachedLimitForUser($userId)) {
            return [
                'valid' => false,
                'message' => 'You have reached the maximum usage limit for this coupon.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        $plan = PricePlan::find($planId);
        if ($plan === null) {
            return [
                'valid' => false,
                'message' => 'Plan not found.',
                'coupon' => null,
                'discount' => 0,
            ];
        }

        $discount = $coupon->calculateDiscount((float) $plan->price);

        return [
            'valid' => true,
            'message' => 'Coupon is valid.',
            'coupon' => $coupon,
            'discount' => $discount,
        ];
    }

    /**
     * Initiate a new subscription.
     *
     * Creates a pending payment log that can be completed after payment.
     */
    public function initiateSubscription(
        User $user,
        PricePlan $plan,
        string $subdomain,
        ?string $theme = null,
        ?Coupon $coupon = null,
        ?string $paymentGateway = null,
        bool $isTrial = false
    ): PaymentLog {
        $pricing = $this->calculatePrice($plan, $coupon);

        return DB::transaction(function () use ($user, $plan, $subdomain, $theme, $coupon, $paymentGateway, $isTrial, $pricing) {
            $startDate = Carbon::now();
            $expireDate = $this->calculateExpireDate($plan, $startDate);

            $paymentLog = PaymentLog::create([
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $plan->title,
                'package_price' => $pricing['final_price'],
                'package_gateway' => $paymentGateway,
                'package_id' => $plan->id,
                'user_id' => $user->id,
                'tenant_id' => $subdomain,
                'status' => $isTrial ? 'trial' : 'pending',
                'payment_status' => $isTrial || $pricing['final_price'] == 0 ? self::PAYMENT_STATUS_COMPLETE : self::PAYMENT_STATUS_PENDING,
                'track' => $this->generateTrackCode(),
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'trial_expire_date' => $isTrial && $plan->trial_days > 0
                    ? $startDate->copy()->addDays($plan->trial_days)
                    : null,
                'theme' => $theme,
                'is_renew' => false,
                'coupon_id' => $coupon?->id,
                'coupon_discount' => $pricing['discount'],
                'unique_key' => Str::uuid()->toString(),
            ]);

            // Log coupon usage
            if ($coupon !== null) {
                CouponLog::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'payment_log_id' => $paymentLog->id,
                    'discount_amount' => $pricing['discount'],
                ]);
            }

            return $paymentLog;
        });
    }

    /**
     * Complete a subscription after successful payment.
     */
    public function completeSubscription(
        PaymentLog $paymentLog,
        string $transactionId,
        ?string $paymentGateway = null
    ): Tenant {
        return DB::transaction(function () use ($paymentLog, $transactionId, $paymentGateway) {
            // Update payment log
            $paymentLog->update([
                'transaction_id' => $transactionId,
                'status' => $paymentLog->status === 'trial' ? 'trial' : 'complete',
                'payment_status' => self::PAYMENT_STATUS_COMPLETE,
                'package_gateway' => $paymentGateway ?? $paymentLog->package_gateway,
            ]);

            // Get or create tenant
            $tenant = Tenant::find($paymentLog->tenant_id);

            if ($tenant === null) {
                // Create new tenant
                $tenant = $this->createTenant($paymentLog);
            } else {
                // Update existing tenant (plan change detected)
                $this->updateTenant($tenant, $paymentLog);
                
                // Check if plan changed and run new module migrations
                $this->handlePlanChange($tenant, $paymentLog);
            }

            // Update user
            $user = User::find($paymentLog->user_id);
            if ($user !== null) {
                $user->update(['has_subdomain' => true]);
            }

            return $tenant;
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(PaymentLog $existingLog): PaymentLog
    {
        $plan = PricePlan::find($existingLog->package_id);
        if ($plan === null) {
            throw new \InvalidArgumentException('Plan not found for renewal.');
        }

        $user = User::find($existingLog->user_id);
        if ($user === null) {
            throw new \InvalidArgumentException('User not found for renewal.');
        }

        return DB::transaction(function () use ($existingLog, $plan, $user) {
            // Calculate new dates based on current expiration
            $startDate = $existingLog->expire_date?->isFuture()
                ? $existingLog->expire_date
                : Carbon::now();

            $expireDate = $this->calculateExpireDate($plan, $startDate);

            $newLog = PaymentLog::create([
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $plan->title,
                'package_price' => $plan->price,
                'package_gateway' => $existingLog->package_gateway,
                'package_id' => $plan->id,
                'user_id' => $user->id,
                'tenant_id' => $existingLog->tenant_id,
                'status' => 'pending',
                'payment_status' => self::PAYMENT_STATUS_PENDING,
                'track' => $this->generateTrackCode(),
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'theme' => $existingLog->theme,
                'is_renew' => true,
                'renew_status' => true,
                'unique_key' => Str::uuid()->toString(),
            ]);

            return $newLog;
        });
    }

    /**
     * Upgrade to a new plan.
     *
     * @return array{
     *     payment_log: PaymentLog,
     *     prorated_amount: float,
     *     credit_remaining: float
     * }
     */
    public function upgradeSubscription(
        PaymentLog $currentLog,
        PricePlan $newPlan,
        ?Coupon $coupon = null
    ): array {
        $user = User::find($currentLog->user_id);
        if ($user === null) {
            throw new \InvalidArgumentException('User not found for upgrade.');
        }

        $currentPlan = PricePlan::find($currentLog->package_id);
        if ($currentPlan === null) {
            throw new \InvalidArgumentException('Current plan not found.');
        }

        // Calculate prorated credit
        $creditRemaining = $this->calculateProratedCredit($currentLog, $currentPlan);
        $newPricing = $this->calculatePrice($newPlan, $coupon);

        $proratedAmount = max(0, $newPricing['final_price'] - $creditRemaining);

        return DB::transaction(function () use ($user, $newPlan, $currentLog, $coupon, $newPricing, $proratedAmount, $creditRemaining) {
            $startDate = Carbon::now();
            $expireDate = $this->calculateExpireDate($newPlan, $startDate);

            $paymentLog = PaymentLog::create([
                'email' => $user->email,
                'name' => $user->name,
                'package_name' => $newPlan->title,
                'package_price' => $proratedAmount,
                'package_gateway' => $currentLog->package_gateway,
                'package_id' => $newPlan->id,
                'user_id' => $user->id,
                'tenant_id' => $currentLog->tenant_id,
                'status' => $proratedAmount == 0 ? 'complete' : 'pending',
                'payment_status' => $proratedAmount == 0 ? self::PAYMENT_STATUS_COMPLETE : self::PAYMENT_STATUS_PENDING,
                'track' => $this->generateTrackCode(),
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'theme' => $currentLog->theme,
                'is_renew' => false,
                'coupon_id' => $coupon?->id,
                'coupon_discount' => $newPricing['discount'],
                'unique_key' => Str::uuid()->toString(),
            ]);

            // Log coupon usage
            if ($coupon !== null) {
                CouponLog::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'payment_log_id' => $paymentLog->id,
                    'discount_amount' => $newPricing['discount'],
                ]);
            }

            // If no payment needed, complete immediately
            if ($proratedAmount == 0) {
                $tenant = Tenant::find($currentLog->tenant_id);
                if ($tenant !== null) {
                    $this->updateTenant($tenant, $paymentLog);
                }
            }

            return [
                'payment_log' => $paymentLog,
                'prorated_amount' => $proratedAmount,
                'credit_remaining' => $creditRemaining,
            ];
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(PaymentLog $paymentLog, string $reason): bool
    {
        return DB::transaction(function () use ($paymentLog, $reason) {
            $paymentLog->update([
                'status' => 'cancelled',
                'custom_fields' => array_merge($paymentLog->custom_fields ?? [], [
                    'cancellation_reason' => $reason,
                    'cancelled_at' => Carbon::now()->toISOString(),
                ]),
            ]);

            return true;
        });
    }

    /**
     * Get user's current active subscription.
     */
    public function getCurrentSubscription(User $user): ?PaymentLog
    {
        return PaymentLog::where('user_id', $user->id)
            ->where('payment_status', self::PAYMENT_STATUS_COMPLETE)
            ->where(function ($query) {
                $query->whereNull('expire_date')
                    ->orWhere('expire_date', '>', Carbon::now());
            })
            ->whereNotIn('status', ['cancelled'])
            ->with(['package', 'tenant'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get user's subscription history.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSubscriptionHistory(User $user, int $perPage = 15)
    {
        return PaymentLog::where('user_id', $user->id)
            ->with(['package', 'tenant'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Calculate expiration date based on plan type.
     */
    private function calculateExpireDate(PricePlan $plan, Carbon $startDate): ?Carbon
    {
        return match ($plan->type) {
            self::TYPE_MONTHLY => $startDate->copy()->addMonth(),
            self::TYPE_YEARLY => $startDate->copy()->addYear(),
            self::TYPE_LIFETIME => null, // No expiration
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Calculate prorated credit for upgrade.
     */
    private function calculateProratedCredit(PaymentLog $currentLog, PricePlan $currentPlan): float
    {
        if ($currentLog->expire_date === null) {
            return 0; // Lifetime plans don't get prorated
        }

        if ($currentLog->expire_date->isPast()) {
            return 0; // Already expired
        }

        $totalDays = match ($currentPlan->type) {
            self::TYPE_MONTHLY => 30,
            self::TYPE_YEARLY => 365,
            default => 30,
        };

        $daysRemaining = (int) Carbon::now()->diffInDays($currentLog->expire_date, false);
        $daysRemaining = max(0, $daysRemaining);

        $dailyRate = (float) $currentLog->package_price / $totalDays;

        return round($dailyRate * $daysRemaining, 2);
    }

    /**
     * Generate a unique tracking code.
     */
    private function generateTrackCode(): string
    {
        return 'TRK' . strtoupper(Str::random(10)) . time();
    }

    /**
     * Create a new tenant from payment log.
     */
    private function createTenant(PaymentLog $paymentLog): Tenant
    {
        $tenant = Tenant::create([
            'id' => $paymentLog->tenant_id,
            'user_id' => $paymentLog->user_id,
            'theme' => $paymentLog->theme,
        ]);

        // Create domain
        $tenant->domains()->create([
            'domain' => $paymentLog->tenant_id,
        ]);

        // Fire tenant creation event (for database setup, etc.)
        $user = User::find($paymentLog->user_id);
        if ($user !== null) {
            event(new TenantRegisterEvent($user, $tenant->id, $paymentLog->theme));
        }

        return $tenant;
    }

    /**
     * Update existing tenant with new subscription data.
     */
    private function updateTenant(Tenant $tenant, PaymentLog $paymentLog): void
    {
        DB::table('tenants')->where('id', $tenant->id)->update([
            'start_date' => $paymentLog->start_date,
            'expire_date' => $paymentLog->expire_date,
            'theme_slug' => $paymentLog->theme,
            'user_id' => $paymentLog->user_id,
        ]);
    }

    /**
     * Handle plan change and run new module migrations if needed.
     *
     * @param Tenant $tenant
     * @param PaymentLog $newPaymentLog
     * @return void
     */
    private function handlePlanChange(Tenant $tenant, PaymentLog $newPaymentLog): void
    {
        // Get the previous payment log
        $previousLog = PaymentLog::where('tenant_id', $tenant->id)
            ->where('id', '<', $newPaymentLog->id)
            ->where('payment_status', self::PAYMENT_STATUS_COMPLETE)
            ->with(['package.planFeatures'])
            ->orderByDesc('id')
            ->first();

        // If no previous log, this is likely a first subscription - skip
        if ($previousLog === null || $previousLog->package_id === $newPaymentLog->package_id) {
            return;
        }

        $tenantService = app(TenantService::class);

        // Load packages with features
        $oldPlan = $previousLog->package;
        $newPlan = $newPaymentLog->package()->with('planFeatures')->first();

        if ($oldPlan === null || $newPlan === null) {
            Log::warning('Cannot detect plan change: package not found', [
                'tenant_id' => $tenant->id,
                'old_plan_id' => $previousLog->package_id,
                'new_plan_id' => $newPaymentLog->package_id,
            ]);
            return;
        }

        // Get modules for both plans
        $oldModules = $tenantService->getModulesForPlan($oldPlan);
        $newModules = $tenantService->getModulesForPlan($newPlan);

        // Find newly enabled modules
        $addedModules = array_diff($newModules, $oldModules);

        if (!empty($addedModules)) {
            Log::info('Plan upgrade detected, running new module migrations', [
                'tenant_id' => $tenant->id,
                'old_plan' => $oldPlan->id,
                'new_plan' => $newPlan->id,
                'old_modules' => $oldModules,
                'new_modules' => $newModules,
                'added_modules' => $addedModules,
            ]);

            // Run migrations for new modules
            $tenantService->runModuleMigrationsForUpgrade($tenant, $addedModules);
        } else {
            Log::info('Plan changed but no new modules detected', [
                'tenant_id' => $tenant->id,
                'old_plan' => $oldPlan->id,
                'new_plan' => $newPlan->id,
            ]);
        }
    }
}
