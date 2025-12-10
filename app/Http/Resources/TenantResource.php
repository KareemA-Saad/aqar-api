<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\Tenant
 */
#[OA\Schema(
    schema: 'TenantResource',
    title: 'Tenant Resource',
    description: 'Tenant (property/organization) resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'acme-corp', description: 'Tenant unique identifier (subdomain)'),
        new OA\Property(property: 'user_id', type: 'integer', example: 1, description: 'Owner user ID'),
        new OA\Property(property: 'instruction_status', type: 'string', example: 'active', description: 'Current tenant status'),
        new OA\Property(property: 'theme', type: 'string', example: 'default', nullable: true, description: 'Theme name'),
        new OA\Property(property: 'theme_code', type: 'string', example: '#FF5733', nullable: true, description: 'Theme color code'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource', nullable: true, description: 'Owner user details (when loaded)'),
        new OA\Property(
            property: 'domains',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DomainResource'),
            nullable: true,
            description: 'Tenant domains (when loaded)'
        ),
        new OA\Property(property: 'latest_payment', ref: '#/components/schemas/PaymentLogResource', nullable: true, description: 'Latest payment record (when loaded)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class TenantResource extends JsonResource
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
            'user_id' => $this->user_id,
            'instruction_status' => $this->instruction_status,
            'theme' => $this->theme ?? null,
            'theme_code' => $this->theme_code ?? null,
            'user' => new UserResource($this->whenLoaded('user')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'latest_payment' => new PaymentLogResource($this->whenLoaded('paymentLog')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

