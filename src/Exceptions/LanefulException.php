<?php

declare(strict_types=1);

namespace Laneful\Exceptions;

use Exception;

/**
 * Base exception class for all Laneful SDK exceptions.
 */
class LanefulException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
