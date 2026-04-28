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
use Modules\RealEstate\Http\Controllers\Admin\MediaController as AdminMediaController;
use Modules\RealEstate\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use Modules\RealEstate\Http\Controllers\Admin\ViewingController as AdminViewingController;

// Agent Controllers
use Modules\RealEstate\Http\Controllers\Agent\AgentDashboardController;
use Modules\RealEstate\Http\Controllers\Agent\InquiryTimelineController;
use Modules\RealEstate\Http\Controllers\Agent\ReminderController;
use Modules\RealEstate\Http\Controllers\Agent\TemplateController as AgentTemplateController;
use Modules\RealEstate\Http\Controllers\Agent\ViewingController as AgentViewingController;

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
use Modules\RealEstate\Http\Controllers\Frontend\GalleryController;
use Modules\RealEstate\Http\Controllers\Frontend\PropertyComparisonController;
use Modules\RealEstate\Http\Controllers\Frontend\MortgageCalculatorController;
use Modules\RealEstate\Http\Controllers\Frontend\PriceInsightController;
use Modules\RealEstate\Http\Controllers\Frontend\SavedSearchController;
use Modules\RealEstate\Http\Controllers\Frontend\ViewingController as FrontendViewingController;

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

/*
|--------------------------------------------------------------------------
| Tenant Context Routes (With Database Switching)
|--------------------------------------------------------------------------
| Routes that operate within a tenant's database context.
|
| Middleware stack:
| - tenancy.token - Resolves and initializes tenant context
| - tenant.context - Ensures valid tenant context exists
|
| For admin routes, add:
| - auth.tenant_admin - Requires admin authentication
| - package.active - Checks subscription is not expired
| - feature:realestate - Checks if real estate feature is allowed by plan
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    // ========================================
    // TIER 1: PUBLIC ROUTES (Frontend - No Auth Required)
    // ========================================
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('realestate')
        ->name('realestate.')
        ->group(function () {
        
        // ----------------------------------------
        // Properties
        // ----------------------------------------
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::get('/', [FrontendPropertyController::class, 'index'])->name('index');
            Route::get('/featured', [FrontendPropertyController::class, 'featured'])->name('featured');
            // Specific routes MUST come before generic wildcard routes
            Route::get('/{property}/similar', [FrontendPropertyController::class, 'similar'])
                ->name('similar');
            Route::get('/{property}', [FrontendPropertyController::class, 'show'])
                ->name('show'); // No constraint - handles in controller
        });
        
        // ----------------------------------------
        // Compounds
        // ----------------------------------------
        Route::prefix('compounds')->name('compounds.')->group(function () {
            Route::get('/', [FrontendCompoundController::class, 'index'])->name('index');
            Route::get('/featured', [FrontendCompoundController::class, 'featured'])->name('featured');
            // Specific routes MUST come before generic wildcard routes
            Route::get('/{compound}/properties', [FrontendCompoundController::class, 'properties'])
                ->name('properties');
            Route::get('/{compound}', [FrontendCompoundController::class, 'show'])
                ->name('show'); // No constraint - handles in controller
        });
        
        // ----------------------------------------
        // Areas/Locations (Hierarchical)
        // ----------------------------------------
        Route::prefix('areas')->name('areas.')->group(function () {
            Route::get('/', [FrontendAreaController::class, 'index'])->name('index');
            Route::get('/tree', [FrontendAreaController::class, 'tree'])->name('tree');
            Route::get('/cities', [FrontendAreaController::class, 'cities'])->name('cities');
            Route::get('/featured', [FrontendAreaController::class, 'featured'])->name('featured');
            Route::get('/by-slug/{slug}', [FrontendAreaController::class, 'showBySlug'])->name('show-by-slug');
            Route::get('/{area}', [FrontendAreaController::class, 'show'])
                ->name('show')
                ->where('area', '[0-9]+-.*');
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
            // Global unified search - MUST be before specific search routes
            Route::get('/', [FrontendSearchController::class, 'global'])->name('global');
            
            Route::get('/properties', [FrontendSearchController::class, 'properties'])->name('properties');
            Route::get('/compounds', [FrontendSearchController::class, 'compounds'])->name('compounds');
            Route::get('/autocomplete', [FrontendSearchController::class, 'autocomplete'])->name('autocomplete');
            Route::get('/facets', [FrontendSearchController::class, 'facets'])->name('facets');
            Route::get('/popular', [FrontendSearchController::class, 'popular'])->name('popular');
            Route::get('/nearby', [FrontendSearchController::class, 'nearby'])->name('nearby');
        });
        
        // ----------------------------------------
        // Map (Geo-spatial Map Endpoints)
        // ----------------------------------------
        Route::prefix('map')->name('map.')->group(function () {
            Route::get('/properties', [FrontendSearchController::class, 'mapProperties'])->name('properties');
            Route::get('/clusters', [FrontendSearchController::class, 'clusters'])->name('clusters');
        });
        
        // ----------------------------------------
        // Property Inquiries (Public Submission)
        // ----------------------------------------
        Route::prefix('inquiries')->name('inquiries.')->group(function () {
            Route::post('/property/{property}', [FrontendPropertyInquiryController::class, 'storeForProperty'])->name('property');
            Route::post('/compound/{compound}', [FrontendPropertyInquiryController::class, 'storeForCompound'])->name('compound');
            Route::post('/general', [FrontendPropertyInquiryController::class, 'store'])->name('general');
        });
        
        // ----------------------------------------
        // Gallery (Public Image Galleries)
        // ----------------------------------------
        Route::prefix('gallery')->name('gallery.')->group(function () {
            Route::get('/properties/{property}', [GalleryController::class, 'propertyGallery'])->name('property');
            Route::get('/compounds/{compound}', [GalleryController::class, 'compoundGallery'])->name('compound');
        });

        // ----------------------------------------
        // Mortgage Calculator (Public / Stateless)
        // ----------------------------------------
        Route::prefix('mortgage')->name('mortgage.')->group(function () {
            Route::post('/monthly-payment', [MortgageCalculatorController::class, 'monthlyPayment'])->name('monthly-payment');
            Route::post('/loan-amount', [MortgageCalculatorController::class, 'loanAmount'])->name('loan-amount');
            Route::post('/amortization-schedule', [MortgageCalculatorController::class, 'amortizationSchedule'])->name('amortization-schedule');
            Route::post('/affordability', [MortgageCalculatorController::class, 'affordability'])->name('affordability');
            Route::post('/down-payment', [MortgageCalculatorController::class, 'downPayment'])->name('down-payment');
            Route::post('/for-property/{property}', [MortgageCalculatorController::class, 'forProperty'])->name('for-property');
        });

        // ----------------------------------------
        // Price Insight (Market Analysis)
        // ----------------------------------------
        Route::prefix('price-insight')->name('price-insight.')->group(function () {
            Route::get('/{property}', [PriceInsightController::class, 'show'])->name('show');
            Route::post('/bulk', [PriceInsightController::class, 'bulk'])->name('bulk');
        });
    });

    // ========================================
    // TIER 2: AUTHENTICATED USER ROUTES
    // ========================================
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context'])
        ->prefix('realestate')
        ->name('realestate.')
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

        // ----------------------------------------
        // My Inquiries (Inquiry Tracking)
        // ----------------------------------------
        Route::prefix('my-inquiries')->name('my-inquiries.')->group(function () {
            Route::get('/', [FrontendPropertyInquiryController::class, 'myInquiries'])->name('index');
            Route::get('/{id}', [FrontendPropertyInquiryController::class, 'showMyInquiry'])->name('show');
        });

        // ----------------------------------------
        // Property Comparison (Session-Based)
        // ----------------------------------------
        Route::prefix('comparison')->name('comparison.')->group(function () {
            Route::get('/', [PropertyComparisonController::class, 'index'])->name('index');
            Route::post('/{property}', [PropertyComparisonController::class, 'add'])->name('add');
            Route::delete('/{property}', [PropertyComparisonController::class, 'remove'])->name('remove');
            Route::delete('/', [PropertyComparisonController::class, 'clear'])->name('clear');
        });

        // ----------------------------------------
        // Saved Searches & Alerts
        // ----------------------------------------
        Route::prefix('saved-searches')->name('saved-searches.')->group(function () {
            Route::get('/', [SavedSearchController::class, 'index'])->name('index');
            Route::post('/', [SavedSearchController::class, 'store'])->name('store');
            Route::get('/{id}', [SavedSearchController::class, 'show'])->name('show');
            Route::put('/{id}', [SavedSearchController::class, 'update'])->name('update');
            Route::delete('/{id}', [SavedSearchController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/toggle-alerts', [SavedSearchController::class, 'toggleAlerts'])->name('toggle-alerts');
            Route::get('/{id}/matches', [SavedSearchController::class, 'matches'])->name('matches');
        });

        // ----------------------------------------
        // F2.5: Viewing Scheduler (user books a viewing)
        // ----------------------------------------
        Route::prefix('viewings')->name('viewings.')->group(function () {
            Route::post('/', [FrontendViewingController::class, 'book'])->name('book');
        });
    });

    // ========================================
    // TIER 3: ADMIN ROUTES (Auth + Package + Feature)
    // ========================================
    Route::middleware(['tenancy.token', 'tenant.context', 'auth.tenant_admin', 'package.active', 'feature:realestate'])
        ->prefix('admin/realestate')
        ->name('admin.realestate.')
        ->group(function () {
        
        // ----------------------------------------
        // Property Management
        // ----------------------------------------
        Route::prefix('properties')->name('properties.')->group(function () {
            Route::get('/', [AdminPropertyController::class, 'index'])->name('index');
            Route::post('/', [AdminPropertyController::class, 'store'])->name('store');
            Route::get('/statistics', [AdminPropertyController::class, 'statistics'])->name('statistics');
            Route::post('/bulk', [AdminPropertyController::class, 'bulk'])->name('bulk');
            Route::get('/{id}', [AdminPropertyController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminPropertyController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminPropertyController::class, 'destroy'])->name('destroy');
            
            // Property Images
            Route::post('/{property}/images', [AdminMediaController::class, 'uploadPropertyImages'])->name('images.upload');
            Route::delete('/{property}/images/{image}', [AdminMediaController::class, 'deletePropertyImage'])->name('images.delete');
            Route::put('/{property}/images/reorder', [AdminMediaController::class, 'reorderPropertyImages'])->name('images.reorder');
            Route::patch('/{property}/images/{image}/primary', [AdminMediaController::class, 'setPrimaryImage'])->name('images.primary');
        });
        
        // ----------------------------------------
        // Compound Management
        // ----------------------------------------
        Route::prefix('compounds')->name('compounds.')->group(function () {
            Route::get('/', [AdminCompoundController::class, 'index'])->name('index');
            Route::post('/', [AdminCompoundController::class, 'store'])->name('store');
            Route::get('/statistics', [AdminCompoundController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [AdminCompoundController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminCompoundController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminCompoundController::class, 'destroy'])->name('destroy');
            Route::post('/bulk', [AdminCompoundController::class, 'bulk'])->name('bulk');
            Route::patch('/{id}/prices', [AdminCompoundController::class, 'updatePrices'])->name('prices');
            
            // Compound Images
            Route::post('/{id}/images', [AdminMediaController::class, 'uploadCompoundImages'])->name('images.upload');
            Route::delete('/{id}/images/{imageId}', [AdminMediaController::class, 'deleteCompoundImage'])->name('images.delete');
            Route::put('/{id}/images/reorder', [AdminMediaController::class, 'reorderPropertyImages'])->name('images.reorder');
        });
        
        // ----------------------------------------
        // Area Management
        // ----------------------------------------
        Route::prefix('areas')->name('areas.')->group(function () {
            Route::get('/', [AdminAreaController::class, 'index'])->name('index');
            Route::post('/', [AdminAreaController::class, 'store'])->name('store');
            Route::get('/tree', [AdminAreaController::class, 'tree'])->name('tree');
            Route::get('/statistics', [AdminAreaController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [AdminAreaController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminAreaController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminAreaController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/children', [AdminAreaController::class, 'children'])->name('children');
            Route::put('/reorder', [AdminAreaController::class, 'reorder'])->name('reorder');
        });
        
        // ----------------------------------------
        // Developer Management
        // ----------------------------------------
        Route::prefix('developers')->name('developers.')->group(function () {
            Route::get('/', [AdminDeveloperController::class, 'index'])->name('index');
            Route::post('/', [AdminDeveloperController::class, 'store'])->name('store');
            Route::get('/{id}', [AdminDeveloperController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminDeveloperController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminDeveloperController::class, 'destroy'])->name('destroy');
        });
        
        // ----------------------------------------
        // Property Type Management
        // ----------------------------------------
        Route::prefix('property-types')->name('property-types.')->group(function () {
            Route::get('/', [AdminPropertyTypeController::class, 'index'])->name('index');
            Route::post('/', [AdminPropertyTypeController::class, 'store'])->name('store');
            Route::get('/{id}', [AdminPropertyTypeController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminPropertyTypeController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminPropertyTypeController::class, 'destroy'])->name('destroy');
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
            Route::get('/{id}', [AdminAmenityController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminAmenityController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminAmenityController::class, 'destroy'])->name('destroy');
            Route::put('/reorder', [AdminAmenityController::class, 'reorder'])->name('reorder');
        });
        
        // ----------------------------------------
        // F2.4: Canned Response Templates
        // ----------------------------------------
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [AdminTemplateController::class, 'index'])->name('index');
            Route::post('/', [AdminTemplateController::class, 'store'])->name('store');
            Route::get('/{id}', [AdminTemplateController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminTemplateController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminTemplateController::class, 'destroy'])->name('destroy');
        });

        // ----------------------------------------
        // F2.5: Viewing Scheduler (admin)
        // ----------------------------------------
        Route::prefix('viewings')->name('viewings.')->group(function () {
            Route::get('/', [AdminViewingController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminViewingController::class, 'show'])->name('show');
            Route::patch('/{id}', [AdminViewingController::class, 'update'])->name('update');
        });

        // ----------------------------------------
        // Property Inquiry Management (CRM/Leads)
        // ----------------------------------------
        Route::prefix('inquiries')->name('inquiries.')->group(function () {
            Route::get('/', [AdminPropertyInquiryController::class, 'index'])->name('index');
            Route::get('/statistics', [AdminPropertyInquiryController::class, 'statistics'])->name('statistics');
            Route::get('/export', [AdminPropertyInquiryController::class, 'export'])->name('export');
            Route::get('/{id}', [AdminPropertyInquiryController::class, 'show'])->name('show');
            Route::put('/{id}', [AdminPropertyInquiryController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminPropertyInquiryController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/assign', [AdminPropertyInquiryController::class, 'assignAgent'])->name('assign');
            Route::post('/bulk/status', [AdminPropertyInquiryController::class, 'bulkUpdateStatus'])->name('bulk-status');
            
            // Status transitions
            Route::post('/{id}/contacted', [AdminPropertyInquiryController::class, 'markContacted'])->name('contacted');
            Route::post('/{id}/qualified', [AdminPropertyInquiryController::class, 'markQualified'])->name('qualified');
            Route::post('/{id}/converted', [AdminPropertyInquiryController::class, 'markConverted'])->name('converted');
        });
    });

    // ========================================
    // TIER 4: AGENT ROUTES (Authenticated Agents)
    // ========================================
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context'])
        ->prefix('agent/realestate')
        ->name('agent.realestate.')
        ->group(function () {
        
        // ----------------------------------------
        // Agent Dashboard
        // ----------------------------------------
        Route::get('/dashboard', [AgentDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [AgentDashboardController::class, 'statistics'])->name('statistics');
        
        // ----------------------------------------
        // Agent's Properties
        // ----------------------------------------
        Route::get('/properties', [AgentDashboardController::class, 'properties'])->name('properties');
        
        // ----------------------------------------
        // Agent's Inquiries
        // ----------------------------------------
        Route::prefix('inquiries')->name('inquiries.')->group(function () {
            Route::get('/', [AgentDashboardController::class, 'inquiries'])->name('index');
            Route::put('/{id}', [AgentDashboardController::class, 'updateInquiry'])->name('update');
            Route::post('/{id}/contact', [AgentDashboardController::class, 'markContacted'])->name('contact');

            // F2.1 - Create reminder for a specific inquiry
            Route::post('/{id}/reminders', [ReminderController::class, 'createForInquiry'])->name('reminders.create');

            // F2.2 - Timeline and notes
            Route::get('/{id}/timeline', [InquiryTimelineController::class, 'timeline'])->name('timeline');
            Route::post('/{id}/notes', [InquiryTimelineController::class, 'addNote'])->name('notes.create');
            Route::delete('/{id}/notes/{noteId}', [InquiryTimelineController::class, 'deleteNote'])->name('notes.delete');
        });

        // ----------------------------------------
        // F2.1: Follow-up Reminders
        // ----------------------------------------
        Route::prefix('reminders')->name('reminders.')->group(function () {
            Route::get('/', [ReminderController::class, 'index'])->name('index');
            Route::patch('/{id}', [ReminderController::class, 'update'])->name('update');
            Route::delete('/{id}', [ReminderController::class, 'destroy'])->name('destroy');
        });

        // ----------------------------------------
        // F2.1: SLA Summary
        // ----------------------------------------
        Route::get('/sla/summary', [ReminderController::class, 'slaSummary'])->name('sla.summary');

        // ----------------------------------------
        // F2.4: Canned Response Templates (agent)
        // ----------------------------------------
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [AgentTemplateController::class, 'index'])->name('index');
            Route::post('/{id}/preview', [AgentTemplateController::class, 'preview'])->name('preview');
        });

        // ----------------------------------------
        // F2.5: Viewing Scheduler (agent)
        // ----------------------------------------
        Route::prefix('viewings')->name('viewings.')->group(function () {
            Route::get('/today', [AgentViewingController::class, 'today'])->name('today');
            Route::get('/', [AgentViewingController::class, 'index'])->name('index');
            Route::patch('/{id}', [AgentViewingController::class, 'update'])->name('update');
            Route::post('/{id}/complete', [AgentViewingController::class, 'complete'])->name('complete');
        });
    });
});
