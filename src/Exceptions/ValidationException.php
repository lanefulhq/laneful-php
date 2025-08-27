<?php

declare(strict_types=1);

namespace Laneful\Exceptions;

/**
 * Exception thrown when data validation fails.
 */
class ValidationException extends LanefulException
{
    public function __construct(
        string $message,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
