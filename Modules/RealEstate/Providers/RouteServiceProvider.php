<?php

declare(strict_types=1);

namespace Modules\RealEstate\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Route Service Provider for RealEstate Module
 *
 * @package Modules\RealEstate\Providers
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected string $moduleNamespace = 'Modules\RealEstate\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
        
        // Custom route model binding for {id}-{slug} pattern
        Route::bind('propertyIdSlug', function ($value) {
            return $this->resolveIdSlug($value, \Modules\RealEstate\Entities\Property::class);
        });
        
        Route::bind('compoundIdSlug', function ($value) {
            return $this->resolveIdSlug($value, \Modules\RealEstate\Entities\Compound::class);
        });
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
        Route::middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('RealEstate', '/Routes/api.php'));
    }

    /**
     * Resolve {id}-{slug} pattern to model instance
     *
     * @param string $value
     * @param string $modelClass
     * @return mixed
     */
    protected function resolveIdSlug(string $value, string $modelClass): mixed
    {
        // Check if it's numeric (just ID)
        if (is_numeric($value)) {
            return $modelClass::findOrFail($value);
        }
        
        // Try to extract ID from {id}-{slug} pattern
        if (preg_match('/^(\d+)-/', $value, $matches)) {
            return $modelClass::findOrFail($matches[1]);
        }
        
        // Fall back to slug lookup
        return $modelClass::where('slug', $value)->firstOrFail();
    }
}
