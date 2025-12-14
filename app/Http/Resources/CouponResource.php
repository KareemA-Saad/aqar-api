<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\Coupon
 */
#[OA\Schema(
    schema: 'CouponResource',
    title: 'Coupon Resource',
    description: 'Coupon resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Summer Sale'),
        new OA\Property(property: 'code', type: 'string', example: 'SUMMER20'),
        new OA\Property(property: 'discount_amount', type: 'number', format: 'float', example: 20.00),
        new OA\Property(
            property: 'discount_type',
            type: 'string',
            enum: ['percentage', 'amount'],
            example: 'percentage'
        ),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'max_use_qty', type: 'integer', example: 100, nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_valid', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CouponResource extends JsonResource
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
            'title' => $this->title,
            'code' => $this->code,
            'discount_amount' => (float) $this->discount_amount,
            'discount_type' => $this->discount_type,
            'status' => $this->status,
            'max_use_qty' => $this->max_use_qty,
            'expire_date' => $this->expire_date?->toISOString(),
            'is_valid' => $this->isValid(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
