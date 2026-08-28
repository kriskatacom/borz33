<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AuthException;
use App\Models\ApiToken;
use App\Models\User;

final class Auth
{
    private static ?User $user = null;

    private static ?ApiToken $token = null;

    public static function set(?User $user, ?ApiToken $token = null): void
    {
        self::$user = $user;
        self::$token = $token;
    }

    public static function user(): ?User
    {
        return self::$user;
    }

    public static function token(): ?ApiToken
    {
        return self::$token;
    }

    public static function requireUser(): User
    {
        if (self::$user === null) {
            throw new AuthException('Необходима е автентикация.', 401);
        }

        return self::$user;
    }
}
