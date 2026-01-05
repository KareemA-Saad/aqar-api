<?php

declare(strict_types=1);

namespace Modules\Product\Services;

/**
 * Tax Service
 *
 * Handles tax calculation based on country/state tax rules.
 */
class TaxService
{
    /**
     * Calculate tax for an order.
     *
     * @param int $countryId
     * @param int|null $stateId
     * @param float $taxableAmount
     * @return array
     */
    public function calculateTax(int $countryId, ?int $stateId, float $taxableAmount): array
    {
        // Try to get state-specific tax first
        if ($stateId) {
            $stateTax = $this->getStateTax($stateId);
            if ($stateTax) {
                return [
                    'tax_percentage' => $stateTax,
                    'tax_amount' => round($taxableAmount * ($stateTax / 100), 2),
                    'tax_type' => 'state',
                ];
            }
        }

        // Fall back to country tax
        $countryTax = $this->getCountryTax($countryId);
        
        return [
            'tax_percentage' => $countryTax,
            'tax_amount' => round($taxableAmount * ($countryTax / 100), 2),
            'tax_type' => $countryTax > 0 ? 'country' : 'none',
        ];
    }

    /**
     * Get state tax percentage.
     *
     * @param int $stateId
     * @return float
     */
    protected function getStateTax(int $stateId): float
    {
        // Try to load from TaxModule if available
        try {
            $stateTaxClass = 'Modules\\TaxModule\\Entities\\StateTax';
            if (class_exists($stateTaxClass)) {
                $stateTax = $stateTaxClass::where('state_id', $stateId)->first();
                if ($stateTax) {
                    return (float) $stateTax->tax_percentage;
                }
            }
        } catch (\Exception $e) {
            // TaxModule not available or error
        }

        return 0;
    }

    /**
     * Get country tax percentage.
     *
     * @param int $countryId
     * @return float
     */
    protected function getCountryTax(int $countryId): float
    {
        // Try to load from TaxModule if available
        try {
            $countryTaxClass = 'Modules\\TaxModule\\Entities\\CountryTax';
            if (class_exists($countryTaxClass)) {
                $countryTax = $countryTaxClass::where('country_id', $countryId)->first();
                if ($countryTax) {
                    return (float) $countryTax->tax_percentage;
                }
            }
        } catch (\Exception $e) {
            // TaxModule not available or error
        }

        // Return default tax rate from settings or 0
        return (float) (get_static_option('default_tax_percentage') ?? 0);
    }

    /**
     * Get tax summary for display.
     *
     * @param int $countryId
     * @param int|null $stateId
     * @return array
     */
    public function getTaxInfo(int $countryId, ?int $stateId = null): array
    {
        $taxData = $this->calculateTax($countryId, $stateId, 100); // Calculate for 100 to get percentage

        return [
            'percentage' => $taxData['tax_percentage'],
            'type' => $taxData['tax_type'],
            'is_taxable' => $taxData['tax_percentage'] > 0,
        ];
    }

    /**
     * Check if tax is applicable for a location.
     *
     * @param int $countryId
     * @param int|null $stateId
     * @return bool
     */
    public function isTaxable(int $countryId, ?int $stateId = null): bool
    {
        $taxData = $this->calculateTax($countryId, $stateId, 100);
        return $taxData['tax_percentage'] > 0;
    }
}
