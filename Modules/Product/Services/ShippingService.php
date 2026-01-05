<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Illuminate\Support\Collection;
use Modules\ShippingModule\Entities\ShippingMethod;
use Modules\ShippingModule\Entities\Zone;
use Modules\ShippingModule\Entities\ZoneRegion;

/**
 * Shipping Service
 *
 * Handles shipping method retrieval and cost calculation based on zones.
 */
class ShippingService
{
    /**
     * Get available shipping methods for a location.
     *
     * @param int $countryId
     * @param int|null $stateId
     * @param float $cartSubtotal
     * @return array
     */
    public function getAvailableMethods(int $countryId, ?int $stateId, float $cartSubtotal): array
    {
        // Find the zone for this location
        $zone = $this->findZone($countryId, $stateId);

        if (!$zone) {
            // Return default shipping methods or empty array
            return $this->getDefaultShippingMethods($cartSubtotal);
        }

        // Get shipping methods for this zone
        $methods = ShippingMethod::where('zone_id', $zone->id)
            ->with('availableOptions')
            ->get();

        return $methods->map(function ($method) use ($cartSubtotal) {
            $option = $method->availableOptions;
            $cost = $this->calculateMethodCost($option, $cartSubtotal);

            return [
                'id' => $method->id,
                'name' => $method->name,
                'cost' => round($cost, 2),
                'estimated_days' => $option?->delivery_time ?? '3-5 business days',
                'is_default' => (bool) $method->is_default,
                'min_order_amount' => $option?->minimum_order_amount ?? 0,
                'free_shipping_threshold' => $option?->free_shipping_minimum ?? null,
            ];
        })->toArray();
    }

    /**
     * Find the applicable zone for a location.
     *
     * @param int $countryId
     * @param int|null $stateId
     * @return Zone|null
     */
    protected function findZone(int $countryId, ?int $stateId): ?Zone
    {
        // First try to find a zone with exact state match
        if ($stateId) {
            $zoneRegion = ZoneRegion::where('country_id', $countryId)
                ->where('state_id', $stateId)
                ->first();

            if ($zoneRegion) {
                return $zoneRegion->zone;
            }
        }

        // Then try to find a zone with country only (state = null means whole country)
        $zoneRegion = ZoneRegion::where('country_id', $countryId)
            ->whereNull('state_id')
            ->first();

        if ($zoneRegion) {
            return $zoneRegion->zone;
        }

        // Return the default zone if exists
        return Zone::where('is_default', true)->first();
    }

    /**
     * Get default shipping methods when no zone matches.
     *
     * @param float $cartSubtotal
     * @return array
     */
    protected function getDefaultShippingMethods(float $cartSubtotal): array
    {
        // Get methods from default zone or return standard methods
        $defaultZone = Zone::where('is_default', true)->first();

        if ($defaultZone) {
            $methods = ShippingMethod::where('zone_id', $defaultZone->id)
                ->with('availableOptions')
                ->get();

            return $methods->map(function ($method) use ($cartSubtotal) {
                $option = $method->availableOptions;
                $cost = $this->calculateMethodCost($option, $cartSubtotal);

                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'cost' => round($cost, 2),
                    'estimated_days' => $option?->delivery_time ?? '5-7 business days',
                    'is_default' => (bool) $method->is_default,
                ];
            })->toArray();
        }

        // Return a basic flat rate if nothing is configured
        return [
            [
                'id' => 0,
                'name' => 'Standard Shipping',
                'cost' => 9.99,
                'estimated_days' => '5-7 business days',
                'is_default' => true,
            ]
        ];
    }

    /**
     * Calculate the cost for a shipping method.
     *
     * @param mixed $option
     * @param float $cartSubtotal
     * @return float
     */
    protected function calculateMethodCost($option, float $cartSubtotal): float
    {
        if (!$option) {
            return 0;
        }

        // Check for free shipping threshold
        if ($option->free_shipping_minimum && $cartSubtotal >= $option->free_shipping_minimum) {
            return 0;
        }

        // Check minimum order amount
        if ($option->minimum_order_amount && $cartSubtotal < $option->minimum_order_amount) {
            // Could throw exception or add surcharge
        }

        // Calculate based on shipping method type
        return match ($option->shipping_type ?? 'flat_rate') {
            'flat_rate' => (float) ($option->flat_rate ?? 0),
            'percentage' => $cartSubtotal * ((float) ($option->percentage ?? 0) / 100),
            'weight_based' => $this->calculateWeightBasedCost($option),
            default => (float) ($option->flat_rate ?? 0),
        };
    }

    /**
     * Calculate weight-based shipping cost.
     *
     * @param mixed $option
     * @return float
     */
    protected function calculateWeightBasedCost($option): float
    {
        // This would need cart weight data
        // For now, return flat rate
        return (float) ($option->flat_rate ?? 0);
    }

    /**
     * Calculate shipping cost for a specific method.
     *
     * @param int $methodId
     * @param float $cartSubtotal
     * @return float
     */
    public function calculateShipping(int $methodId, float $cartSubtotal): float
    {
        // Handle the default fallback case
        if ($methodId === 0) {
            return 9.99;
        }

        $method = ShippingMethod::with('availableOptions')->find($methodId);

        if (!$method) {
            return 0;
        }

        return $this->calculateMethodCost($method->availableOptions, $cartSubtotal);
    }

    /**
     * Validate that a shipping method is available for a location.
     *
     * @param int $methodId
     * @param int $countryId
     * @param int|null $stateId
     * @return bool
     */
    public function isMethodAvailable(int $methodId, int $countryId, ?int $stateId): bool
    {
        $availableMethods = $this->getAvailableMethods($countryId, $stateId, 0);

        return collect($availableMethods)->contains('id', $methodId);
    }
}
