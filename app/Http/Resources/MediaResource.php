<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\MediaHelper;
use App\Models\MediaUploader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Media Resource
 *
 * @mixin MediaUploader
 */
#[OA\Schema(
    schema: 'MediaResource',
    title: 'Media Resource',
    description: 'Media file resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'product-image.jpg'),
        new OA\Property(property: 'alt', type: 'string', example: 'Product main image', nullable: true),
        new OA\Property(property: 'path', type: 'string', example: 'landlord/uploads/media-uploader/product-image123456.jpg'),
        new OA\Property(property: 'extension', type: 'string', example: 'jpg'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'image/jpeg'),
        new OA\Property(property: 'is_image', type: 'boolean', example: true),
        new OA\Property(property: 'size', type: 'string', example: '256 KB'),
        new OA\Property(property: 'dimensions', type: 'string', example: '800 x 600 pixels', nullable: true),
        new OA\Property(
            property: 'urls',
            type: 'object',
            properties: [
                new OA\Property(property: 'original', type: 'string', example: 'https://example.com/storage/landlord/uploads/media-uploader/product-image123456.jpg'),
                new OA\Property(property: 'thumb', type: 'string', example: 'https://example.com/storage/landlord/uploads/media-uploader/thumb/thumb-product-image123456.jpg', nullable: true),
                new OA\Property(property: 'grid', type: 'string', example: 'https://example.com/storage/landlord/uploads/media-uploader/grid/grid-product-image123456.jpg', nullable: true),
                new OA\Property(property: 'large', type: 'string', example: 'https://example.com/storage/landlord/uploads/media-uploader/large/large-product-image123456.jpg', nullable: true),
            ]
        ),
        new OA\Property(
            property: 'uploader',
            type: 'object',
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'admin'),
                new OA\Property(property: 'id', type: 'integer', example: 1),
            ]
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
    ]
)]
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MediaUploader $this */
        $urls = $this->buildUrls();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'alt' => $this->alt,
            'path' => $this->path,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'is_image' => $this->is_image,
            'size' => $this->human_size,
            'dimensions' => $this->dimensions,
            'urls' => $urls,
            'uploader' => [
                'type' => $this->user_type === MediaUploader::USER_TYPE_ADMIN ? 'admin' : 'user',
                'id' => $this->user_id,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Build URLs for all available sizes.
     *
     * @return array<string, string|null>
     */
    private function buildUrls(): array
    {
        $urls = [
            'original' => MediaHelper::getMediaUrl($this->id),
        ];

        // Add size URLs only for images
        if ($this->is_image) {
            $sizes = array_keys(config('media.image_sizes', []));

            foreach ($sizes as $size) {
                $urls[$size] = MediaHelper::getMediaUrl($this->id, $size);
            }
        }

        return $urls;
    }
}
