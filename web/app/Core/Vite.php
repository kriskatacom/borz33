<?php

declare(strict_types=1);

namespace Store\Core;

class Vite
{
    public static function origin(): ?string
    {
        $raw = getenv('STORE_VITE_DEV_URL');

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $origin = rtrim($raw, '/');

        if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin) !== 1) {
            return null;
        }

        return $origin;
    }
}
