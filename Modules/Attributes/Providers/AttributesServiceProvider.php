<?php

declare(strict_types=1);

namespace Modules\Attributes\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Attributes Module Service Provider
 *
 * Registers the Attributes module (Categories, Brands, Colors, Sizes, Tags).
 *
 * @package Modules\Attributes\Providers
 */
class AttributesServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'Attributes';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'attributes';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
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
            $moduleLangPath = module_path($this->moduleName, 'Resources/lang');
            if (is_dir($moduleLangPath)) {
                $this->loadTranslationsFrom($moduleLangPath, $this->moduleNameLower);
                $this->loadJsonTranslationsFrom($moduleLangPath);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }
}
