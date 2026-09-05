<?php

declare(strict_types=1);

namespace App\Resources;

use App\Services\Storage\ObjectStorage;

final class StorageUrl
{
    public static function forPath(?string $path): ?string
    {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        return (new ObjectStorage())->publicUrl($path);
    }
}
