<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Address Resource for API responses.
 */
#[OA\Schema(
    schema: 'AddressResource',
    title: 'Address Resource',
    description: 'Customer address resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'phone', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'address_line_1', type: 'string', example: '123 Main Street'),
        new OA\Property(property: 'address_line_2', type: 'string', example: 'Apt 4B', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true),
        new OA\Property(property: 'postal_code', type: 'string', example: '10001', nullable: true),
        new OA\Property(property: 'country', type: 'string', example: 'USA'),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
        new OA\Property(property: 'type', type: 'string', enum: ['shipping', 'billing', 'both'], example: 'shipping'),
        new OA\Property(
            property: 'formatted_address',
            type: 'string',
            example: '123 Main Street, Apt 4B, New York, NY 10001, USA'
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both Eloquent model and stdClass from raw DB queries
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'id' => $data['id'] ?? $this->id,
            'name' => $data['name'] ?? $this->name ?? null,
            'phone' => $data['phone'] ?? $this->phone ?? null,
            'address_line_1' => $data['address_line_1'] ?? $this->address_line_1 ?? null,
            'address_line_2' => $data['address_line_2'] ?? $this->address_line_2 ?? null,
            'city' => $data['city'] ?? $this->city ?? null,
            'state' => $data['state'] ?? $this->state ?? null,
            'postal_code' => $data['postal_code'] ?? $this->postal_code ?? null,
            'country' => $data['country'] ?? $this->country ?? null,
            'is_default' => (bool) ($data['is_default'] ?? $this->is_default ?? false),
            'type' => $data['type'] ?? $this->type ?? 'shipping',
            'formatted_address' => $this->formatAddress($data),
            'created_at' => isset($data['created_at']) 
                ? (is_string($data['created_at']) ? $data['created_at'] : $data['created_at']->toISOString())
                : ($this->created_at?->toISOString() ?? null),
            'updated_at' => isset($data['updated_at'])
                ? (is_string($data['updated_at']) ? $data['updated_at'] : $data['updated_at']->toISOString())
                : ($this->updated_at?->toISOString() ?? null),
        ];
    }

    /**
     * Format address into a single string.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function formatAddress(array $data): string
    {
        $parts = array_filter([
            $data['address_line_1'] ?? $this->address_line_1 ?? null,
            $data['address_line_2'] ?? $this->address_line_2 ?? null,
            $data['city'] ?? $this->city ?? null,
            $data['state'] ?? $this->state ?? null,
            $data['postal_code'] ?? $this->postal_code ?? null,
            $data['country'] ?? $this->country ?? null,
        ]);

        return implode(', ', $parts);
    }
}
