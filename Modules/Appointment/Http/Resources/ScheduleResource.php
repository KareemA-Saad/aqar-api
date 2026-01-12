<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Schedule Resource
 */
#[OA\Schema(
    schema: 'ScheduleResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(property: 'appointment_day_id', type: 'integer'),
        new OA\Property(property: 'time', type: 'string', example: '09:00'),
        new OA\Property(property: 'allow_multiple', type: 'boolean'),
        new OA\Property(property: 'max_bookings', type: 'integer', nullable: true),
        new OA\Property(property: 'is_blocked', type: 'boolean'),
        new OA\Property(property: 'day', ref: '#/components/schemas/DayResource', nullable: true),
    ]
)]
final class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'appointment_day_id' => $this->appointment_day_id,
            'time' => $this->time,
            'allow_multiple' => (bool) $this->allow_multiple,
            'max_bookings' => $this->max_bookings,
            'is_blocked' => (bool) $this->is_blocked,
            'day' => $this->whenLoaded('day', fn () => new DayResource($this->day)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
