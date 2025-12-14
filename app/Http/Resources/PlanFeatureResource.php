<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\PlanFeature
 */
#[OA\Schema(
    schema: 'PlanFeatureResource',
    title: 'Plan Feature Resource',
    description: 'Plan feature resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'plan_id', type: 'integer', example: 1),
        new OA\Property(property: 'feature_name', type: 'string', example: 'eCommerce'),
        new OA\Property(property: 'feature_slug', type: 'string', example: 'ecommerce'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class PlanFeatureResource extends JsonResource
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
            'plan_id' => $this->plan_id,
            'feature_name' => $this->feature_name,
            'feature_slug' => $this->generateSlug($this->feature_name),
            'status' => $this->status,
            'order' => $this->order ?? $this->id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Generate a slug from feature name.
     */
    private function generateSlug(string $name): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim($name)));
    }
}
