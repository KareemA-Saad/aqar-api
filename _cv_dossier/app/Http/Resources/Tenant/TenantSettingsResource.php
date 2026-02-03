<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Tenant Settings Resource
 *
 * Resource for tenant settings data.
 */
#[OA\Schema(
    schema: 'TenantSettingsResource',
    title: 'Tenant Settings Resource',
    description: 'Tenant settings data organized by group',
    properties: [
        new OA\Property(property: 'group', type: 'string', example: 'general'),
        new OA\Property(
            property: 'settings',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['site_title' => 'My Store', 'site_timezone' => 'UTC']
        ),
        new OA\Property(
            property: 'keys',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['site_title', 'site_tag_line', 'site_timezone']
        ),
    ]
)]
class TenantSettingsResource extends JsonResource
{
    /**
     * The settings group name.
     */
    private string $group;

    /**
     * The settings data.
     *
     * @var array<string, mixed>
     */
    private array $settings;

    /**
     * Create a new resource instance.
     *
     * @param string $group
     * @param array<string, mixed> $settings
     */
    public function __construct(string $group, array $settings)
    {
        $this->group = $group;
        $this->settings = $settings;
        parent::__construct($settings);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'group' => $this->group,
            'settings' => $this->settings,
            'keys' => array_keys($this->settings),
        ];
    }
}
