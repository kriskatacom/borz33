<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class AuthException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 400
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
