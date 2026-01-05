<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Http\Resources\TenantUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Customer Dashboard Resource for self-service dashboard.
 */
#[OA\Schema(
    schema: 'CustomerDashboardResource',
    title: 'Customer Dashboard Resource',
    description: 'Customer dashboard overview with stats',
    properties: [
        new OA\Property(property: 'customer', ref: '#/components/schemas/TenantUserResource'),
        new OA\Property(
            property: 'stats',
            properties: [
                new OA\Property(property: 'total_orders', type: 'integer', example: 10),
                new OA\Property(property: 'pending_orders', type: 'integer', example: 2),
                new OA\Property(property: 'completed_orders', type: 'integer', example: 8),
                new OA\Property(property: 'total_spent', type: 'number', format: 'float', example: 1250.50),
                new OA\Property(property: 'wishlist_count', type: 'integer', example: 5),
                new OA\Property(property: 'addresses_count', type: 'integer', example: 2),
                new OA\Property(property: 'support_tickets', type: 'integer', example: 1),
            ],
            type: 'object'
        ),
    ]
)]
class CustomerDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'customer' => new TenantUserResource($this->resource['customer']),
            'stats' => [
                'total_orders' => $this->resource['stats']['total_orders'] ?? 0,
                'pending_orders' => $this->resource['stats']['pending_orders'] ?? 0,
                'completed_orders' => $this->resource['stats']['completed_orders'] ?? 0,
                'total_spent' => $this->resource['stats']['total_spent'] ?? 0,
                'wishlist_count' => $this->resource['stats']['wishlist_count'] ?? 0,
                'addresses_count' => $this->resource['stats']['addresses_count'] ?? 0,
                'support_tickets' => $this->resource['stats']['support_tickets'] ?? 0,
            ],
        ];
    }
}
