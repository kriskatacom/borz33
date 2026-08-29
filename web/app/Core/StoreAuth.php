<?php

declare(strict_types=1);

namespace Store\Core;

use App\Core\Auth;
use App\Services\Auth\TokenService;

class StoreAuth
{
    public const TOKEN_COOKIE = 'borz33_store_token';
    public const DEVICE_COOKIE = 'borz33_store_device';

    public static function boot(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => self::secure(),
            ]);
        }

        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        self::ensureDeviceCookie();
        self::hydrate();
    }

    public static function csrf(): string
    {
        return (string) $_SESSION['csrf'];
    }

    public static function checkCsrf(string $token): bool
    {
        return $token !== '' && hash_equals(self::csrf(), $token);
    }

    public static function deviceUuid(): string
    {
        return (string) ($_COOKIE[self::DEVICE_COOKIE] ?? '');
    }

    public static function deviceName(): string
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Браузър';

        return substr($agent, 0, 255);
    }

    public static function persistToken(string $token, ?string $expiresAt): void
    {
        $expires = $expiresAt !== null ? strtotime($expiresAt) : false;

        self::writeCookie(self::TOKEN_COOKIE, $token, is_int($expires) ? $expires : time() + 60 * 60 * 24 * 30, true);
        $_COOKIE[self::TOKEN_COOKIE] = $token;
    }

    public static function clearToken(): void
    {
        self::writeCookie(self::TOKEN_COOKIE, '', time() - 3600, true);
        unset($_COOKIE[self::TOKEN_COOKIE]);
    }

    private static function hydrate(): void
    {
        $plain = $_COOKIE[self::TOKEN_COOKIE] ?? null;

        if (!is_string($plain) || $plain === '') {
            return;
        }

        $tokens = new TokenService();
        $token = $tokens->findValid($plain);

        if ($token === null || $token->user === null || !$token->user->isActive()) {
            self::clearToken();

            return;
        }

        $tokens->touch($token);
        Auth::set($token->user, $token);
    }

    private static function ensureDeviceCookie(): void
    {
        $current = $_COOKIE[self::DEVICE_COOKIE] ?? '';

        if (is_string($current) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $current) === 1) {
            return;
        }

        $uuid = self::uuid();
        self::writeCookie(self::DEVICE_COOKIE, $uuid, time() + 60 * 60 * 24 * 400, false);
        $_COOKIE[self::DEVICE_COOKIE] = $uuid;
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function writeCookie(string $name, string $value, int $expires, bool $httpOnly): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => self::secure(),
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);
    }

    private static function secure(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        return $https !== '' && $https !== 'off';
    }
}
