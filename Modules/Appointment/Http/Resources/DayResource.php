<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Day Resource
 */
#[OA\Schema(
    schema: 'DayResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'day', type: 'string', example: 'Monday'),
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
        new OA\Property(property: 'day_type', ref: '#/components/schemas/DayTypeResource', nullable: true),
        new OA\Property(property: 'schedules', type: 'array', items: new OA\Items(ref: '#/components/schemas/ScheduleResource')),
    ]
)]
final class DayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day' => $this->day,
            'appointment_id' => $this->appointment_id,
            'status' => $this->status,
            'day_type' => $this->whenLoaded('dayType', fn () => new DayTypeResource($this->dayType)),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
