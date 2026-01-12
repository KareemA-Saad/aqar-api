<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Theme Resource
 *
 * Transforms theme model data for API responses.
 */
#[OA\Schema(
    schema: 'ThemeResource',
    title: 'Theme Resource',
    description: 'Theme data representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'E-Commerce Theme'),
        new OA\Property(property: 'slug', type: 'string', example: 'ecommerce'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Modern e-commerce theme with shopping cart'),
        new OA\Property(property: 'status', type: 'boolean', example: true, description: 'Active/inactive status'),
        new OA\Property(property: 'is_available', type: 'boolean', example: true, description: 'Available for selection'),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'https://example.com/themes/ecommerce.jpg'),
        new OA\Property(property: 'url', type: 'string', nullable: true, example: 'https://demo.example.com/ecommerce'),
        new OA\Property(property: 'theme_code', type: 'string', nullable: true, example: 'ecom-v1'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
    ]
)]
class ThemeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'is_available' => $this->is_available,
            'image' => $this->image,
            'url' => $this->url,
            'theme_code' => $this->theme_code,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
