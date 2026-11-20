<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $status,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }
}
