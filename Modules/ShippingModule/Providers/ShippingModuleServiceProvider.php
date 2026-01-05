<?php

declare(strict_types=1);

namespace Modules\ShippingModule\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * ShippingModule Service Provider
 */
class ShippingModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'ShippingModule';
    protected string $moduleNameLower = 'shippingmodule';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->publishes([$configPath => config_path($this->moduleNameLower . '.php')], 'config');
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $moduleLangPath = module_path($this->moduleName, 'Resources/lang');
            if (is_dir($moduleLangPath)) {
                $this->loadTranslationsFrom($moduleLangPath, $this->moduleNameLower);
            }
        }
    }

    public function provides(): array
    {
        return [];
    }
}
