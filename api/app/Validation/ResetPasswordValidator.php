<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;

class ResetPasswordValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string', 'min:32', 'max:128'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'email' => 'имейл',
            'token' => 'код',
            'password' => 'парола',
            'password_confirmation' => 'потвърждение на паролата',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
