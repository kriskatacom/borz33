<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use Illuminate\Validation\Rule;

class UpdateAccountProfileValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, int $userId): array
    {
        $validator = ValidatorFactory::make()->make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'current_password' => ['nullable', 'string'],
        ], [], [
            'first_name' => 'име',
            'last_name' => 'фамилия',
            'email' => 'имейл',
            'phone' => 'телефон',
            'current_password' => 'текуща парола',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
