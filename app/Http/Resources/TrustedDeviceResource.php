<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Trusted Device Resource
 *
 * API Resource for trusted device data transformation.
 *
 * @mixin \App\Models\TrustedDevice
 */
#[OA\Schema(
    schema: 'TrustedDeviceResource',
    title: 'Trusted Device Resource',
    description: 'Trusted device data for 2FA',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'device_name', type: 'string', example: 'Chrome on Windows'),
        new OA\Property(property: 'ip_address', type: 'string', example: '192.168.1.1'),
        new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', example: '2024-12-17T10:00:00.000000Z', nullable: true),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2025-01-17T10:00:00.000000Z'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-12-17T10:00:00.000000Z'),
        new OA\Property(property: 'is_current', type: 'boolean', example: true),
    ]
)]
class TrustedDeviceResource extends JsonResource
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
            'device_name' => $this->device_name,
            'ip_address' => $this->ip_address,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'expires_at' => $this->expires_at->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'is_current' => $this->isCurrentDevice($request),
        ];
    }

    /**
     * Check if this is the current request's device.
     */
    private function isCurrentDevice(Request $request): bool
    {
        $deviceToken = $request->header('X-Device-Token');
        return $deviceToken && $this->device_token === $deviceToken;
    }
}
