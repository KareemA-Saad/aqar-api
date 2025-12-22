<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Settings Group Resource
 *
 * Transforms settings grouped by category for API responses.
 */
#[OA\Schema(
    schema: 'SettingsGroupResource',
    title: 'Settings Group Resource',
    description: 'Settings grouped by category',
    properties: [
        new OA\Property(property: 'group', type: 'string', example: 'general'),
        new OA\Property(
            property: 'settings',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                description: 'Setting key-value pairs'
            ),
            example: ['site_title' => 'My Site', 'timezone' => 'UTC']
        ),
        new OA\Property(
            property: 'keys',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['site_title', 'timezone', 'site_logo']
        ),
    ]
)]
class SettingsGroupResource extends JsonResource
{
    /**
     * The group name.
     *
     * @var string
     */
    protected string $groupName;

    /**
     * The settings data.
     *
     * @var array<string, mixed>
     */
    protected array $settings;

    /**
     * The available keys in this group.
     *
     * @var array<string>
     */
    protected array $keys;

    /**
     * Create a new resource instance.
     *
     * @param string $groupName The group name
     * @param array<string, mixed> $settings The settings data
     * @param array<string> $keys The available keys
     */
    public function __construct(string $groupName, array $settings, array $keys = [])
    {
        parent::__construct($settings);
        $this->groupName = $groupName;
        $this->settings = $settings;
        $this->keys = $keys;
    }

    /**
     * Create from group name and settings.
     *
     * @param string $groupName The group name
     * @param array<string, mixed> $settings The settings data
     * @param array<string> $keys The available keys
     * @return static
     */
    public static function fromGroup(string $groupName, array $settings, array $keys = []): static
    {
        return new static($groupName, $settings, $keys);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'group' => $this->groupName,
            'settings' => $this->settings,
            'keys' => $this->keys ?: array_keys($this->settings),
        ];
    }
}
