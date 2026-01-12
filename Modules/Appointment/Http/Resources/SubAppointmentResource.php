<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Sub-Appointment Resource
 */
#[OA\Schema(
    schema: 'SubAppointmentResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Deep Cleaning'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'slug', type: 'string', example: 'deep-cleaning'),
        new OA\Property(property: 'appointment_id', type: 'integer'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', example: 50.00),
        new OA\Property(property: 'duration', type: 'integer', nullable: true, description: 'Duration in minutes'),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
final class SubAppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', app()->getLocale()),
            'title_translations' => $this->getTranslations('title'),
            'description' => $this->getTranslation('description', app()->getLocale()),
            'description_translations' => $this->getTranslations('description'),
            'slug' => $this->slug,
            'appointment_id' => $this->appointment_id,
            'image' => $this->image,
            'price' => (float) $this->price,
            'duration' => $this->duration,
            'status' => $this->status,
            'appointment' => $this->whenLoaded('appointment', fn () => new AppointmentResource($this->appointment)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
