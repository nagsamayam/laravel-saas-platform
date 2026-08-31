<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ApplicationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
