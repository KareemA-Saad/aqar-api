<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * SMTP Config Resource
 *
 * Transforms SMTP configuration for API responses.
 */
#[OA\Schema(
    schema: 'SmtpConfigResource',
    title: 'SMTP Config Resource',
    description: 'SMTP configuration response (password masked)',
    properties: [
        new OA\Property(property: 'driver', type: 'string', example: 'smtp'),
        new OA\Property(property: 'host', type: 'string', example: 'smtp.gmail.com'),
        new OA\Property(property: 'port', type: 'string', example: '587'),
        new OA\Property(property: 'username', type: 'string', example: 'your-email@gmail.com'),
        new OA\Property(property: 'password', type: 'string', example: '********'),
        new OA\Property(property: 'encryption', type: 'string', example: 'tls'),
        new OA\Property(property: 'from_email', type: 'string', example: 'noreply@example.com'),
        new OA\Property(property: 'from_name', type: 'string', nullable: true, example: 'My Application'),
    ]
)]
class SmtpConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'driver' => $this->resource['driver'] ?? 'smtp',
            'host' => $this->resource['host'] ?? null,
            'port' => $this->resource['port'] ?? '587',
            'username' => $this->resource['username'] ?? null,
            'password' => '********', // Always masked
            'encryption' => $this->resource['encryption'] ?? 'tls',
            'from_email' => $this->resource['from_email'] ?? null,
            'from_name' => $this->resource['from_name'] ?? null,
        ];
    }
}
