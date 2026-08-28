<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Services\Auth\AdminBootstrapService;
use Illuminate\Validation\Rule;

class RegisterValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, $this->rules(), $this->messages(), $this->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
                Rule::notIn([AdminBootstrapService::configuredEmail()]),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:32'],
            'device_uuid' => ['required', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'email.not_in' => 'Този имейл е резервиран за администратор.',
        ];
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
            'device_uuid' => 'устройство',
            'device_name' => 'име на устройство',
        ];
    }
}
