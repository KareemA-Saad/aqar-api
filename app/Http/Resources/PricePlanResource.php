<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PricePlan
 */
class PricePlanResource extends JsonResource
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
            'subtitle' => $this->subtitle,
            'features' => $this->features,
            'type' => $this->type,
            'status' => $this->status,
            'price' => $this->price,
            'free_trial' => $this->free_trial,
            'zero_price' => $this->zero_price,
            'permissions' => [
                'page' => $this->page_permission_feature,
                'blog' => $this->blog_permission_feature,
                'product' => $this->product_permission_feature,
                'portfolio' => $this->portfolio_permission_feature,
                'storage' => $this->storage_permission_feature,
                'appointment' => $this->appointment_permission_feature,
            ],
            'plan_features' => PlanFeatureResource::collection($this->whenLoaded('planFeatures')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

