<?php

declare(strict_types=1);

return [
    'disk' => strtolower(trim((string) (getenv('FILESYSTEM_DISK') ?: 'local'))),
    'r2' => [
        'endpoint' => rtrim(trim((string) (getenv('R2_ENDPOINT') ?: '')), '/'),
        'region' => trim((string) (getenv('R2_REGION') ?: 'auto')),
        'bucket' => trim((string) (getenv('R2_BUCKET') ?: '')),
        'key' => trim((string) (getenv('R2_ACCESS_KEY_ID') ?: '')),
        'secret' => trim((string) (getenv('R2_SECRET_ACCESS_KEY') ?: '')),
        'public_url' => rtrim(trim((string) (getenv('R2_PUBLIC_URL') ?: '')), '/'),
    ],
];
