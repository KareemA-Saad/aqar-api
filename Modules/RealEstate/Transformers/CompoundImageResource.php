<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_CompoundImageResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Master Plan'),
        new OA\Property(property: 'image_path', type: 'string', example: 'compounds/1/master-plan.jpg'),
        new OA\Property(property: 'type', type: 'string', enum: ['gallery', 'master_plan', 'unit_plan']),
        new OA\Property(property: 'alt_text', type: 'string', example: 'Compound master plan'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(
            property: 'urls',
            type: 'object',
            properties: [
                new OA\Property(property: 'original', type: 'string'),
                new OA\Property(property: 'small', type: 'string'),
                new OA\Property(property: 'medium', type: 'string'),
                new OA\Property(property: 'large', type: 'string'),
            ]
        ),
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
            'title' => $this->title,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'type' => $this->type,
            'alt_text' => $this->alt_text,
            'order' => $this->order,
            'urls' => [
                'original' => Storage::url($this->image_path),
                'small' => $this->getThumbnailUrl('small'),
                'medium' => $this->getThumbnailUrl('medium'),
                'large' => $this->getThumbnailUrl('large'),
            ],
        ];
    }

    /**
     * Get thumbnail URL for a specific size.
     */
    protected function getThumbnailUrl(string $size): string
    {
        $pathInfo = pathinfo($this->image_path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
        
        return Storage::url($thumbnailPath);
    }
}
