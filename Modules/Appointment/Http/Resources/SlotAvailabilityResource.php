<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Slot Availability Resource
 */
#[OA\Schema(
    schema: 'SlotAvailabilityResource',
    properties: [
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2024-01-15'),
        new OA\Property(property: 'day_name', type: 'string', example: 'Monday'),
        new OA\Property(property: 'is_available', type: 'boolean', example: true),
        new OA\Property(
            property: 'slots',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'time', type: 'string', example: '09:00'),
                    new OA\Property(property: 'available', type: 'boolean', example: true),
                    new OA\Property(property: 'booked_count', type: 'integer', example: 0),
                    new OA\Property(property: 'max_bookings', type: 'integer', nullable: true),
                ],
                type: 'object'
            )
        ),
    ]
)]
final class SlotAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'] ?? null,
            'day_name' => $this['day_name'] ?? null,
            'is_available' => $this['is_available'] ?? false,
            'slots' => collect($this['slots'] ?? [])->map(fn ($slot) => [
                'time' => $slot['time'] ?? null,
                'available' => $slot['available'] ?? false,
                'booked_count' => $slot['booked_count'] ?? 0,
                'max_bookings' => $slot['max_bookings'] ?? null,
            ])->toArray(),
        ];
    }
}
