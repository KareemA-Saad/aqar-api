<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Tax Resource
 */
#[OA\Schema(
    schema: 'TaxResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'percentage', type: 'number', example: 10.00),
        new OA\Property(property: 'appointment_id', type: 'integer'),
    ]
)]
final class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'percentage' => (float) $this->percentage,
            'appointment_id' => $this->appointment_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
