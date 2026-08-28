<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return rtrim($path, '/') ?: '/';
    }

    public static function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    public static function json(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    public static function input(?string $key = null, mixed $default = null): mixed
    {
        $data = array_merge($_POST, self::json());

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    public static function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return substr($ip, 0, 45);
    }

    public static function userAgent(): ?string
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($agent === null || $agent === '') {
            return null;
        }

        return substr($agent, 0, 512);
    }
}
