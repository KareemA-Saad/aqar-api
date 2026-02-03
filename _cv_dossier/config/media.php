<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | The MIME types that are allowed for upload. Extend this list if you need
    | to support additional file types.
    |
    */
    'allowed_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/avif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/zip',
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    |
    | The file extensions that are allowed for upload.
    |
    */
    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'avif',
        'pdf',
        'doc',
        'docx',
        'txt',
        'zip',
        'csv',
        'xlsx',
        'xlsm',
        'xlsb',
        'xltx',
        'pptx',
        'pptm',
        'ppt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Extensions
    |--------------------------------------------------------------------------
    |
    | Extensions that are considered images and can have thumbnails generated.
    |
    */
    'image_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | The maximum allowed file size in kilobytes.
    | Default: 10240 KB (10 MB)
    |
    */
    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10240),

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | The sizes to generate for uploaded images.
    | Format: 'name' => [width, height]
    | Use null for proportional scaling.
    |
    */
    'image_sizes' => [
        'thumb' => [150, 150],
        'grid' => [350, null],
        'large' => [740, null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk to use for storing uploaded media.
    |
    */
    'storage_disk' => env('MEDIA_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | The base paths for storing media files.
    |
    */
    'paths' => [
        'landlord' => 'landlord/uploads/media-uploader',
        'tenant' => 'tenant/{tenant_id}/uploads/media-uploader',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Types
    |--------------------------------------------------------------------------
    |
    | Mapping of user types for media ownership.
    |
    */
    'user_types' => [
        'admin' => 0,
        'user' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Settings
    |--------------------------------------------------------------------------
    |
    | Image quality settings for generated thumbnails.
    |
    */
    'quality' => [
        'jpeg' => 85,
        'png' => 9,
        'webp' => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache TTL for media queries in seconds.
    |
    */
    'cache_ttl' => 300,
];
