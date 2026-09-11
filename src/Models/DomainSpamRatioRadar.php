<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * A sending domain whose spam complaint ratio reached a critical level
 * at a mailbox provider on a given day.
 */
final class DomainSpamRatioRadar implements JsonSerializable
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly string $domain,
        public readonly string $esp,
        public readonly float $spamRatio,
        public readonly string $date
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            workspaceId: (int) ($data['workspace_id'] ?? 0),
            domain: $data['domain'] ?? '',
            esp: $data['esp'] ?? '',
            spamRatio: (float) ($data['spam_ratio'] ?? 0),
            date: (string) ($data['date'] ?? '')
        );
    }

    /**
     * @return array<string, float|int|string>
     */
    public function toArray(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'domain' => $this->domain,
            'esp' => $this->esp,
            'spam_ratio' => $this->spamRatio,
            'date' => $this->date,
        ];
    }

    /**
     * @return array<string, float|int|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
