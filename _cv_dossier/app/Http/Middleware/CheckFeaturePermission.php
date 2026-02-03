<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Feature Permission Middleware
 *
 * Checks if the tenant's subscription plan allows access to a specific feature.
 * Returns 403 with upgrade message if the feature is not allowed.
 *
 * Usage: Route::middleware('feature:blog')
 * Usage: Route::middleware('feature:eCommerce,inventory')
 */
final class CheckFeaturePermission
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param string ...$features Required features (comma-separated or multiple params)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        // If no features specified, allow access
        if (empty($features)) {
            return $next($request);
        }

        // Get current tenant
        $tenant = $this->getCurrentTenant($request);

        if (!$tenant) {
            // No tenant context - let EnsureTenantContext handle this
            return $next($request);
        }

        // Get the payment log and package
        $paymentLog = $tenant->paymentLog;

        if (!$paymentLog) {
            return $this->noSubscriptionResponse();
        }

        // Load package with features
        $package = $paymentLog->package;

        if (!$package) {
            return $this->noPackageResponse();
        }

        // Get allowed features from the package
        $allowedFeatures = $this->getAllowedFeatures($package);

        // Check each required feature
        $missingFeatures = [];
        foreach ($features as $feature) {
            // Handle comma-separated features
            $featureList = array_map('trim', explode(',', $feature));

            foreach ($featureList as $singleFeature) {
                if (!$this->hasFeature($allowedFeatures, $singleFeature, $package)) {
                    $missingFeatures[] = $singleFeature;
                }
            }
        }

        if (!empty($missingFeatures)) {
            return $this->featureNotAllowedResponse($missingFeatures, $package);
        }

        return $next($request);
    }

    /**
     * Get current tenant from request or tenancy.
     *
     * @param Request $request
     * @return Tenant|null
     */
    private function getCurrentTenant(Request $request): ?Tenant
    {
        $tenant = $request->attributes->get('tenant');

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        if ($this->tenancy->initialized && $this->tenancy->tenant instanceof Tenant) {
            return $this->tenancy->tenant;
        }

        return null;
    }

    /**
     * Get allowed features from package.
     *
     * @param mixed $package
     * @return array<string>
     */
    private function getAllowedFeatures($package): array
    {
        $features = [];

        // Get from plan_features relationship
        if ($package->relationLoaded('planFeatures')) {
            $features = $package->planFeatures->pluck('feature_name')->toArray();
        } else {
            $features = $package->planFeatures()->where('status', true)->pluck('feature_name')->toArray();
        }

        return $features;
    }

    /**
     * Check if package has the required feature.
     *
     * @param array<string> $allowedFeatures
     * @param string $feature
     * @param mixed $package
     * @return bool
     */
    private function hasFeature(array $allowedFeatures, string $feature, $package): bool
    {
        // Direct feature name match
        if (in_array($feature, $allowedFeatures, true)) {
            return true;
        }

        // Case-insensitive match
        $lowerFeature = strtolower($feature);
        foreach ($allowedFeatures as $allowed) {
            if (strtolower($allowed) === $lowerFeature) {
                return true;
            }
        }

        // Check numeric permission fields on package
        $permissionFields = [
            'page' => 'page_permission_feature',
            'blog' => 'blog_permission_feature',
            'product' => 'product_permission_feature',
            'portfolio' => 'portfolio_permission_feature',
            'storage' => 'storage_permission_feature',
            'appointment' => 'appointment_permission_feature',
        ];

        if (isset($permissionFields[$lowerFeature])) {
            $field = $permissionFields[$lowerFeature];
            return ($package->{$field} ?? 0) > 0;
        }

        return false;
    }

    /**
     * Return no subscription response.
     *
     * @return Response
     */
    private function noSubscriptionResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'No active subscription found.',
            'error_code' => 'NO_SUBSCRIPTION',
        ], 403);
    }

    /**
     * Return no package response.
     *
     * @return Response
     */
    private function noPackageResponse(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Subscription package not found.',
            'error_code' => 'PACKAGE_NOT_FOUND',
        ], 403);
    }

    /**
     * Return feature not allowed response.
     *
     * @param array<string> $missingFeatures
     * @param mixed $package
     * @return Response
     */
    private function featureNotAllowedResponse(array $missingFeatures, $package): Response
    {
        $featureList = implode(', ', $missingFeatures);
        $singleFeature = count($missingFeatures) === 1;

        return response()->json([
            'success' => false,
            'message' => $singleFeature
                ? "The '{$missingFeatures[0]}' feature is not included in your current plan. Please upgrade to access this feature."
                : "The following features are not included in your current plan: {$featureList}. Please upgrade to access these features.",
            'error_code' => 'FEATURE_NOT_ALLOWED',
            'details' => [
                'missing_features' => $missingFeatures,
                'current_plan' => $package->title ?? 'Unknown',
                'current_plan_id' => $package->id ?? null,
            ],
            'action_required' => 'upgrade_plan',
        ], 403);
    }
}

