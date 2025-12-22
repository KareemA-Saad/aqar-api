<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Email Template Resource
 *
 * Transforms email template for API responses.
 */
#[OA\Schema(
    schema: 'EmailTemplateResource',
    title: 'Email Template Resource',
    description: 'Email template response',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'welcome_email'),
        new OA\Property(property: 'name', type: 'string', example: 'Welcome Email'),
        new OA\Property(property: 'description', type: 'string', example: 'Sent when a new user registers'),
        new OA\Property(property: 'subject', type: 'string', example: 'Welcome to {{site_name}}'),
        new OA\Property(property: 'body', type: 'string', example: '<h1>Hello {{user_name}}</h1>'),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
        new OA\Property(
            property: 'variables',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['{{site_name}}', '{{user_name}}', '{{user_email}}']
        ),
    ]
)]
class EmailTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'name' => $this->resource['name'] ?? null,
            'description' => $this->resource['description'] ?? null,
            'subject' => $this->resource['subject'] ?? null,
            'body' => $this->resource['body'] ?? null,
            'enabled' => $this->resource['enabled'] ?? true,
            'variables' => $this->resource['variables'] ?? [],
        ];
    }
}
