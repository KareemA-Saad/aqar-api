<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\User
 */
#[OA\Schema(
    schema: 'UserResource',
    title: 'User Resource',
    description: 'User (landlord/tenant owner) resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', nullable: true),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'company', type: 'string', example: 'Acme Corp', nullable: true),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/avatar.jpg', nullable: true),
        new OA\Property(property: 'has_subdomain', type: 'boolean', example: true),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
        new OA\Property(property: 'tenants', type: 'array', items: new OA\Items(type: 'object'), nullable: true, description: 'User tenants (when loaded)'),
        new OA\Property(property: 'latest_payment', type: 'object', nullable: true, description: 'Latest payment (when loaded)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class UserResource extends JsonResource
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
            'company' => $this->company,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'image' => $this->image,
            'has_subdomain' => $this->has_subdomain,
            'email_verified' => $this->email_verified,
            'tenants' => TenantResource::collection($this->whenLoaded('tenants')),
            'latest_payment' => new PaymentLogResource($this->whenLoaded('latestPaymentLog')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

