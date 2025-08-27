<?php

declare(strict_types=1);

namespace Laneful\Exceptions;

/**
 * Exception thrown when HTTP communication fails.
 */
class HttpException extends LanefulException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
