<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;

/**
 * @mixin Permission
 */
#[OA\Schema(
    schema: 'PermissionResource',
    title: 'Permission Resource',
    description: 'Permission resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'page-list'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'api_admin'),
        new OA\Property(property: 'group', type: 'string', example: 'page', nullable: true, description: 'Permission group derived from name'),
    ]
)]
class PermissionResource extends JsonResource
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
            'guard_name' => $this->guard_name,
            'group' => $this->getPermissionGroup(),
        ];
    }

    /**
     * Extract the permission group from the permission name.
     *
     * Examples:
     * - page-list -> page
     * - blog-category-edit -> blog-category
     * - admin-management-create -> admin-management
     */
    private function getPermissionGroup(): ?string
    {
        $name = $this->name;

        // Common action suffixes to strip
        $actions = ['-list', '-create', '-edit', '-delete', '-view', '-manage'];

        foreach ($actions as $action) {
            if (str_ends_with($name, $action)) {
                return substr($name, 0, -strlen($action));
            }
        }

        return $name;
    }
}
