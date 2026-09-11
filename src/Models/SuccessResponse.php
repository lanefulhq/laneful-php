<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Generic success message returned by mutating endpoints that do not
 * return a resource body (for example, delete).
 */
final class SuccessResponse implements JsonSerializable
{
    public function __construct(
        public readonly string $message
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'] ?? ''
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return ['message' => $this->message];
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
