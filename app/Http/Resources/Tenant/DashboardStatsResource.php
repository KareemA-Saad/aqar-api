<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Dashboard Stats Resource
 *
 * Resource for tenant dashboard statistics.
 */
#[OA\Schema(
    schema: 'DashboardStatsResource',
    title: 'Dashboard Stats Resource',
    description: 'Tenant dashboard statistics and metrics',
    properties: [
        new OA\Property(property: 'total_users', type: 'integer', example: 150),
        new OA\Property(property: 'total_orders', type: 'integer', example: 500),
        new OA\Property(property: 'total_revenue', type: 'number', format: 'float', example: 15000.00),
        new OA\Property(property: 'pending_orders', type: 'integer', example: 25),
        new OA\Property(property: 'total_products', type: 'integer', example: 100),
        new OA\Property(property: 'low_stock_count', type: 'integer', example: 5),
        new OA\Property(property: 'total_blogs', type: 'integer', example: 30),
        new OA\Property(property: 'today_orders', type: 'integer', example: 10),
        new OA\Property(property: 'today_revenue', type: 'number', format: 'float', example: 500.00),
        new OA\Property(property: 'this_month_revenue', type: 'number', format: 'float', example: 5000.00),
        new OA\Property(
            property: 'growth',
            properties: [
                new OA\Property(property: 'revenue', type: 'number', format: 'float', example: 15.5),
                new OA\Property(property: 'orders', type: 'number', format: 'float', example: 10.2),
                new OA\Property(property: 'users', type: 'number', format: 'float', example: 5.0),
            ],
            type: 'object'
        ),
    ]
)]
class DashboardStatsResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_users' => $this->resource['total_users'] ?? 0,
            'total_orders' => $this->resource['total_orders'] ?? 0,
            'total_revenue' => (float) ($this->resource['total_revenue'] ?? 0),
            'pending_orders' => $this->resource['pending_orders'] ?? 0,
            'total_products' => $this->resource['total_products'] ?? 0,
            'low_stock_count' => $this->resource['low_stock_count'] ?? 0,
            'total_blogs' => $this->resource['total_blogs'] ?? 0,
            'today_orders' => $this->resource['today_orders'] ?? 0,
            'today_revenue' => (float) ($this->resource['today_revenue'] ?? 0),
            'this_month_revenue' => (float) ($this->resource['this_month_revenue'] ?? 0),
            'growth' => [
                'revenue' => (float) ($this->resource['growth']['revenue'] ?? 0),
                'orders' => (float) ($this->resource['growth']['orders'] ?? 0),
                'users' => (float) ($this->resource['growth']['users'] ?? 0),
            ],
        ];
    }
}
