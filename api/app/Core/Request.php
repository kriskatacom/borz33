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

    public static function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        if (!is_array($file) || !isset($file['tmp_name']) || is_array($file['tmp_name'])) {
            return null;
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /** @return list<array<string, mixed>> */
    public static function files(string $key): array
    {
        $file = $_FILES[$key] ?? null;

        if (!is_array($file) || !isset($file['tmp_name'])) {
            $single = self::file($key);

            return $single === null ? [] : [$single];
        }

        if (!is_array($file['tmp_name'])) {
            $single = self::file($key);

            return $single === null ? [] : [$single];
        }

        $items = [];
        $count = count($file['tmp_name']);

        for ($index = 0; $index < $count; $index++) {
            $error = (int) ($file['error'][$index] ?? UPLOAD_ERR_NO_FILE);

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $items[] = [
                'name' => $file['name'][$index] ?? 'image',
                'type' => $file['type'][$index] ?? '',
                'tmp_name' => $file['tmp_name'][$index],
                'error' => $error,
                'size' => $file['size'][$index] ?? 0,
            ];
        }

        return $items;
    }

    public static function wantsTrue(string $key): bool
    {
        $value = self::query($key, self::input($key));

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';

        if (!is_string($header) || $header === '') {
            return null;
        }

        if (!preg_match('/^Bearer\s+(\S+)/i', $header, $matches)) {
            return null;
        }

        return $matches[1];
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
