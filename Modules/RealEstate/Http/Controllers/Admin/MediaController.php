<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use Modules\RealEstate\Http\Controllers\BaseController;
use App\Models\MediaUploader;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Entities\PropertyImage;
use Modules\RealEstate\Entities\CompoundImage;
use OpenApi\Attributes as OA;

/**
 * RealEstate Media Controller
 * 
 * Handles media uploads for properties and compounds including:
 * - Multi-image upload with thumbnail generation
 * - Gallery management (ordering, primary image)
 * - Floor plans and master plans
 */
#[OA\Tag(name: 'Admin - Media', description: 'Media management for properties and compounds')]
class MediaController extends BaseController
{
    /**
     * Thumbnail sizes for property/compound images.
     */
    protected array $thumbnailSizes = [
        'small' => ['width' => 300, 'height' => 200],
        'medium' => ['width' => 600, 'height' => 400],
        'large' => ['width' => 1200, 'height' => 800],
    ];

    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * Upload multiple images for a property.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{property}/images',
        summary: 'Upload property images',
        description: 'Upload multiple images for a property with automatic thumbnail generation',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Media'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['images'],
                    properties: [
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Array of image files'
                        ),
                        new OA\Property(property: 'titles', type: 'array', items: new OA\Items(type: 'string'), description: 'Optional titles for images'),
                        new OA\Property(property: 'set_first_as_primary', type: 'boolean', description: 'Set first image as primary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Images uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: '3 image(s) uploaded successfully.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'url', type: 'string', example: '/storage/properties/1/image.jpg'),
                                    new OA\Property(property: 'title', type: 'string', example: 'Living Room'),
                                    new OA\Property(property: 'order', type: 'integer', example: 1),
                                    new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Property not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Property not found.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/RE_ValidationErrorResponse')
            ),
        ]
    )]
    public function uploadPropertyImages(Request $request, int $property): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
            'titles' => 'nullable|array',
            'titles.*' => 'nullable|string|max:255',
            'set_first_as_primary' => 'nullable|boolean',
        ]);

        $property = Property::findOrFail($property);

        $uploadedImages = [];
        $order = PropertyImage::where('property_id', $property->id)->max('order') ?? 0;

        DB::transaction(function () use ($request, $property, &$uploadedImages, &$order) {
            foreach ($request->file('images') as $index => $image) {
                $order++;
                
                // Upload original image
                $path = $image->store('properties/' . $property->id, 'public');
                
                // Generate thumbnails
                $this->generateThumbnails($path);
                
                // Create property image record
                $propertyImage = PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $path,
                    'title' => $request->input('titles.' . $index) ?? basename($path),
                    'order' => $order,
                    'is_primary' => ($index === 0 && $request->boolean('set_first_as_primary', false)),
                ]);

                $uploadedImages[] = $propertyImage;
            }

            // Update property thumbnail if first upload
            if ($request->boolean('set_first_as_primary', true) && !$property->thumbnail) {
                $property->update(['thumbnail' => $uploadedImages[0]->image_path]);
            }
        });

        return response()->json([
            'message' => count($uploadedImages) . ' image(s) uploaded successfully.',
            'data' => $uploadedImages->map(fn($img) => [
                'id' => $img->id,
                'url' => Storage::url($img->image_path),
                'title' => $img->title,
                'order' => $img->order,
                'is_primary' => $img->is_primary,
            ]),
        ], 201);
    }

    /**
     * Upload multiple images for a compound.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/{compound}/images',
        summary: 'Upload compound images',
        description: 'Upload multiple images for a compound (gallery, master plan, unit plans)',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Media'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['images'],
                    properties: [
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary')
                        ),
                        new OA\Property(property: 'type', type: 'string', enum: ['gallery', 'master_plan', 'unit_plan'], description: 'Image type'),
                        new OA\Property(property: 'titles', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Images uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: '3 image(s) uploaded successfully.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'url', type: 'string', example: '/storage/compounds/1/image.jpg'),
                                    new OA\Property(property: 'title', type: 'string', example: 'Master Plan'),
                                    new OA\Property(property: 'type', type: 'string', example: 'gallery'),
                                    new OA\Property(property: 'order', type: 'integer', example: 1),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Compound not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Compound not found.'),
                    ]
                )
            ),
        ]
    )]
    public function uploadCompoundImages(Request $request, int $compound): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'type' => 'required|string|in:gallery,master_plan,unit_plan',
            'titles' => 'nullable|array',
            'titles.*' => 'nullable|string|max:255',
        ]);

        $compound = Compound::findOrFail($compound);

        $uploadedImages = [];
        $order = CompoundImage::where('compound_id', $compound->id)->max('order') ?? 0;

        DB::transaction(function () use ($request, $compound, &$uploadedImages, &$order) {
            foreach ($request->file('images') as $index => $image) {
                $order++;
                
                // Upload original image
                $path = $image->store('compounds/' . $compound->id, 'public');
                
                // Generate thumbnails
                $this->generateThumbnails($path);
                
                // Create compound image record
                $compoundImage = CompoundImage::create([
                    'compound_id' => $compound->id,
                    'image_path' => $path,
                    'title' => $request->input('titles.' . $index) ?? basename($path),
                    'type' => $request->input('type'),
                    'order' => $order,
                ]);

                $uploadedImages[] = $compoundImage;
            }

            // Update compound thumbnail if first gallery upload
            if ($request->input('type') === 'gallery' && !$compound->thumbnail) {
                $compound->update(['thumbnail' => $uploadedImages[0]->image_path]);
            }
        });

        return response()->json([
            'message' => count($uploadedImages) . ' image(s) uploaded successfully.',
            'data' => $uploadedImages->map(fn($img) => [
                'id' => $img->id,
                'url' => Storage::url($img->image_path),
                'title' => $img->title,
                'type' => $img->type,
                'order' => $img->order,
            ]),
        ], 201);
    }

    /**
     * Reorder property images.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{property}/images/reorder',
        summary: 'Reorder property images',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Media'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order'],
                properties: [
                    new OA\Property(
                        property: 'order',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Array of image IDs in desired order',
                        example: [5, 3, 1, 4, 2]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Images reordered successfully'),
        ]
    )]
    public function reorderPropertyImages(Request $request, int $property): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:re_property_images,id',
        ]);

        DB::transaction(function () use ($request, $property) {
            foreach ($request->input('order') as $position => $imageId) {
                PropertyImage::where('id', $imageId)
                    ->where('property_id', $property)
                    ->update(['order' => $position + 1]);
            }
        });

        return response()->json([
            'message' => 'Images reordered successfully.',
        ]);
    }

    /**
     * Set primary property image.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{property}/images/{image}/primary',
        summary: 'Set primary property image',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Media'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'image', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Primary image updated'),
        ]
    )]
    public function setPrimaryImage(int $property, int $image): JsonResponse
    {
        DB::transaction(function () use ($property, $image) {
            // Remove primary flag from all images
            PropertyImage::where('property_id', $property)->update(['is_primary' => false]);
            
            // Set new primary image
            $propertyImage = PropertyImage::where('property_id', $property)
                ->where('id', $image)
                ->firstOrFail();
            
            $propertyImage->update(['is_primary' => true]);
            
            // Update property thumbnail
            Property::find($property)->update(['thumbnail' => $propertyImage->image_path]);
        });

        return response()->json([
            'message' => 'Primary image updated successfully.',
        ]);
    }

    /**
     * Delete property image.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/properties/{property}/images/{image}',
        summary: 'Delete property image',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Media'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'image', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Image deleted'),
        ]
    )]
    public function deletePropertyImage(int $property, int $image): JsonResponse
    {
        $propertyImage = PropertyImage::where('property_id', $property)
            ->where('id', $image)
            ->firstOrFail();

        // Delete file and thumbnails
        $this->deleteImageWithThumbnails($propertyImage->image_path);

        $propertyImage->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }

    /**
     * Delete compound image.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/compounds/{compound}/images/{image}',
        summary: 'Delete compound image',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Media'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'image', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Image deleted'),
        ]
    )]
    public function deleteCompoundImage(int $compound, int $image): JsonResponse
    {
        $compoundImage = CompoundImage::where('compound_id', $compound)
            ->where('id', $image)
            ->firstOrFail();

        // Delete file and thumbnails
        $this->deleteImageWithThumbnails($compoundImage->image_path);

        $compoundImage->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }

    /**
     * Generate thumbnails for an image.
     */
    protected function generateThumbnails(string $path): void
    {
        $fullPath = Storage::disk('public')->path($path);
        
        foreach ($this->thumbnailSizes as $sizeName => $dimensions) {
            $thumbnailPath = $this->getThumbnailPath($path, $sizeName);
            
            Image::read($fullPath)
                ->cover($dimensions['width'], $dimensions['height'])
                ->save(Storage::disk('public')->path($thumbnailPath));
        }
    }

    /**
     * Delete image file with all thumbnails.
     */
    protected function deleteImageWithThumbnails(string $path): void
    {
        // Delete original
        Storage::disk('public')->delete($path);
        
        // Delete thumbnails
        foreach (array_keys($this->thumbnailSizes) as $sizeName) {
            $thumbnailPath = $this->getThumbnailPath($path, $sizeName);
            Storage::disk('public')->delete($thumbnailPath);
        }
    }

    /**
     * Get thumbnail path for a size.
     */
    protected function getThumbnailPath(string $originalPath, string $sizeName): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '_' . $sizeName . '.' . $pathInfo['extension'];
    }
}
