<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Setting Resource
 *
 * Transforms a single setting for API responses.
 */
#[OA\Schema(
    schema: 'SettingResource',
    title: 'Setting Resource',
    description: 'Single setting response',
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'site_title'),
        new OA\Property(property: 'value', description: 'The setting value', example: 'My Site'),
        new OA\Property(property: 'group', type: 'string', nullable: true, example: 'general'),
    ]
)]
class SettingResource extends JsonResource
{
    /**
     * The key for the setting.
     *
     * @var string|null
     */
    protected ?string $key = null;

    /**
     * The group for the setting.
     *
     * @var string|null
     */
    protected ?string $group = null;

    /**
     * Create a new resource instance with key.
     *
     * @param mixed $resource The value
     * @param string|null $key The setting key
     * @param string|null $group The setting group
     */
    public function __construct(mixed $resource, ?string $key = null, ?string $group = null)
    {
        parent::__construct($resource);
        $this->key = $key;
        $this->group = $group;
    }

    /**
     * Create a setting resource from key-value pair.
     *
     * @param string $key The setting key
     * @param mixed $value The setting value
     * @param string|null $group The setting group
     * @return static
     */
    public static function fromKeyValue(string $key, mixed $value, ?string $group = null): static
    {
        return new static($value, $key, $group);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // If resource is a StaticOption model
        if ($this->resource instanceof \App\Models\StaticOption) {
            return [
                'key' => $this->resource->option_name,
                'value' => $this->resource->option_value,
                'group' => $this->group ?? $this->getGroupForKey($this->resource->option_name),
            ];
        }

        // If resource is a value with key set
        return [
            'key' => $this->key,
            'value' => $this->resource,
            'group' => $this->group ?? ($this->key ? $this->getGroupForKey($this->key) : null),
        ];
    }

    /**
     * Get the group for a setting key.
     *
     * @param string $key The setting key
     * @return string|null
     */
    protected function getGroupForKey(string $key): ?string
    {
        $groups = \App\Services\SettingsService::SETTINGS_GROUPS;

        foreach ($groups as $group => $keys) {
            if (in_array($key, $keys, true)) {
                return $group;
            }
        }

        return null;
    }
}
