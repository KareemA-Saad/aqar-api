<?php

declare(strict_types=1);

namespace Modules\Appointment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Appointment\Services\AppointmentService;
use Modules\Appointment\Services\AppointmentBookingService;
use Modules\Appointment\Services\ScheduleService;
use Modules\Appointment\Services\SlotAvailabilityService;

/**
 * Appointment Module Service Provider
 *
 * Registers the Appointment module and its services.
 *
 * @package Modules\Appointment\Providers
 */
class AppointmentServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'Appointment';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'appointment';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        // Migrations are handled by tenancy
        // $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Register services as singletons
        $this->app->singleton(AppointmentService::class, function ($app) {
            return new AppointmentService();
        });

        $this->app->singleton(ScheduleService::class, function ($app) {
            return new ScheduleService();
        });

        $this->app->singleton(SlotAvailabilityService::class, function ($app) {
            return new SlotAvailabilityService();
        });

        $this->app->singleton(AppointmentBookingService::class, function ($app) {
            return new AppointmentBookingService(
                $app->make(SlotAvailabilityService::class)
            );
        });
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path($this->moduleNameLower . '.php'),
            ], 'config');

            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        if (is_dir($sourcePath)) {
            $this->publishes([
                $sourcePath => $viewPath,
            ], ['views', $this->moduleNameLower . '-module-views']);

            $this->loadViewsFrom(
                array_merge($this->getPublishableViewPaths(), [$sourcePath]),
                $this->moduleNameLower
            );
        }
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } elseif (is_dir($sourcePath)) {
            $this->loadTranslationsFrom($sourcePath, $this->moduleNameLower);
        }
    }

    /**
     * Get the publishable view paths.
     *
     * @return array
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (\Config::get('view.paths') as $path) {
            $viewPath = $path . '/modules/' . $this->moduleNameLower;
            if (is_dir($viewPath)) {
                $paths[] = $viewPath;
            }
        }

        return $paths;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            AppointmentService::class,
            ScheduleService::class,
            SlotAvailabilityService::class,
            AppointmentBookingService::class,
        ];
    }
}
