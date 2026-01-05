<?php

declare(strict_types=1);

namespace Modules\CouponManage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductCouponResource',
    title: 'Product Coupon Resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'discount', type: 'number'),
        new OA\Property(property: 'discount_type', type: 'string', enum: ['percentage', 'amount']),
        new OA\Property(property: 'discount_on', type: 'string'),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date'),
        new OA\Property(property: 'status', type: 'integer'),
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
            'discount' => (float) $this->discount,
            'discount_type' => $this->discount_type,
            'discount_on' => $this->discount_on,
            'discount_on_details' => $this->discount_on_details,
            'expire_date' => $this->expire_date,
            'status' => $this->status,
            'is_active' => (bool) $this->status,
            'is_expired' => $this->expire_date ? now()->isAfter($this->expire_date) : false,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
