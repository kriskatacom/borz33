<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Request;
use App\Exceptions\AuthException;
use App\Services\Auth\TokenService;

class Authenticate implements MiddlewareInterface
{
    public function __construct(
        private readonly TokenService $tokenService = new TokenService()
    ) {
    }

    public function handle(): void
    {
        $plain = Request::bearerToken();

        if ($plain === null) {
            throw new AuthException('Необходима е автентикация.', 401);
        }

        $token = $this->tokenService->findValid($plain);

        if ($token === null || $token->user === null) {
            throw new AuthException('Сесията е невалидна или изтекла.', 401);
        }

        $user = $token->user;

        if (!$user->isActive()) {
            throw new AuthException('Профилът е деактивиран.', 403);
        }

        $this->tokenService->touch($token);
        Auth::set($user, $token);
    }
}
