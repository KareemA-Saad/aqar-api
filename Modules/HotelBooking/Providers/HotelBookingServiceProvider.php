<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\HotelBooking\Services\HotelService;
use Modules\HotelBooking\Services\RoomTypeService;
use Modules\HotelBooking\Services\RoomService;
use Modules\HotelBooking\Services\AmenityService;
use Modules\HotelBooking\Services\InventoryService;
use Modules\HotelBooking\Services\BookingService;
use Modules\HotelBooking\Services\PricingService;
use Modules\HotelBooking\Services\RoomSearchService;
use Modules\HotelBooking\Services\RoomHoldService;
use Modules\HotelBooking\Services\HotelPaymentService;
use Modules\HotelBooking\Services\RefundService;
use Modules\HotelBooking\Services\CancellationPolicyService;

/**
 * Hotel Booking Module Service Provider
 *
 * Registers the Hotel Booking module and its services.
 *
 * @package Modules\HotelBooking\Providers
 */
class HotelBookingServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'HotelBooking';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'hotelbooking';

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
        $this->app->singleton(HotelService::class, function ($app) {
            return new HotelService();
        });

        $this->app->singleton(RoomTypeService::class, function ($app) {
            return new RoomTypeService();
        });

        $this->app->singleton(RoomService::class, function ($app) {
            return new RoomService();
        });

        $this->app->singleton(AmenityService::class, function ($app) {
            return new AmenityService();
        });

        $this->app->singleton(InventoryService::class, function ($app) {
            return new InventoryService();
        });

        $this->app->singleton(PricingService::class, function ($app) {
            return new PricingService(
                $app->make(InventoryService::class)
            );
        });

        $this->app->singleton(RoomSearchService::class, function ($app) {
            return new RoomSearchService();
        });

        $this->app->singleton(CancellationPolicyService::class, function ($app) {
            return new CancellationPolicyService();
        });

        $this->app->singleton(RoomHoldService::class, function ($app) {
            return new RoomHoldService(
                $app->make(InventoryService::class),
                $app->make(PricingService::class)
            );
        });

        $this->app->singleton(RefundService::class, function ($app) {
            return new RefundService(
                $app->make(PricingService::class)
            );
        });

        $this->app->singleton(HotelPaymentService::class, function ($app) {
            return new HotelPaymentService();
        });

        $this->app->singleton(BookingService::class, function ($app) {
            return new BookingService(
                $app->make(RoomHoldService::class),
                $app->make(InventoryService::class),
                $app->make(PricingService::class)
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

            $this->mergeConfigFrom(
                $configPath, $this->moduleNameLower
            );
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

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Get the publishable view paths.
     *
     * @return array
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        return $paths;
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            HotelService::class,
            RoomTypeService::class,
            RoomService::class,
            AmenityService::class,
            InventoryService::class,
            PricingService::class,
            RoomSearchService::class,
            RoomHoldService::class,
            CancellationPolicyService::class,
            RefundService::class,
            HotelPaymentService::class,
            BookingService::class,
        ];
    }
}
