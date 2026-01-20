<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Admin Controllers
use Modules\RealEstate\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use Modules\RealEstate\Http\Controllers\Admin\CompoundController as AdminCompoundController;
use Modules\RealEstate\Http\Controllers\Admin\AreaController as AdminAreaController;
use Modules\RealEstate\Http\Controllers\Admin\DeveloperController as AdminDeveloperController;
use Modules\RealEstate\Http\Controllers\Admin\PropertyTypeController as AdminPropertyTypeController;
use Modules\RealEstate\Http\Controllers\Admin\AmenityController as AdminAmenityController;
use Modules\RealEstate\Http\Controllers\Admin\PropertyInquiryController as AdminPropertyInquiryController;

// Frontend Controllers
use Modules\RealEstate\Http\Controllers\Frontend\PropertyController as FrontendPropertyController;
use Modules\RealEstate\Http\Controllers\Frontend\CompoundController as FrontendCompoundController;
use Modules\RealEstate\Http\Controllers\Frontend\AreaController as FrontendAreaController;
use Modules\RealEstate\Http\Controllers\Frontend\DeveloperController as FrontendDeveloperController;
use Modules\RealEstate\Http\Controllers\Frontend\PropertyTypeController as FrontendPropertyTypeController;
use Modules\RealEstate\Http\Controllers\Frontend\AmenityController as FrontendAmenityController;
use Modules\RealEstate\Http\Controllers\Frontend\PropertyInquiryController as FrontendPropertyInquiryController;
use Modules\RealEstate\Http\Controllers\Frontend\SearchController as FrontendSearchController;
use Modules\RealEstate\Http\Controllers\Frontend\SavedPropertyController;

