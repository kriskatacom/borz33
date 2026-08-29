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

        $configured = rtrim($raw, '/');

        if (!self::isAllowedDevOrigin($configured)) {
            return null;
        }

        $host = self::requestHostname();

        if ($host === null) {
            return $configured;
        }

        $scheme = parse_url($configured, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($configured, PHP_URL_PORT);

        if (!is_int($port)) {
            $port = $scheme === 'https' ? 443 : 80;
        }

        $rewritten = $scheme . '://' . self::hostForUrl($host) . ':' . $port;

        if (!self::isAllowedDevOrigin($rewritten)) {
            return $configured;
        }

        return $rewritten;
    }

    private static function requestHostname(): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if (!is_string($host) || $host === '') {
            return null;
        }

        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');

            return $end === false ? null : substr($host, 1, $end - 1);
        }

        $colon = strrpos($host, ':');

        if ($colon === false) {
            return $host;
        }

        return substr($host, 0, $colon);
    }

    private static function hostForUrl(string $host): string
    {
        if (str_contains($host, ':') && !str_starts_with($host, '[')) {
            return '[' . $host . ']';
        }

        return $host;
    }

    private static function isAllowedDevOrigin(string $origin): bool
    {
        $parts = parse_url($origin);

        if (!is_array($parts)) {
            return false;
        }

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';

        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        if ($host === 'localhost') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
