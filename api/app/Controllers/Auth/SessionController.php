<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Resources\UserResource;
use App\Services\Auth\TokenService;

class SessionController extends Controller
{
    public function __construct(
        private readonly TokenService $tokenService = new TokenService()
    ) {
    }

    public function show(): never
    {
        $user = Auth::requireUser();

        $this->ok([
            'user' => UserResource::toArray($user),
        ]);
    }

    public function destroy(): never
    {
        $this->tokenService->revoke(Auth::token());
        Auth::set(null);

        $this->ok([], 'Излязохте от профила.');
    }
}
