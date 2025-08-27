<?php

declare(strict_types=1);

namespace Laneful\Exceptions;

/**
 * Exception thrown when the API returns an error response.
 */
class ApiException extends LanefulException
{
    public function __construct(
        string $message,
        int $httpStatusCode = 0,
        ?string $apiError = null,
        ?\Exception $previous = null
    ) {
        $fullMessage = $message;
        if ($apiError !== null) {
            $fullMessage .= ": {$apiError}";
        }

        parent::__construct($fullMessage, $httpStatusCode, $previous);
    }
}
