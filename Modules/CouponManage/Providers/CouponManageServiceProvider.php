<?php

declare(strict_types=1);

namespace Modules\CouponManage\Providers;

use Illuminate\Support\ServiceProvider;

class CouponManageServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CouponManage';
    protected string $moduleNameLower = 'couponmanage';

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
        }
    }

    public function provides(): array
    {
        return [];
    }
}
