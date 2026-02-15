<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | Varsayılan storage disk'i. 'public', 's3', 's3_public' olabilir.
    |
    */

    'default_disk' => env('IMAGE_UPLOAD_DEFAULT_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Max File Size
    |--------------------------------------------------------------------------
    |
    | Maksimum dosya boyutu (KB cinsinden).
    |
    */

    'max_file_size' => env('IMAGE_UPLOAD_MAX_SIZE', 5120), // 5MB

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | İzin verilen dosya tipleri.
    |
    */

    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml', // SVG
        'application/pdf', // PDF
    ],

    /*
    |--------------------------------------------------------------------------
    | File Type Groups
    |--------------------------------------------------------------------------
    |
    | Dosya tipi grupları (resize yapılabilir olanlar vs).
    |
    */

    'file_type_groups' => [
        'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'vectors' => ['image/svg+xml'],
        'documents' => ['application/pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Quality
    |--------------------------------------------------------------------------
    |
    | Resim kalitesi (1-100).
    |
    */

    'quality' => env('IMAGE_UPLOAD_QUALITY', 90),

    /*
    |--------------------------------------------------------------------------
    | Resize Configuration
    |--------------------------------------------------------------------------
    |
    | Otomatik resize ayarları.
    |
    */

    'resize' => [
        'enabled' => env('IMAGE_UPLOAD_RESIZE_ENABLED', false),
        'width' => env('IMAGE_UPLOAD_RESIZE_WIDTH', 800),
        'height' => env('IMAGE_UPLOAD_RESIZE_HEIGHT', 800),
        'maintain_aspect_ratio' => env('IMAGE_UPLOAD_MAINTAIN_ASPECT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Configuration
    |--------------------------------------------------------------------------
    |
    | Thumbnail oluşturma ayarları.
    |
    */

    'thumbnail' => [
        'enabled' => env('IMAGE_UPLOAD_THUMBNAIL_ENABLED', false),
        'width' => env('IMAGE_UPLOAD_THUMBNAIL_WIDTH', 200),
        'height' => env('IMAGE_UPLOAD_THUMBNAIL_HEIGHT', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | S3 Configuration
    |--------------------------------------------------------------------------
    |
    | AWS S3 yapılandırması (config/filesystems.php'de tanımlı olmalı).
    |
    */

    's3' => [
        'bucket' => env('AWS_BUCKET'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'visibility' => env('AWS_VISIBILITY', 'public'),
    ],
];
