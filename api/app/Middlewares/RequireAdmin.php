<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Auth;
use App\Exceptions\AuthException;

class RequireAdmin implements MiddlewareInterface
{
    public function handle(): void
    {
        $user = Auth::user();

        if ($user === null || !$user->isAdmin()) {
            throw new AuthException('Нямате достъп до този ресурс.', 403);
        }
    }
}
