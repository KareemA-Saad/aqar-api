<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CouponResource',
    title: 'Coupon Resource',
    description: 'Coupon resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Summer Sale'),
        new OA\Property(property: 'code', type: 'string', example: 'SUMMER2026'),
        new OA\Property(property: 'discount', type: 'string', example: '20'),
        new OA\Property(property: 'discount_type', type: 'string', example: 'percentage'),
        new OA\Property(property: 'discount_on', type: 'string', example: 'all', nullable: true),
        new OA\Property(property: 'discount_on_details', type: 'string', nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'publish'),
        new OA\Property(property: 'is_expired', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'discount_on' => $this->discount_on,
            'discount_on_details' => $this->discount_on_details,
            'expire_date' => $this->expire_date,
            'status' => $this->status,
            'is_expired' => $this->expire_date ? $this->expire_date < now() : false,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
