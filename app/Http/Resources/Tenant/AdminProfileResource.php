<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Admin Profile Resource
 *
 * Resource for admin profile data.
 */
#[OA\Schema(
    schema: 'AdminProfileResource',
    title: 'Admin Profile Resource',
    description: 'Admin user profile data',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'company', type: 'string', example: 'My Company'),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'state', type: 'string', example: 'NY'),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
        new OA\Property(property: 'country', type: 'string', example: 'USA'),
        new OA\Property(property: 'image', type: 'integer', example: 123, nullable: true),
        new OA\Property(property: 'image_url', type: 'string', format: 'url', example: 'https://example.com/images/avatar.jpg', nullable: true),
        new OA\Property(property: 'two_factor_enabled', type: 'boolean', example: false),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class AdminProfileResource extends JsonResource
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
            'username' => $this->username ?? null,
            'mobile' => $this->mobile ?? null,
            'company' => $this->company ?? null,
            'city' => $this->city ?? null,
            'state' => $this->state ?? null,
            'address' => $this->address ?? null,
            'country' => $this->country ?? null,
            'image' => $this->image ?? null,
            'image_url' => $this->getImageUrl(),
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'email_verified' => $this->isEmailVerified(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get the image URL.
     *
     * @return string|null
     */
    private function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Try to get from media relation if exists
        if ($this->relationLoaded('media') && $this->media) {
            return $this->media->url ?? null;
        }

        // Fallback to helper function if available
        if (function_exists('get_attachment_image_by_id')) {
            return get_attachment_image_by_id($this->image, 'full', false);
        }

        return null;
    }

    /**
     * Check if two-factor authentication is enabled.
     *
     * @return bool
     */
    private function hasTwoFactorEnabled(): bool
    {
        // Check for User model
        if (method_exists($this->resource, 'hasTwoFactorEnabled')) {
            return $this->resource->hasTwoFactorEnabled();
        }

        // Check for Admin model with google_2fa fields
        if (isset($this->google_2fa_secret) && isset($this->google_2fa_enable)) {
            return !empty($this->google_2fa_secret) && $this->google_2fa_enable;
        }

        return false;
    }

    /**
     * Check if email is verified.
     *
     * @return bool
     */
    private function isEmailVerified(): bool
    {
        if (isset($this->email_verified)) {
            return (bool) $this->email_verified;
        }

        if (isset($this->email_verified_at)) {
            return $this->email_verified_at !== null;
        }

        return false;
    }
}
