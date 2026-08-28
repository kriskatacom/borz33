<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreUserValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_CUSTOMER])],
            'is_active' => ['required', 'boolean'],
        ], [], $this->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'first_name' => 'име',
            'last_name' => 'фамилия',
            'email' => 'имейл',
            'password' => 'парола',
            'password_confirmation' => 'потвърждение на паролата',
            'phone' => 'телефон',
            'role' => 'роля',
            'is_active' => 'активен',
        ];
    }
}
