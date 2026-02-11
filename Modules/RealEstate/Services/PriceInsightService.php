<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\Property;

/**
 * Price Insight Service
 * 
 * Calculates market insights and price comparisons for properties
 * using database aggregation (no ML models).
 */
class PriceInsightService
{
    /**
     * Get market insight for a property.
     * 
     * Finds comparable properties and calculates price position.
     */
    public function getPropertyInsight(Property $property): array
    {
        if (!$property->compound || !$property->compound->area) {
            Log::warning('Price insight: property missing area relationship', [
                'property_id' => $property->id,
            ]);
            return [
                'error' => 'Property location data incomplete',
                'available' => false,
            ];
        }

        Log::info('Price insight: calculating for property', [
            'property_id' => $property->id,
            'area_id' => $property->compound->area_id,
            'bedrooms' => $property->bedrooms,
            'price' => $property->price,
        ]);

        // Find comparable properties (same area, similar bedrooms)
        $comparables = $this->findComparables($property);

        if ($comparables->isEmpty()) {
            Log::info('Price insight: no comparable properties found', [
                'property_id' => $property->id,
                'area_id' => $property->compound->area_id,
            ]);
            return [
                'error' => 'Not enough comparable properties in this area',
                'available' => false,
                'comparable_count' => 0,
            ];
        }

        // Calculate statistics
        $stats = $this->calculateStats($comparables, $property);

        Log::info('Price insight: calculation complete', [
            'property_id' => $property->id,
            'comparable_count' => $comparables->count(),
            'average_price' => $stats['average_price'],
            'position' => $stats['position'],
        ]);

        return $stats;
    }

    /**
     * Find comparable properties.
     * 
     * Criteria:
     * - Same area (compound.area_id)
     * - Published and available
     * - Similar price range (±25%)
     * - Same or ±1 bedroom
     * - Exclude the property itself
     */
    private function findComparables(Property $property): \Illuminate\Database\Eloquent\Collection
    {
        $priceVariance = $property->price * 0.25; // 25% variance
        $minPrice = $property->price - $priceVariance;
        $maxPrice = $property->price + $priceVariance;

        // Bedroom range: same or ±1
        $bedroomMin = max(0, ($property->bedrooms ?? 0) - 1);
        $bedroomMax = ($property->bedrooms ?? 0) + 1;

        return Property::query()
            ->join('re_compounds', 're_properties.compound_id', '=', 're_compounds.id')
            ->where('re_compounds.area_id', $property->compound->area_id)
            ->where('re_properties.id', '!=', $property->id)
            ->where('re_properties.is_published', true)
            ->where('re_properties.is_available', true)
            ->where('re_properties.listing_type', $property->listing_type) // Same listing type (sale/rent)
            ->whereBetween('re_properties.price', [$minPrice, $maxPrice])
            ->whereBetween('re_properties.bedrooms', [$bedroomMin, $bedroomMax])
            ->select('re_properties.*')
            ->with('compound.area')
            ->limit(100) // Reasonable upper limit
            ->get();
    }

    /**
     * Calculate market statistics from comparable properties.
     */
    private function calculateStats(\Illuminate\Database\Eloquent\Collection $comparables, Property $property): array
    {
        $prices = $comparables->pluck('price')->toArray();
        $averagePrice = array_sum($prices) / count($prices);
        $minPrice = min($prices);
        $maxPrice = max($prices);

        // Price per sqm calculations
        $propertyPricePerSqm = $property->area ? round($property->price / $property->area, 2) : null;
        
        $comparablesPricePerSqm = [];
        foreach ($comparables as $comp) {
            if ($comp->area) {
                $comparablesPricePerSqm[] = $comp->price / $comp->area;
            }
        }
        $areaPricePerSqm = !empty($comparablesPricePerSqm) ? 
            round(array_sum($comparablesPricePerSqm) / count($comparablesPricePerSqm), 2) : null;

        // Position analysis
        $priceDifference = $property->price - $averagePrice;
        $priceDifferencePercent = round(($priceDifference / $averagePrice) * 100, 2);
        
        if ($priceDifferencePercent < -5) {
            $position = 'below_average';
        } elseif ($priceDifferencePercent > 5) {
            $position = 'above_average';
        } else {
            $position = 'at_average';
        }

        // Market assessment
        $assessment = $this->assessPricing($position, $priceDifferencePercent);

        return [
            'available' => true,
            'property' => [
                'id' => $property->id,
                'title' => $property->title,
                'price' => (float) $property->price,
                'currency' => $property->currency,
                'bedrooms' => $property->bedrooms,
                'area_sqm' => $property->area,
                'listing_type' => $property->listing_type,
            ],
            'market_insight' => [
                'area_name' => $property->compound->area->name ?? 'Unknown',
                'comparable_properties_count' => $comparables->count(),
                'market_statistics' => [
                    'average_price' => round($averagePrice, 2),
                    'median_price' => round($this->calculateMedian($prices), 2),
                    'price_range' => [
                        'min' => (float) $minPrice,
                        'max' => (float) $maxPrice,
                        'difference' => (float) ($maxPrice - $minPrice),
                    ],
                ],
                'price_per_sqm' => [
                    'this_property' => $propertyPricePerSqm,
                    'area_average' => $areaPricePerSqm,
                ],
                'position_analysis' => [
                    'position' => $position,
                    'absolute_difference' => round($priceDifference, 2),
                    'percentage_difference' => $priceDifferencePercent,
                    'assessment' => $assessment,
                ],
                'recommendation' => [
                    'fair_price_range' => [
                        'min' => round($averagePrice * 0.95, 2), // 5% below average
                        'max' => round($averagePrice * 1.05, 2), // 5% above average
                    ],
                    'suggested_price' => round($averagePrice, 2),
                ],
            ],
        ];
    }

    /**
     * Calculate median from array of prices.
     */
    private function calculateMedian(array $prices): float
    {
        sort($prices);
        $count = count($prices);
        $mid = intval($count / 2);

        if ($count % 2 == 0) {
            return ($prices[$mid - 1] + $prices[$mid]) / 2;
        }
        return (float) $prices[$mid];
    }

    /**
     * Provide human-readable assessment of pricing.
     */
    private function assessPricing(string $position, float $percentDiff): string
    {
        return match ($position) {
            'below_average' => match (true) {
                $percentDiff < -15 => 'Well below market - potential bargain or needs investigation',
                $percentDiff < -10 => 'Below market average - good value',
                default => 'Slightly below average - competitive pricing',
            },
            'above_average' => match (true) {
                $percentDiff > 15 => 'Well above market - premium property or location',
                $percentDiff > 10 => 'Above market average - premium pricing',
                default => 'Slightly above average - asking premium',
            },
            default => 'At market average - fairly priced',
        };
    }
}
