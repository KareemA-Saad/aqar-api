<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Create Tenant Database Job
 *
 * Queued job for creating tenant database, running migrations, and seeding.
 * This is dispatched when a new tenant is created to avoid blocking the request.
 */
final class CreateTenantDatabase implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout;

    /**
     * Create a new job instance.
     *
     * @param Tenant $tenant
     */
    public function __construct(
        public readonly Tenant $tenant,
    ) {
        $this->tries = config('tenancy.queue.tries', 3);
        $this->timeout = config('tenancy.queue.timeout', 300);
        $this->onQueue(config('tenancy.queue.queue', 'tenant-operations'));
        $this->onConnection(config('tenancy.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     *
     * @param TenantService $tenantService
     * @return void
     */
    public function handle(TenantService $tenantService): void
    {
        Log::info('Starting tenant database creation', [
            'tenant_id' => $this->tenant->id,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Create database
            $tenantService->createDatabase($this->tenant);

            // Run migrations
            $tenantService->runTenantMigrations($this->tenant);

            // Seed data if configured
            if (config('tenancy.database.auto_seed', false)) {
                $tenantService->seedTenantData($this->tenant);
            }

            Log::info('Tenant database created successfully', [
                'tenant_id' => $this->tenant->id,
            ]);

            // TODO: Dispatch notification event
            // event(new TenantDatabaseCreated($this->tenant));

        } catch (\Exception $e) {
            Log::error('Tenant database creation failed', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Tenant database creation permanently failed', [
            'tenant_id' => $this->tenant->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send notification to admin
        // Mail::to(config('mail.admin_email'))->send(new TenantDatabaseCreationFailed($this->tenant, $exception));
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 60s, 120s
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'tenant:' . $this->tenant->id,
            'tenant-database-creation',
        ];
    }
}

