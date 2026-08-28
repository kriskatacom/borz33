<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;

class VerifyEmailValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, $this->rules(), [], $this->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'email' => 'имейл',
            'code' => 'код',
        ];
    }
}
