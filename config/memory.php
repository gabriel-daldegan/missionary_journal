<?php

return [
    'media' => [
        'disk' => env('MEMORY_MEDIA_DISK', 'local'),
        'collection' => 'photos',
        'path' => 'memory-records',
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ],
        'max_image_size_kilobytes' => 10 * 1024,
        'max_photos_per_record' => 25,
        'workspace_storage_cap_bytes' => 2 * 1024 * 1024 * 1024,
    ],
];
