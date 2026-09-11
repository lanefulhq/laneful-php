<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Paginated list of sending domains.
 */
final class ListDomainsResponse implements JsonSerializable
{
    /**
     * @param Domain[] $domains
     */
    public function __construct(
        public readonly array $domains,
        public readonly ?string $nextCursor = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $domains = array_map(
            static fn(array $domain) => Domain::fromArray($domain),
            $data['domains'] ?? []
        );

        $pagination = $data['pagination'] ?? [];
        $nextCursor = is_array($pagination) ? ($pagination['next_cursor'] ?? null) : null;

        return new self(
            domains: $domains,
            nextCursor: $nextCursor
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'domains' => array_map(
                static fn(Domain $domain) => $domain->toArray(),
                $this->domains
            ),
        ];

        if ($this->nextCursor !== null) {
            $data['pagination'] = ['next_cursor' => $this->nextCursor];
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
