<?php

declare(strict_types=1);

namespace Modules\RealEstate\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * RealEstate Module Service Provider
 *
 * @package Modules\RealEstate\Providers
 */
class RealEstateServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'RealEstate';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'realestate';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Register module services
        $this->registerServices();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register translations.
     *
     * @return void
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Register module services.
     *
     * @return void
     */
    protected function registerServices(): void
    {
        $this->app->singleton(\Modules\RealEstate\Services\PropertyService::class);
        $this->app->singleton(\Modules\RealEstate\Services\CompoundService::class);
        $this->app->singleton(\Modules\RealEstate\Services\AreaService::class);
        $this->app->singleton(\Modules\RealEstate\Services\InquiryService::class);
        $this->app->singleton(\Modules\RealEstate\Services\SearchService::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }
}
