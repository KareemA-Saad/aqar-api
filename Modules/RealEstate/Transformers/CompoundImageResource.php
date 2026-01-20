<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_CompoundImageResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'image_path', type: 'string'),
        new OA\Property(property: 'type', type: 'string', enum: ['gallery', 'master_plan', 'unit_plan']),
        new OA\Property(property: 'alt_text', type: 'string'),
        new OA\Property(property: 'is_primary', type: 'boolean'),
    ]
)]
class CompoundImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'type' => $this->type,
            'alt_text' => $this->alt_text,
            'is_primary' => $this->is_primary,
            'order' => $this->order,
        ];
    }
}
