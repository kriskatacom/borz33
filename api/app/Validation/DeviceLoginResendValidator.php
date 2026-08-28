<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;

class DeviceLoginResendValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, [
            'email' => ['required', 'string', 'email', 'max:255'],
            'device_uuid' => ['required', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ], [], [
            'email' => 'имейл',
            'device_uuid' => 'устройство',
            'device_name' => 'име на устройство',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
