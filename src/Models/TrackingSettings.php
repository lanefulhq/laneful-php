<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Controls email tracking and unsubscribe settings.
 */
final class TrackingSettings implements JsonSerializable
{
    public function __construct(
        public readonly ?bool $opens = null,
        public readonly ?bool $clicks = null,
        public readonly ?bool $unsubscribes = null,
        public readonly ?int $unsubscribeGroupId = null,
        public readonly ?string $unsubscribeGroupName = null
    ) {
    }

    /**
     * Create TrackingSettings from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            opens: $data['opens'] ?? null,
            clicks: $data['clicks'] ?? null,
            unsubscribes: $data['unsubscribes'] ?? null,
            unsubscribeGroupId: $data['unsubscribe_group_id'] ?? null,
            unsubscribeGroupName: $data['unsubscribe_group_name'] ?? null
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, bool|int|string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->opens !== null) {
            $data['opens'] = $this->opens;
        }

        if ($this->clicks !== null) {
            $data['clicks'] = $this->clicks;
        }

        if ($this->unsubscribes !== null) {
            $data['unsubscribes'] = $this->unsubscribes;
        }

        if ($this->unsubscribeGroupId !== null) {
            $data['unsubscribe_group_id'] = $this->unsubscribeGroupId;
        }

        if ($this->unsubscribeGroupName !== null) {
            $data['unsubscribe_group_name'] = $this->unsubscribeGroupName;
        }

        return $data;
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
