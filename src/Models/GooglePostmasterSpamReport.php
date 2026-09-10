<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * A daily Gmail spam-rate report from Google Postmaster Tools.
 */
final class GooglePostmasterSpamReport implements JsonSerializable
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly string $domain,
        public readonly string $date,
        public readonly float $spamRatio
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
            date: (string) ($data['date'] ?? ''),
            spamRatio: (float) ($data['spam_ratio'] ?? 0)
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
            'date' => $this->date,
            'spam_ratio' => $this->spamRatio,
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