/*
|--------------------------------------------------------------------------
| RealEstate Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the RealEstate module.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// ========================================
// TIER 1: PUBLIC ROUTES (Frontend - No Auth Required)
// ========================================
Route::prefix('realestate')
    ->name('realestate.')
    ->group(function () {
        
        // ----------------------------------------
        // Properties
        // ----------------------------------------
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::get('/', [FrontendPropertyController::class, 'index'])->name('index');
            Route::get('/featured', [FrontendPropertyController::class, 'featured'])->name('featured');
            Route::get('/{property}', [FrontendPropertyController::class, 'show'])
                ->name('show')
                ->where('property', '[0-9]+-.*'); // Nawy-style: {id}-{slug}
            Route::get('/{property}/similar', [FrontendPropertyController::class, 'similar'])
                ->name('similar')
                ->where('property', '[0-9]+-.*');
        });
        
        // ----------------------------------------
        // Compounds
        // ----------------------------------------
        Route::prefix('compounds')->name('compounds.')->group(function () {
            Route::get('/', [FrontendCompoundController::class, 'index'])->name('index');
            Route::get('/featured', [FrontendCompoundController::class, 'featured'])->name('featured');
            Route::get('/{compound}', [FrontendCompoundController::class, 'show'])
                ->name('show')
                ->where('compound', '[0-9]+-.*');
            Route::get('/{compound}/properties', [FrontendCompoundController::class, 'properties'])
                ->name('properties')
                ->where('compound', '[0-9]+-.*');
        });
        
        // ----------------------------------------
        // Areas/Locations (Hierarchical)
        // ----------------------------------------
        Route::prefix('areas')->name('areas.')->group(function () {
            Route::get('/tree', [FrontendAreaController::class, 'tree'])->name('tree');
            Route::get('/cities', [FrontendAreaController::class, 'cities'])->name('cities');
            Route::get('/featured', [FrontendAreaController::class, 'featured'])->name('featured');
            Route::get('/{slug}', [FrontendAreaController::class, 'show'])->name('show');
            Route::get('/{area}/children', [FrontendAreaController::class, 'children'])->name('children');
            Route::get('/{area}/compounds', [FrontendAreaController::class, 'compounds'])->name('compounds');
            Route::get('/{area}/properties', [FrontendAreaController::class, 'properties'])->name('properties');
            Route::get('/{area}/breadcrumbs', [FrontendAreaController::class, 'breadcrumbs'])->name('breadcrumbs');
        });
        
        // ----------------------------------------
        // Developers
        // ----------------------------------------
        Route::prefix('developers')->name('developers.')->group(function () {
            Route::get('/', [FrontendDeveloperController::class, 'index'])->name('index');
            Route::get('/featured', [FrontendDeveloperController::class, 'featured'])->name('featured');
            Route::get('/{developer}', [FrontendDeveloperController::class, 'show'])
                ->name('show')
                ->where('developer', '[0-9]+-.*');
            Route::get('/{developer}/compounds', [FrontendDeveloperController::class, 'compounds'])
                ->name('compounds')
                ->where('developer', '[0-9]+-.*');
        });
        
        // ----------------------------------------
        // Property Types & Amenities (Lookups)
        // ----------------------------------------
        Route::get('property-types', [FrontendPropertyTypeController::class, 'index'])->name('property-types.index');
        Route::get('property-types/{slug}', [FrontendPropertyTypeController::class, 'show'])->name('property-types.show');
        Route::get('amenities', [FrontendAmenityController::class, 'index'])->name('amenities.index');
        
        // ----------------------------------------
        // Search (Advanced Search Endpoints)
        // ----------------------------------------
        Route::prefix('search')->name('search.')->group(function () {
            Route::get('/properties', [FrontendSearchController::class, 'properties'])->name('properties');
            Route::get('/compounds', [FrontendSearchController::class, 'compounds'])->name('compounds');
            Route::get('/autocomplete', [FrontendSearchController::class, 'autocomplete'])->name('autocomplete');
            Route::get('/facets', [FrontendSearchController::class, 'facets'])->name('facets');
            Route::get('/popular', [FrontendSearchController::class, 'popular'])->name('popular');
            Route::get('/nearby', [FrontendSearchController::class, 'nearby'])->name('nearby');
        });
        
        // ----------------------------------------
        // Property Inquiries (Public Submission)
        // ----------------------------------------
        Route::prefix('inquiries')->name('inquiries.')->group(function () {
            Route::post('/property/{property}', [FrontendPropertyInquiryController::class, 'storeForProperty'])->name('property');
            Route::post('/compound/{compound}', [FrontendPropertyInquiryController::class, 'storeForCompound'])->name('compound');
            Route::post('/general', [FrontendPropertyInquiryController::class, 'storeGeneral'])->name('general');
        });
    });

// ========================================
// TIER 2: AUTHENTICATED USER ROUTES
// ========================================
Route::prefix('realestate')
    ->name('realestate.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        
        // ----------------------------------------
        // Saved/Favorite Properties
        // ----------------------------------------
        Route::prefix('saved-properties')->name('saved.')->group(function () {
            Route::get('/', [SavedPropertyController::class, 'index'])->name('index');
            Route::post('/{property}', [SavedPropertyController::class, 'store'])->name('store');
            Route::delete('/{property}', [SavedPropertyController::class, 'destroy'])->name('destroy');
            Route::post('/{property}/toggle', [SavedPropertyController::class, 'toggle'])->name('toggle');
            Route::get('/{property}/check', [SavedPropertyController::class, 'check'])->name('check');
            Route::post('/check-multiple', [SavedPropertyController::class, 'checkMultiple'])->name('check-multiple');
        });
    });

// ========================================
// TIER 3: ADMIN ROUTES (Auth + Package + Feature)
// ========================================
Route::prefix('admin/realestate')
    ->name('admin.realestate.')
    ->middleware(['auth:sanctum', 'package.active', 'feature:realestate'])
    ->group(function () {
        
        // ----------------------------------------
        // Property Management
        // ----------------------------------------
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::get('/', [AdminPropertyController::class, 'index'])->name('index');
            Route::post('/', [AdminPropertyController::class, 'store'])->name('store');
            Route::get('/statistics', [AdminPropertyController::class, 'statistics'])->name('statistics');
            Route::get('/{property}', [AdminPropertyController::class, 'show'])->name('show');
            Route::put('/{property}', [AdminPropertyController::class, 'update'])->name('update');
            Route::delete('/{property}', [AdminPropertyController::class, 'destroy'])->name('destroy');
            Route::post('/bulk', [AdminPropertyController::class, 'bulkAction'])->name('bulk');
            Route::patch('/{property}/feature', [AdminPropertyController::class, 'toggleFeatured'])->name('feature');
            Route::patch('/{property}/status', [AdminPropertyController::class, 'updateStatus'])->name('status');
            
            // Property Images
            Route::post('/{property}/images', [AdminPropertyController::class, 'uploadImages'])->name('images.upload');
            Route::delete('/{property}/images/{image}', [AdminPropertyController::class, 'deleteImage'])->name('images.delete');
            Route::put('/{property}/images/reorder', [AdminPropertyController::class, 'reorderImages'])->name('images.reorder');
            Route::patch('/{property}/images/{image}/primary', [AdminPropertyController::class, 'setPrimaryImage'])->name('images.primary');
        });
        
        // ----------------------------------------
        // Compound Management
        // ----------------------------------------
        Route::prefix('compounds')->name('compounds.')->group(function () {
            Route::get('/', [AdminCompoundController::class, 'index'])->name('index');
            Route::post('/', [AdminCompoundController::class, 'store'])->name('store');
            Route::get('/statistics', [AdminCompoundController::class, 'statistics'])->name('statistics');
            Route::get('/{compound}', [AdminCompoundController::class, 'show'])->name('show');
            Route::put('/{compound}', [AdminCompoundController::class, 'update'])->name('update');
            Route::delete('/{compound}', [AdminCompoundController::class, 'destroy'])->name('destroy');
            Route::post('/bulk', [AdminCompoundController::class, 'bulkAction'])->name('bulk');
            Route::patch('/{compound}/feature', [AdminCompoundController::class, 'toggleFeatured'])->name('feature');
            Route::patch('/{compound}/status', [AdminCompoundController::class, 'updateStatus'])->name('status');
            Route::patch('/{compound}/prices', [AdminCompoundController::class, 'updatePrices'])->name('prices');
            
            // Compound Images
            Route::post('/{compound}/images', [AdminCompoundController::class, 'uploadImages'])->name('images.upload');
            Route::delete('/{compound}/images/{image}', [AdminCompoundController::class, 'deleteImage'])->name('images.delete');
            Route::put('/{compound}/images/reorder', [AdminCompoundController::class, 'reorderImages'])->name('images.reorder');
        });
        
        // ----------------------------------------
        // Area Management
        // ----------------------------------------
        Route::prefix('areas')->name('areas.')->group(function () {
            Route::get('/', [AdminAreaController::class, 'index'])->name('index');
            Route::post('/', [AdminAreaController::class, 'store'])->name('store');
            Route::get('/tree', [AdminAreaController::class, 'tree'])->name('tree');
            Route::get('/statistics', [AdminAreaController::class, 'statistics'])->name('statistics');
            Route::get('/{area}', [AdminAreaController::class, 'show'])->name('show');
            Route::put('/{area}', [AdminAreaController::class, 'update'])->name('update');
            Route::delete('/{area}', [AdminAreaController::class, 'destroy'])->name('destroy');
            Route::get('/{area}/children', [AdminAreaController::class, 'children'])->name('children');
            Route::put('/reorder', [AdminAreaController::class, 'reorder'])->name('reorder');
        });
        
        // ----------------------------------------
        // Developer Management
        // ----------------------------------------
        Route::prefix('developers')->name('developers.')->group(function () {
            Route::get('/', [AdminDeveloperController::class, 'index'])->name('index');
            Route::post('/', [AdminDeveloperController::class, 'store'])->name('store');
            Route::get('/{developer}', [AdminDeveloperController::class, 'show'])->name('show');
            Route::put('/{developer}', [AdminDeveloperController::class, 'update'])->name('update');
            Route::delete('/{developer}', [AdminDeveloperController::class, 'destroy'])->name('destroy');
        });
        
        // ----------------------------------------
        // Property Type Management
        // ----------------------------------------
        Route::prefix('property-types')->name('property-types.')->group(function () {
            Route::get('/', [AdminPropertyTypeController::class, 'index'])->name('index');
            Route::post('/', [AdminPropertyTypeController::class, 'store'])->name('store');
            Route::get('/{propertyType}', [AdminPropertyTypeController::class, 'show'])->name('show');
            Route::put('/{propertyType}', [AdminPropertyTypeController::class, 'update'])->name('update');
            Route::delete('/{propertyType}', [AdminPropertyTypeController::class, 'destroy'])->name('destroy');
            Route::put('/reorder', [AdminPropertyTypeController::class, 'reorder'])->name('reorder');
        });
        
        // ----------------------------------------
        // Amenity Management
        // ----------------------------------------
        Route::prefix('amenities')->name('amenities.')->group(function () {
            Route::get('/', [AdminAmenityController::class, 'index'])->name('index');
            Route::post('/', [AdminAmenityController::class, 'store'])->name('store');
            Route::get('/for-properties', [AdminAmenityController::class, 'forProperties'])->name('for-properties');
            Route::get('/for-compounds', [AdminAmenityController::class, 'forCompounds'])->name('for-compounds');
            Route::get('/{amenity}', [AdminAmenityController::class, 'show'])->name('show');
            Route::put('/{amenity}', [AdminAmenityController::class, 'update'])->name('update');
            Route::delete('/{amenity}', [AdminAmenityController::class, 'destroy'])->name('destroy');
            Route::put('/reorder', [AdminAmenityController::class, 'reorder'])->name('reorder');
        });
        
        // ----------------------------------------
        // Property Inquiry Management (CRM/Leads)
        // ----------------------------------------
        Route::prefix('inquiries')->name('inquiries.')->group(function () {
            Route::get('/', [AdminPropertyInquiryController::class, 'index'])->name('index');
            Route::get('/statistics', [AdminPropertyInquiryController::class, 'statistics'])->name('statistics');
            Route::get('/export', [AdminPropertyInquiryController::class, 'export'])->name('export');
            Route::get('/{inquiry}', [AdminPropertyInquiryController::class, 'show'])->name('show');
            Route::put('/{inquiry}', [AdminPropertyInquiryController::class, 'update'])->name('update');
            Route::delete('/{inquiry}', [AdminPropertyInquiryController::class, 'destroy'])->name('destroy');
            Route::patch('/{inquiry}/status', [AdminPropertyInquiryController::class, 'updateStatus'])->name('status');
            Route::post('/{inquiry}/assign', [AdminPropertyInquiryController::class, 'assign'])->name('assign');
            Route::post('/{inquiry}/notes', [AdminPropertyInquiryController::class, 'addNote'])->name('notes');
            Route::post('/bulk', [AdminPropertyInquiryController::class, 'bulkAction'])->name('bulk');
        });
    });
