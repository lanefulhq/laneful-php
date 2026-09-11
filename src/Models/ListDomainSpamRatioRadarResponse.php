<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Paginated list of domain spam-ratio radar entries.
 */
final class ListDomainSpamRatioRadarResponse implements JsonSerializable
{
    /**
     * @param DomainSpamRatioRadar[] $radar
     */
    public function __construct(
        public readonly array $radar,
        public readonly ?string $nextCursor = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $radar = array_map(
            static fn(array $entry) => DomainSpamRatioRadar::fromArray($entry),
            $data['radar'] ?? []
        );

        return new self(
            radar: $radar,
            nextCursor: $data['next_cursor'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'radar' => array_map(
                static fn(DomainSpamRatioRadar $entry) => $entry->toArray(),
                $this->radar
            ),
        ];

        if ($this->nextCursor !== null) {
            $data['next_cursor'] = $this->nextCursor;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
