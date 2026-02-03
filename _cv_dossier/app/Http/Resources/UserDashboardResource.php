<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * User Dashboard Resource
 *
 * Resource for user dashboard data.
 */
#[OA\Schema(
    schema: 'UserDashboardResource',
    title: 'User Dashboard Resource',
    description: 'User dashboard statistics and summary',
    properties: [
        new OA\Property(property: 'tenants_count', type: 'integer', example: 3),
        new OA\Property(property: 'active_packages', type: 'integer', example: 2),
        new OA\Property(property: 'support_tickets_count', type: 'integer', example: 5),
        new OA\Property(property: 'open_tickets_count', type: 'integer', example: 1),
        new OA\Property(property: 'total_spent', type: 'number', format: 'float', example: 299.99),
        new OA\Property(
            property: 'recent_payments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaymentLogResource')
        ),
    ]
)]
class UserDashboardResource extends JsonResource
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
            'tenants_count' => $this->resource['tenants_count'],
            'active_packages' => $this->resource['active_packages'],
            'support_tickets_count' => $this->resource['support_tickets_count'],
            'open_tickets_count' => $this->resource['open_tickets_count'],
            'total_spent' => $this->resource['total_spent'],
            'recent_payments' => PaymentLogResource::collection($this->resource['recent_payments']),
        ];
    }
}

