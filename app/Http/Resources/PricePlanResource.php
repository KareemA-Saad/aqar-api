<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\PricePlan
 */
#[OA\Schema(
    schema: 'PricePlanResource',
    title: 'Price Plan Resource',
    description: 'Price plan resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Premium Plan'),
        new OA\Property(property: 'subtitle', type: 'string', example: 'Best for growing businesses', nullable: true),
        new OA\Property(property: 'features_text', type: 'string', example: 'All premium features included', nullable: true),
        new OA\Property(
            property: 'type',
            type: 'integer',
            description: '0 = Monthly, 1 = Yearly, 2 = Lifetime',
            enum: [0, 1, 2],
            example: 0
        ),
        new OA\Property(property: 'type_label', type: 'string', example: 'Monthly'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
        new OA\Property(property: 'formatted_price', type: 'string', example: '$99.99'),
        new OA\Property(property: 'has_trial', type: 'boolean', example: true),
        new OA\Property(property: 'trial_days', type: 'integer', example: 14),
        new OA\Property(property: 'zero_price', type: 'boolean', example: false),
        new OA\Property(
            property: 'permissions',
            properties: [
                new OA\Property(property: 'page', type: 'integer', example: 10, nullable: true),
                new OA\Property(property: 'blog', type: 'integer', example: 50, nullable: true),
                new OA\Property(property: 'product', type: 'integer', example: 100, nullable: true),
                new OA\Property(property: 'portfolio', type: 'integer', example: 20, nullable: true),
                new OA\Property(property: 'storage', type: 'integer', example: 1024, nullable: true, description: 'Storage in MB'),
                new OA\Property(property: 'appointment', type: 'integer', example: 100, nullable: true),
            ],
            type: 'object',
            description: 'Permission limits (-1 for unlimited, null for not included)'
        ),
        new OA\Property(
            property: 'plan_features',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PlanFeatureResource'),
            description: 'List of plan features'
        ),
        new OA\Property(
            property: 'faq',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                ],
                type: 'object'
            ),
            nullable: true
        ),
        new OA\Property(property: 'subscribers_count', type: 'integer', example: 150, description: 'Number of active subscribers'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class PricePlanResource extends JsonResource
{
    /**
     * Plan type labels.
     */
    private const TYPE_LABELS = [
        0 => 'Monthly',
        1 => 'Yearly',
        2 => 'Lifetime',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $faq = null;
        if ($this->faq) {
            $faq = is_string($this->faq) ? @unserialize($this->faq) : $this->faq;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'features_text' => $this->features,
            'type' => $this->type,
            'type_label' => self::TYPE_LABELS[$this->type] ?? 'Unknown',
            'status' => $this->status,
            'price' => (float) $this->price,
            'formatted_price' => $this->formatPrice((float) $this->price),
            'has_trial' => (bool) ($this->has_trial ?? ($this->free_trial > 0)),
            'trial_days' => (int) ($this->trial_days ?? $this->free_trial ?? 0),
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
            'faq' => $faq,
            'subscribers_count' => $this->when(
                $this->relationLoaded('paymentLogs'),
                fn () => $this->paymentLogs->where('payment_status', 1)->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format price with currency symbol.
     */
    private function formatPrice(float $price): string
    {
        // Could be extended to use site settings for currency
        return '$' . number_format($price, 2);
    }
}
