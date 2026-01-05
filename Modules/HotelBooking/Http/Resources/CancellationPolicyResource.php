<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Cancellation Policy Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\CancellationPolicy
 */
#[OA\Schema(
    schema: 'CancellationPolicyResource',
    title: 'Cancellation Policy Resource',
    description: 'Cancellation policy with refund tiers',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Flexible Cancellation'),
        new OA\Property(property: 'description', type: 'string', example: 'Free cancellation up to 24 hours before check-in'),
        new OA\Property(property: 'is_refundable', type: 'boolean', example: true),
        new OA\Property(property: 'is_default', type: 'boolean', example: false),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'hotel_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'room_type_id', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'tiers', type: 'array', items: new OA\Items(ref: '#/components/schemas/CancellationPolicyTierResource')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CancellationPolicyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_refundable' => (bool) $this->is_refundable,
            'is_default' => (bool) $this->is_default,
            'status' => (bool) $this->status,
            'hotel_id' => $this->hotel_id,
            'room_type_id' => $this->room_type_id,
            'tiers' => CancellationPolicyTierResource::collection($this->whenLoaded('tiers')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
