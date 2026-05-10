<?php

namespace App\Exceptions\Auth;

use RuntimeException;

class InvalidRefreshTokenException extends RuntimeException
{
    public function __construct(
        private readonly string $reason = 'invalid',
        string $message = 'Refresh token is invalid.',
    ) {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
