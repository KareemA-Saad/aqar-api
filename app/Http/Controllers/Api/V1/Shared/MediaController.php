<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Media\BulkDeleteMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Requests\Media\UploadMediaRequest;
use App\Http\Resources\MediaCollection;
use App\Http\Resources\MediaResource;
use App\Models\Admin;
use App\Models\MediaUploader;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Media Controller
 *
 * Handles media file operations including upload, retrieval, update, and deletion.
 * This controller is shared between landlord and tenant contexts.
 *
 * @package App\Http\Controllers\Api\V1\Shared
 */
#[OA\Tag(
    name: 'Media Management',
    description: 'Media file management endpoints. Available for both landlord and tenant contexts.'
)]
final class MediaController extends BaseApiController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * List all media files with pagination and filters.
     */
    #[OA\Get(
        path: '/api/v1/media',
        summary: 'List media files',
        description: 'Get paginated list of media files with optional filters for type and search.',
        security: [['sanctum_admin' => []], ['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\Parameter(
        name: 'type',
        in: 'query',
        required: false,
        description: 'Filter by file type (image, document)',
        schema: new OA\Schema(type: 'string', enum: ['image', 'document'])
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search term for title or alt text',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Number of items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 20)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'Page number',
        schema: new OA\Schema(type: 'integer', default: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Media files retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Media files retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/MediaResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                        new OA\Property(property: 'per_page', type: 'integer', example: 20),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 3),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $query = MediaUploader::query();

        // Apply type filter
        if ($request->filled('type')) {
            $query->byFileType($request->input('type'));
        }

        // Apply search filter
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Apply user context filter
        $this->applyUserContextFilter($query, $request);

        // Order and paginate
        $media = $query->orderBy('id', 'desc')
            ->paginate($this->getPerPage());

        return $this->paginated($media, MediaResource::class, 'Media files retrieved successfully');
    }

    /**
     * Upload single or multiple media files.
     */
    #[OA\Post(
        path: '/api/v1/media',
        summary: 'Upload media files',
        description: 'Upload one or multiple media files. Supports images and documents.',
        security: [['sanctum_admin' => []], ['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: '#/components/schemas/UploadMediaRequest')
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Media uploaded successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Media uploaded successfully'),
                new OA\Property(
                    property: 'data',
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/MediaResource'),
                        new OA\Schema(
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/MediaResource')
                        ),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Storage limit exceeded or upload failed')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        [$userId, $userType] = $this->resolveUserContext();

        $files = $request->getUploadedFiles();
        $folder = $request->getFolder();
        $altText = $request->getAltText();

        try {
            if (count($files) === 1) {
                // Single file upload
                $media = $this->mediaService->upload(
                    $files[0],
                    $userId,
                    $userType,
                    $folder,
                    $altText
                );

                return $this->created(
                    new MediaResource($media),
                    'Media uploaded successfully'
                );
            }

            // Multiple file upload
            $uploadedMedia = $this->mediaService->uploadMultiple(
                $files,
                $userId,
                $userType,
                $folder,
                $altText
            );

            if (empty($uploadedMedia)) {
                return $this->error('No files were uploaded successfully', 400);
            }

            return $this->created(
                MediaResource::collection($uploadedMedia),
                count($uploadedMedia) . ' files uploaded successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get media details by ID.
     */
    #[OA\Get(
        path: '/api/v1/media/{id}',
        summary: 'Get media details',
        description: 'Get detailed information about a media file including all size URLs.',
        security: [['sanctum_admin' => []], ['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Media ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Media details retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Media details retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MediaResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Media not found')]
    public function show(int $id): JsonResponse
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return $this->notFound('Media not found');
        }

        return $this->success(
            new MediaResource($media),
            'Media details retrieved successfully'
        );
    }

    /**
     * Update media metadata.
     */
    #[OA\Put(
        path: '/api/v1/media/{id}',
        summary: 'Update media metadata',
        description: 'Update media title and alt text.',
        security: [['sanctum_admin' => []], ['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Media ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateMediaRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Media updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Media updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MediaResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Media not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateMediaRequest $request, int $id): JsonResponse
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return $this->notFound('Media not found');
        }

        $updatedMedia = $this->mediaService->updateMedia($media, [
            'title' => $request->getTitle(),
            'alt' => $request->getAltText(),
        ]);

        return $this->success(
            new MediaResource($updatedMedia),
            'Media updated successfully'
        );
    }

    /**
     * Delete a media file.
     */
    #[OA\Delete(
        path: '/api/v1/media/{id}',
        summary: 'Delete media file',
        description: 'Delete a media file and all its thumbnails.',
        security: [['sanctum_admin' => []], ['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Media ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Media deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Media deleted successfully'),
                new OA\Property(property: 'data', type: 'null'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Media not found')]
    public function destroy(int $id): JsonResponse
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return $this->notFound('Media not found');
        }

        try {
            $this->mediaService->deleteMedia($media);

            return $this->success(null, 'Media deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete media: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk delete media files.
     */
    #[OA\Post(
        path: '/api/v1/media/bulk-delete',
        summary: 'Bulk delete media files',
        description: 'Delete multiple media files at once.',
        security: [['sanctum_admin' => []], ['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/BulkDeleteMediaRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Bulk delete completed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: '5 files deleted successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'deleted', type: 'integer', example: 5),
                        new OA\Property(property: 'failed', type: 'integer', example: 0),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function bulkDelete(BulkDeleteMediaRequest $request): JsonResponse
    {
        $ids = $request->getMediaIds();

        $result = $this->mediaService->bulkDelete($ids);

        $message = "{$result['deleted']} files deleted successfully";
        if ($result['failed'] > 0) {
            $message .= ", {$result['failed']} failed";
        }

        return $this->success($result, $message);
    }

    /**
     * Get storage usage information (tenant context only).
     */
    #[OA\Get(
        path: '/api/v1/media/storage-info',
        summary: 'Get storage info',
        description: 'Get storage usage and limit information. Only available in tenant context.',
        security: [['sanctum_user' => []]],
        tags: ['Media Management']
    )]
    #[OA\Response(
        response: 200,
        description: 'Storage info retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Storage info retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'used', type: 'string', example: '50.5 MB'),
                        new OA\Property(property: 'used_bytes', type: 'integer', example: 52953088),
                        new OA\Property(property: 'limit', type: 'string', example: '100 MB'),
                        new OA\Property(property: 'limit_bytes', type: 'integer', example: 104857600),
                        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 50.5),
                        new OA\Property(property: 'unlimited', type: 'boolean', example: false),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 400, description: 'Not in tenant context')]
    public function storageInfo(): JsonResponse
    {
        $tenant = tenant();

        if (!$tenant) {
            return $this->error('Storage info is only available in tenant context', 400);
        }

        $usedBytes = $this->mediaService->getTenantStorageUsage($tenant);
        $paymentLog = $tenant->paymentLog;
        $limitMb = $paymentLog?->package?->storage_permission_feature ?? 100;
        $unlimited = $limitMb === -1;
        $limitBytes = $unlimited ? 0 : $limitMb * 1024 * 1024;

        $data = [
            'used' => $this->mediaService->formatFileSize($usedBytes),
            'used_bytes' => $usedBytes,
            'limit' => $unlimited ? 'Unlimited' : $this->mediaService->formatFileSize($limitBytes),
            'limit_bytes' => $limitBytes,
            'percentage' => $unlimited ? 0 : round(($usedBytes / $limitBytes) * 100, 2),
            'unlimited' => $unlimited,
        ];

        return $this->success($data, 'Storage info retrieved successfully');
    }

    /**
     * Apply user context filter to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     */
    private function applyUserContextFilter($query, Request $request): void
    {
        // For admin users, show all admin uploads
        if (auth('api_admin')->check()) {
            $query->where('user_type', MediaUploader::USER_TYPE_ADMIN);
            return;
        }

        // For regular users, show only their uploads
        if (auth('api_user')->check()) {
            /** @var User $user */
            $user = auth('api_user')->user();
            $query->ownedBy($user->id, MediaUploader::USER_TYPE_USER);
        }
    }

    /**
     * Resolve user context for upload.
     *
     * @return array{0: int, 1: int} [userId, userType]
     */
    private function resolveUserContext(): array
    {
        if (auth('api_admin')->check()) {
            /** @var Admin $admin */
            $admin = auth('api_admin')->user();
            return [$admin->id, MediaUploader::USER_TYPE_ADMIN];
        }

        if (auth('api_user')->check()) {
            /** @var User $user */
            $user = auth('api_user')->user();
            return [$user->id, MediaUploader::USER_TYPE_USER];
        }

        // Fallback (shouldn't reach here due to auth middleware)
        return [0, MediaUploader::USER_TYPE_ADMIN];
    }
}
