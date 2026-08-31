<?php

declare(strict_types=1);

namespace App\Core;

final class Cors
{
    public static function handle(): void
    {
        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        $allowed = array_values(array_filter(array_map(
            static fn (string $value): string => rtrim(trim($value), '/'),
            explode(',', (string) (getenv('CORS_ALLOWED_ORIGINS') ?: ''))
        )));

        if ($origin !== '' && in_array(rtrim($origin, '/'), $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Accept, Authorization, Content-Type, X-Requested-With');
            header('Access-Control-Expose-Headers: Content-Disposition');
            header('Access-Control-Max-Age: 600');
            header('Vary: Origin');
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
            http_response_code($origin !== '' && in_array(rtrim($origin, '/'), $allowed, true) ? 204 : 403);
            exit;
        }
    }
}
