<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Appointment Resource
 */
#[OA\Schema(
    schema: 'AppointmentResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Dental Checkup'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'slug', type: 'string', example: 'dental-checkup'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', example: 100.00),
        new OA\Property(property: 'person_type', type: 'string', enum: ['single', 'multiple']),
        new OA\Property(property: 'max_person', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
        new OA\Property(property: 'category', ref: '#/components/schemas/CategoryResource', nullable: true),
        new OA\Property(property: 'subcategory', ref: '#/components/schemas/SubcategoryResource', nullable: true),
        new OA\Property(property: 'sub_appointments', type: 'array', items: new OA\Items(ref: '#/components/schemas/SubAppointmentResource')),
        new OA\Property(property: 'meta', type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class AppointmentResource extends JsonResource
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
            'image' => $this->image,
            'price' => (float) $this->price,
            'person_type' => $this->person_type,
            'max_person' => $this->max_person,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'subcategory' => $this->whenLoaded('subcategory', fn () => new SubcategoryResource($this->subcategory)),
            'sub_appointments' => SubAppointmentResource::collection($this->whenLoaded('subAppointments')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'taxes' => TaxResource::collection($this->whenLoaded('taxes')),
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'tags' => $this->meta_tags,
                'image' => $this->meta_image,
            ],
            'bookings_count' => $this->when(isset($this->bookings_count), $this->bookings_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
