<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * An unsubscribe group in a workspace.
 */
final class UnsubscribeGroup implements JsonSerializable
{
    public function __construct(
        public readonly int $unsubscribeGroupId,
        public readonly string $name,
        public readonly int $createdAt
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            unsubscribeGroupId: (int) ($data['unsubscribe_group_id'] ?? 0),
            name: $data['name'] ?? '',
            createdAt: (int) ($data['created_at'] ?? 0)
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'unsubscribe_group_id' => $this->unsubscribeGroupId,
            'name' => $this->name,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
