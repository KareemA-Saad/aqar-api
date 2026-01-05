<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Modules\HotelBooking\Entities\Inventory;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Entities\CancellationPolicy;

class PricingService
{
    /**
     * Default tax rate (can be overridden by hotel/zone settings).
     */
    protected float $defaultTaxRate = 0.15; // 15% VAT

    /**
     * Calculate total price for a room type within a date range.
     */
    public function calculateRoomPrice(
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        int $quantity = 1,
        array $options = []
    ): array {
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);
        $nights = $checkIn->diffInDays($checkOut);

        if ($nights < 1) {
            throw new \InvalidArgumentException('Check-out date must be after check-in date.');
        }

        // Get room type with base price fallback
        $roomType = RoomType::findOrFail($roomTypeId);

        // Get inventory prices for each night (excluding checkout date)
        $inventories = Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [
                $checkIn->format('Y-m-d'),
                $checkOut->copy()->subDay()->format('Y-m-d')
            ])
            ->where('is_available', true)
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($inv) => Carbon::parse($inv->date)->format('Y-m-d'));

        // Calculate day-wise pricing
        $dailyBreakdown = [];
        $subtotal = 0;
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $inventory = $inventories->get($dateString);
            
            // Use inventory price or fall back to room type base price
            $nightPrice = $inventory?->price ?? $roomType->base_price ?? 0;
            $nightTotal = $nightPrice * $quantity;

            $dailyBreakdown[] = [
                'date' => $dateString,
                'day_name' => $date->format('l'),
                'unit_price' => $nightPrice,
                'quantity' => $quantity,
                'total' => $nightTotal,
            ];

            $subtotal += $nightTotal;
        }

        // Calculate meal options
        $mealTotal = $this->calculateMealPrice($options['meal_plan'] ?? null, $nights, $quantity, $options['adults'] ?? 1);

        // Calculate extras
        $extrasTotal = $this->calculateExtrasPrice($options['extras'] ?? []);

        // Calculate subtotal before tax
        $subtotalBeforeTax = $subtotal + $mealTotal + $extrasTotal;

        // Calculate tax
        $taxRate = $options['tax_rate'] ?? $this->defaultTaxRate;
        $taxAmount = round($subtotalBeforeTax * $taxRate, 2);

        // Calculate total
        $total = $subtotalBeforeTax + $taxAmount;

        return [
            'room_type_id' => $roomTypeId,
            'room_type_name' => $roomType->name,
            'check_in_date' => $checkInDate,
            'check_out_date' => $checkOutDate,
            'nights' => $nights,
            'quantity' => $quantity,
            'daily_breakdown' => $dailyBreakdown,
            'room_subtotal' => round($subtotal, 2),
            'meal_plan' => $options['meal_plan'] ?? null,
            'meal_total' => round($mealTotal, 2),
            'extras' => $options['extras'] ?? [],
            'extras_total' => round($extrasTotal, 2),
            'subtotal' => round($subtotalBeforeTax, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($total, 2),
            'currency' => $options['currency'] ?? 'SAR',
        ];
    }

    /**
     * Calculate total price for multiple room types (multi-room booking).
     */
    public function calculateMultiRoomPrice(
        array $roomSelections,
        string $checkInDate,
        string $checkOutDate,
        array $options = []
    ): array {
        $roomBreakdowns = [];
        $roomsSubtotal = 0;
        $totalMeals = 0;
        $totalNights = 0;
        $totalQuantity = 0;

        foreach ($roomSelections as $selection) {
            $roomTypeId = $selection['room_type_id'];
            $quantity = $selection['quantity'] ?? 1;
            $mealPlan = $selection['meal_plan'] ?? null;
            $adults = $selection['adults'] ?? 2;

            $breakdown = $this->calculateRoomPrice(
                $roomTypeId,
                $checkInDate,
                $checkOutDate,
                $quantity,
                array_merge($options, [
                    'meal_plan' => $mealPlan,
                    'adults' => $adults,
                    'tax_rate' => 0, // We'll calculate tax on the total
                ])
            );

            $roomBreakdowns[] = $breakdown;
            $roomsSubtotal += $breakdown['room_subtotal'];
            $totalMeals += $breakdown['meal_total'];
            $totalNights = $breakdown['nights'];
            $totalQuantity += $quantity;
        }

        // Calculate extras for entire booking
        $extrasTotal = $this->calculateExtrasPrice($options['extras'] ?? []);

        // Calculate subtotal before tax
        $subtotalBeforeTax = $roomsSubtotal + $totalMeals + $extrasTotal;

        // Calculate tax on total
        $taxRate = $options['tax_rate'] ?? $this->defaultTaxRate;
        $taxAmount = round($subtotalBeforeTax * $taxRate, 2);

        // Calculate total
        $total = $subtotalBeforeTax + $taxAmount;

        return [
            'check_in_date' => $checkInDate,
            'check_out_date' => $checkOutDate,
            'nights' => $totalNights,
            'total_rooms' => $totalQuantity,
            'room_breakdowns' => $roomBreakdowns,
            'rooms_subtotal' => round($roomsSubtotal, 2),
            'meals_total' => round($totalMeals, 2),
            'extras' => $options['extras'] ?? [],
            'extras_total' => round($extrasTotal, 2),
            'subtotal' => round($subtotalBeforeTax, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => round($total, 2),
            'currency' => $options['currency'] ?? 'SAR',
        ];
    }

    /**
     * Calculate meal plan price.
     */
    public function calculateMealPrice(?string $mealPlan, int $nights, int $rooms, int $adultsPerRoom = 2): float
    {
        if (!$mealPlan) {
            return 0;
        }

        // Meal prices per person per night (can be moved to config)
        $mealPrices = [
            'breakfast' => 50,           // Room Only + Breakfast
            'half_board' => 120,         // Breakfast + Dinner
            'full_board' => 180,         // Breakfast + Lunch + Dinner
            'all_inclusive' => 250,      // All meals + drinks + snacks
        ];

        $pricePerPerson = $mealPrices[$mealPlan] ?? 0;
        $totalPersons = $rooms * $adultsPerRoom;

        return $pricePerPerson * $totalPersons * $nights;
    }

    /**
     * Calculate extras price.
     */
    public function calculateExtrasPrice(array $extras): float
    {
        $total = 0;

        // Predefined extras with prices (can be moved to config/database)
        $extraPrices = [
            'airport_transfer' => 150,
            'late_checkout' => 100,
            'early_checkin' => 100,
            'extra_bed' => 80,
            'crib' => 0,
            'parking' => 50,
            'spa_access' => 200,
            'gym_access' => 50,
        ];

        foreach ($extras as $extra) {
            $extraId = is_array($extra) ? ($extra['id'] ?? $extra['type'] ?? null) : $extra;
            $quantity = is_array($extra) ? ($extra['quantity'] ?? 1) : 1;
            
            if ($extraId && isset($extraPrices[$extraId])) {
                $total += $extraPrices[$extraId] * $quantity;
            } elseif (is_array($extra) && isset($extra['price'])) {
                $total += $extra['price'] * $quantity;
            }
        }

        return $total;
    }

    /**
     * Get the lowest price for a room type in a date range.
     */
    public function getLowestPrice(int $roomTypeId, string $startDate, string $endDate): ?float
    {
        $minPrice = Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_available', true)
            ->min('price');

        if ($minPrice === null) {
            $roomType = RoomType::find($roomTypeId);
            return $roomType?->base_price;
        }

        return (float) $minPrice;
    }

    /**
     * Get the average price for a room type in a date range.
     */
    public function getAveragePrice(int $roomTypeId, string $startDate, string $endDate): ?float
    {
        $avgPrice = Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_available', true)
            ->avg('price');

        if ($avgPrice === null) {
            $roomType = RoomType::find($roomTypeId);
            return $roomType?->base_price;
        }

        return round((float) $avgPrice, 2);
    }

    /**
     * Calculate cancellation refund amount.
     */
    public function calculateRefundAmount(
        float $totalPaid,
        string $checkInDate,
        ?int $cancellationPolicyId = null
    ): array {
        $checkIn = Carbon::parse($checkInDate);
        $now = Carbon::now();
        $hoursBeforeCheckIn = $now->diffInHours($checkIn, false);

        // Default policy: full refund if more than 24 hours before check-in
        if ($cancellationPolicyId === null) {
            $refundPercentage = $hoursBeforeCheckIn >= 24 ? 100 : 0;
            
            return [
                'total_paid' => $totalPaid,
                'hours_before_checkin' => max(0, $hoursBeforeCheckIn),
                'refund_percentage' => $refundPercentage,
                'refund_amount' => round($totalPaid * ($refundPercentage / 100), 2),
                'penalty_amount' => round($totalPaid * ((100 - $refundPercentage) / 100), 2),
                'policy_name' => 'Default Policy',
            ];
        }

        // Get policy with tiers
        $policy = CancellationPolicy::with('tiers')->findOrFail($cancellationPolicyId);
        $refundPercentage = $policy->getRefundPercentage($hoursBeforeCheckIn);

        return [
            'total_paid' => $totalPaid,
            'hours_before_checkin' => max(0, $hoursBeforeCheckIn),
            'refund_percentage' => $refundPercentage,
            'refund_amount' => round($totalPaid * ($refundPercentage / 100), 2),
            'penalty_amount' => round($totalPaid * ((100 - $refundPercentage) / 100), 2),
            'policy_name' => $policy->name,
            'policy_id' => $policy->id,
        ];
    }

    /**
     * Get price comparison for room types.
     */
    public function comparePrices(array $roomTypeIds, string $checkInDate, string $checkOutDate): Collection
    {
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);
        $nights = $checkIn->diffInDays($checkOut);

        $comparisons = collect();

        foreach ($roomTypeIds as $roomTypeId) {
            $pricing = $this->calculateRoomPrice($roomTypeId, $checkInDate, $checkOutDate, 1, [
                'tax_rate' => $this->defaultTaxRate,
            ]);

            $roomType = RoomType::with('hotel')->find($roomTypeId);

            $comparisons->push([
                'room_type_id' => $roomTypeId,
                'room_type_name' => $roomType?->name,
                'hotel_name' => $roomType?->hotel?->name,
                'nights' => $nights,
                'price_per_night' => $nights > 0 ? round($pricing['room_subtotal'] / $nights, 2) : 0,
                'total_before_tax' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'total' => $pricing['total'],
            ]);
        }

        return $comparisons->sortBy('total');
    }

    /**
     * Apply promo code/discount.
     */
    public function applyDiscount(array $pricing, string $discountType, float $discountValue): array
    {
        $subtotal = $pricing['subtotal'];
        $discountAmount = 0;

        switch ($discountType) {
            case 'percentage':
                $discountAmount = round($subtotal * ($discountValue / 100), 2);
                break;
            case 'fixed':
                $discountAmount = min($discountValue, $subtotal);
                break;
            case 'per_night':
                $discountAmount = round($discountValue * ($pricing['nights'] ?? 1), 2);
                break;
        }

        $newSubtotal = $subtotal - $discountAmount;
        $taxAmount = round($newSubtotal * $pricing['tax_rate'], 2);
        $newTotal = $newSubtotal + $taxAmount;

        return array_merge($pricing, [
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'subtotal_after_discount' => $newSubtotal,
            'tax_amount' => $taxAmount,
            'total' => round($newTotal, 2),
        ]);
    }

    /**
     * Get available meal plans with prices.
     */
    public function getAvailableMealPlans(): array
    {
        return [
            [
                'id' => 'room_only',
                'name' => 'Room Only',
                'description' => 'No meals included',
                'price_per_person_per_night' => 0,
            ],
            [
                'id' => 'breakfast',
                'name' => 'Bed & Breakfast',
                'description' => 'Breakfast included',
                'price_per_person_per_night' => 50,
            ],
            [
                'id' => 'half_board',
                'name' => 'Half Board',
                'description' => 'Breakfast and dinner included',
                'price_per_person_per_night' => 120,
            ],
            [
                'id' => 'full_board',
                'name' => 'Full Board',
                'description' => 'All meals included',
                'price_per_person_per_night' => 180,
            ],
            [
                'id' => 'all_inclusive',
                'name' => 'All Inclusive',
                'description' => 'All meals, drinks, and snacks included',
                'price_per_person_per_night' => 250,
            ],
        ];
    }

    /**
     * Get available extras with prices.
     */
    public function getAvailableExtras(): array
    {
        return [
            [
                'id' => 'airport_transfer',
                'name' => 'Airport Transfer',
                'description' => 'Round-trip airport transfer',
                'price' => 150,
                'per' => 'booking',
            ],
            [
                'id' => 'late_checkout',
                'name' => 'Late Check-out',
                'description' => 'Check-out until 3 PM',
                'price' => 100,
                'per' => 'room',
            ],
            [
                'id' => 'early_checkin',
                'name' => 'Early Check-in',
                'description' => 'Check-in from 10 AM',
                'price' => 100,
                'per' => 'room',
            ],
            [
                'id' => 'extra_bed',
                'name' => 'Extra Bed',
                'description' => 'Additional bed in room',
                'price' => 80,
                'per' => 'night',
            ],
            [
                'id' => 'crib',
                'name' => 'Baby Crib',
                'description' => 'Baby crib in room',
                'price' => 0,
                'per' => 'stay',
            ],
            [
                'id' => 'parking',
                'name' => 'Parking',
                'description' => 'Secure parking spot',
                'price' => 50,
                'per' => 'night',
            ],
            [
                'id' => 'spa_access',
                'name' => 'Spa Access',
                'description' => 'Access to spa facilities',
                'price' => 200,
                'per' => 'person',
            ],
            [
                'id' => 'gym_access',
                'name' => 'Gym Access',
                'description' => 'Access to fitness center',
                'price' => 50,
                'per' => 'stay',
            ],
        ];
    }
}
