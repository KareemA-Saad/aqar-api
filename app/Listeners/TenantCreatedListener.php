<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\CreateTenantDatabase;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\TenantCreated;

/**
 * Tenant Created Listener
 *
 * Listens to tenant.created event and triggers database setup.
 * This is an alternative to manual dispatching in TenantService.
 */
final class TenantCreatedListener
{
    /**
     * Handle the event.
     *
     * @param TenantCreated $event
     * @return void
     */
    public function handle(TenantCreated $event): void
    {
        /** @var Tenant $tenant */
        $tenant = $event->tenant;

        Log::info('TenantCreated event received', [
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->user_id,
        ]);

        // Only dispatch if auto_create is disabled (we handle it ourselves)
        if (!config('tenancy.database.auto_create', false)) {
            CreateTenantDatabase::dispatch($tenant);

            Log::info('CreateTenantDatabase job dispatched', [
                'tenant_id' => $tenant->id,
            ]);
        }
    }

    /**
     * Handle a failed event.
     *
     * @param TenantCreated $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(TenantCreated $event, \Throwable $exception): void
    {
        Log::error('TenantCreatedListener failed', [
            'tenant_id' => $event->tenant->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

