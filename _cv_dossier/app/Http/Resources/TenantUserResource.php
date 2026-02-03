<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\TenantUser
 */
#[OA\Schema(
    schema: 'TenantUserResource',
    title: 'Tenant User Resource',
    description: 'Tenant user (end-user within a tenant) resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Smith'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@acme-corp.com'),
        new OA\Property(property: 'username', type: 'string', example: 'janesmith', nullable: true),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'address', type: 'string', example: '456 Oak St', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'San Francisco', nullable: true),
        new OA\Property(property: 'state', type: 'string', example: 'CA', nullable: true),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/avatar.jpg', nullable: true),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class TenantUserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'image' => $this->image,
            'email_verified' => $this->email_verified,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

