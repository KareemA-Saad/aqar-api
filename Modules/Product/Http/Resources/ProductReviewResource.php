<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductReviewResource',
    title: 'Product Review Resource',
    description: 'Product review resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 10),
        new OA\Property(property: 'user', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'avatar', type: 'string', nullable: true),
        ]),
        new OA\Property(property: 'rating', type: 'integer', example: 5),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Great product!'),
        new OA\Property(property: 'comment', type: 'string', example: 'Highly recommend this product.'),
        new OA\Property(property: 'is_verified_purchase', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class ProductReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->when($this->relationLoaded('user') && $this->user, fn() => $this->user->name),
            ],
            'rating' => $this->rating,
            'review' => $this->review,
            'is_verified_purchase' => $this->is_verified_purchase ?? false,
            'status' => $this->status ?? 1,
            'admin_reply' => $this->admin_reply ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
