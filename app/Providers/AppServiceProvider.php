<?php

namespace App\Providers;

use App\Helpers\LanguageHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register GlobalLanguage singleton for language helper access
        $this->app->singleton('GlobalLanguage', function ($app) {
            return new LanguageHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
