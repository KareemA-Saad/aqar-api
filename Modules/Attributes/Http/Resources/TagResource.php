<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TagResource',
    title: 'Tag Resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'tag_text', type: 'string'),
    ]
)]
class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag_text' => $this->tag_text,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
