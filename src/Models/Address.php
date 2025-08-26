<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Represents an email address with an optional name.
 */
final class Address implements JsonSerializable
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $name = null
    ) {
        if (empty($this->email)) {
            throw new \InvalidArgumentException('Email address cannot be empty');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address format');
        }
    }

    /**
     * Create an Address from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            name: $data['name'] ?? null
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = ['email' => $this->email];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
