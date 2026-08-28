<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\UserResource;
use App\Services\Auth\RegisterService;
use App\Validation\RegisterValidator;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterValidator $registerValidator = new RegisterValidator(),
        private readonly RegisterService $registerService = new RegisterService()
    ) {
    }

    public function store(): never
    {
        $payload = $this->registerValidator->validate(Request::input());
        $user = $this->registerService->register($payload);

        $this->json([
            'success' => true,
            'message' => 'Регистрацията е успешна. Изпратихме код за потвърждение на имейла Ви.',
            'data' => UserResource::toArray($user),
        ], 201);
    }
}
