<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Cancellation Policy Tier Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\CancellationPolicyTier
 */
#[OA\Schema(
    schema: 'CancellationPolicyTierResource',
    title: 'Cancellation Policy Tier Resource',
    description: 'Individual tier within a cancellation policy',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'hours_before_checkin', type: 'integer', example: 24),
        new OA\Property(property: 'refund_percentage', type: 'integer', example: 100),
        new OA\Property(property: 'description', type: 'string', example: 'Cancel at least 24 hours before check-in for 100% refund'),
    ]
)]
class CancellationPolicyTierResource extends JsonResource
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
            'hours_before_checkin' => (int) $this->hours_before_checkin,
            'refund_percentage' => (int) $this->refund_percentage,
            'description' => $this->description,
        ];
    }
}
