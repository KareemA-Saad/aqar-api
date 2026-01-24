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
        $this->registerViews();

        // Migrations are handled by TenantService based on plan features.
        // DO NOT use loadMigrationsFrom() as it would run migrations on central DB.
        // RealEstate tables belong in tenant databases only.
        // See: TenantService::runTenantMigrations() and config/modules.php feature_module_map
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
     * Register views.
     *
     * @return void
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Get publishable view paths.
     *
     * @return array<string>
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
