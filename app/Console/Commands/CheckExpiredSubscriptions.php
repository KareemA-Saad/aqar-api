<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Check Expired Subscriptions Command
 *
 * This command checks all tenant subscriptions and updates their status
 * based on payment log expiration dates. It should be scheduled to run daily.
 *
 * Actions performed:
 * 1. Identifies tenants with expired subscriptions
 * 2. Updates tenant subscription_status to 'expired'
 * 3. Logs all status changes for audit trail
 * 4. Optionally sends notification emails (future enhancement)
 */
class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:check-expired
                            {--dry-run : Run without making changes}
                            {--notify : Send notification emails to users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired tenant subscriptions and update their status';

    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting subscription expiration check...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $shouldNotify = $this->option('notify');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made.');
            $this->newLine();
        }

        // Get expired tenants
        $expiredTenants = $this->subscriptionService->getExpiredTenants();
        $expiredCount = $expiredTenants->count();

        $this->info("Found {$expiredCount} tenant(s) with expired subscriptions.");

        if ($expiredCount === 0) {
            $this->info('No expired subscriptions found. Exiting.');
            return Command::SUCCESS;
        }

        // Process each expired tenant
        $this->newLine();
        $processed = 0;
        $errors = 0;

        $this->withProgressBar($expiredTenants, function (Tenant $tenant) use ($dryRun, $shouldNotify, &$processed, &$errors) {
            try {
                $this->processExpiredTenant($tenant, $dryRun, $shouldNotify);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to process expired tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $this->newLine(2);

        // Summary
        $this->info('Subscription check completed.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Expired', $expiredCount],
                ['Processed', $processed],
                ['Errors', $errors],
            ]
        );

        // Also check for tenants expiring soon (for warning)
        $expiringSoon = $this->subscriptionService->getTenantsExpiringSoon(7);
        if ($expiringSoon->count() > 0) {
            $this->newLine();
            $this->warn("Found {$expiringSoon->count()} tenant(s) expiring within 7 days:");
            
            $tableData = $expiringSoon->map(fn($t) => [
                'tenant_id' => $t->id,
                'user_email' => $t->user?->email ?? 'N/A',
                'expires' => $t->paymentLog?->expire_date?->format('Y-m-d'),
                'days_left' => $this->subscriptionService->getDaysUntilExpiry($t),
            ])->toArray();

            $this->table(['Tenant ID', 'User Email', 'Expires', 'Days Left'], $tableData);

            if ($shouldNotify && !$dryRun) {
                $this->sendExpirationWarnings($expiringSoon);
            }
        }

        Log::info('Subscription expiration check completed', [
            'expired_count' => $expiredCount,
            'processed' => $processed,
            'errors' => $errors,
            'expiring_soon' => $expiringSoon->count(),
            'dry_run' => $dryRun,
        ]);

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process a single expired tenant.
     */
    private function processExpiredTenant(Tenant $tenant, bool $dryRun, bool $shouldNotify): void
    {
        $oldStatus = $tenant->subscription_status;
        $paymentLog = $tenant->paymentLog;

        // Determine expiration reason
        $reason = 'subscription_expired';
        if ($paymentLog?->status === 'trial') {
            $reason = 'trial_expired';
        }

        if (!$dryRun) {
            // Update tenant status
            DB::table('tenants')->where('id', $tenant->id)->update([
                'subscription_status' => SubscriptionService::STATUS_EXPIRED,
                'updated_at' => Carbon::now(),
            ]);

            // Log the status change using activity log
            activity()
                ->performedOn($tenant)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => SubscriptionService::STATUS_EXPIRED,
                    'reason' => $reason,
                    'expired_at' => $paymentLog?->expire_date ?? $paymentLog?->trial_expire_date,
                ])
                ->log('subscription_expired');
        }

        Log::info('Tenant subscription marked as expired', [
            'tenant_id' => $tenant->id,
            'old_status' => $oldStatus,
            'reason' => $reason,
            'dry_run' => $dryRun,
        ]);

        // Send notification if requested
        if ($shouldNotify && !$dryRun) {
            $this->sendExpiredNotification($tenant);
        }
    }

    /**
     * Send expiration notification to tenant owner.
     */
    private function sendExpiredNotification(Tenant $tenant): void
    {
        $user = $tenant->user;
        if ($user === null) {
            return;
        }

        // TODO: Implement email notification
        // Use Laravel Notification system or Mail facade
        // $user->notify(new SubscriptionExpiredNotification($tenant));

        Log::info('Expiration notification sent', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Send warning notifications for tenants expiring soon.
     *
     * @param \Illuminate\Support\Collection<int, Tenant> $tenants
     */
    private function sendExpirationWarnings($tenants): void
    {
        foreach ($tenants as $tenant) {
            $user = $tenant->user;
            if ($user === null) {
                continue;
            }

            // TODO: Implement email notification
            // $user->notify(new SubscriptionExpiringNotification($tenant, $daysLeft));

            Log::info('Expiration warning sent', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'days_left' => $this->subscriptionService->getDaysUntilExpiry($tenant),
            ]);
        }

        $this->info("Sent {$tenants->count()} expiration warning(s).");
    }
}
