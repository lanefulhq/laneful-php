<?php

declare(strict_types=1);

namespace Laneful\Models;

/**
 * Query parameters for listing domains.
 */
final class ListDomainsParams
{
    public function __construct(
        public readonly ?string $cursor = null,
        public readonly ?int $limit = null,
        public readonly ?string $filterDomain = null
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function toQuery(): array
    {
        $query = [];

        if ($this->cursor !== null && $this->cursor !== '') {
            $query['cursor'] = $this->cursor;
        }

        if ($this->limit !== null && $this->limit > 0) {
            $query['limit'] = $this->limit;
        }

        if ($this->filterDomain !== null && $this->filterDomain !== '') {
            $query['filter[domain]'] = $this->filterDomain;
        }

        return $query;
    }
}
