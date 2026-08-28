<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(
        private readonly array $errors,
        string $message = 'Невалидни данни.'
    ) {
        parent::__construct($message);
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
