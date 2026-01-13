<?php

declare(strict_types=1);

namespace Modules\EmailTemplate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmailTemplateResource',
    title: 'Email Template Resource',
    description: 'Email template resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Welcome Email'),
        new OA\Property(property: 'type', type: 'string', example: 'user_registration'),
        new OA\Property(property: 'subject', type: 'string', example: 'Welcome to {{site_name}}'),
        new OA\Property(property: 'body', type: 'string', example: 'Hello {{user_name}}, welcome!'),
        new OA\Property(property: 'variables', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class EmailTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'subject' => $this->subject,
            'body' => $this->body,
            'variables' => $this->variables ? json_decode($this->variables, true) : null,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
