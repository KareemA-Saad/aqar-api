<?php

declare(strict_types=1);

namespace Modules\Attributes\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Route Service Provider for Attributes Module
 *
 * @package Modules\Attributes\Providers
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected string $moduleNamespace = 'Modules\Attributes\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map(): void
    {
        $this->mapApiRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(module_path('Attributes', '/Routes/api.php'));
    }
}
