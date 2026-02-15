<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | Varsayılan storage disk'i.
    |
    */

    'default_disk' => env('FILE_MANAGER_DEFAULT_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Deduplication
    |--------------------------------------------------------------------------
    |
    | Aynı dosyanın birden fazla kopyasını önleme.
    |
    */

    'deduplication' => [
        'enabled' => env('FILE_MANAGER_DEDUPLICATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Resim işleme ayarları.
    |
    */

    'image' => [
        'resize' => env('FILE_MANAGER_IMAGE_RESIZE', false),
        'max_width' => env('FILE_MANAGER_IMAGE_MAX_WIDTH', 2000),
        'max_height' => env('FILE_MANAGER_IMAGE_MAX_HEIGHT', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URL
    |--------------------------------------------------------------------------
    |
    | Signed URL ayarları.
    |
    */

    'signed_url' => [
        'expires_in_minutes' => env('FILE_MANAGER_SIGNED_URL_EXPIRES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Güvenlik ayarları.
    |
    */

    'security' => [
        'max_file_size_mb' => env('FILE_MANAGER_MAX_SIZE_MB', 100),
        'allowed_mimes' => [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // Archives
            'application/zip', 'application/x-rar-compressed',
        ],
        'scan_virus' => env('FILE_MANAGER_SCAN_VIRUS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | API endpoint'leri için ayarlar.
    |
    */

    'api' => [
        'enabled' => env('FILE_MANAGER_API_ENABLED', true),
        'prefix' => env('FILE_MANAGER_API_PREFIX', 'api/files'),
        'middleware' => ['api', 'auth:sanctum'],
    ],
];
