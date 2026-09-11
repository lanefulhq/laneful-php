<?php

declare(strict_types=1);

namespace Laneful\Models;

/**
 * Query parameters for listing unsubscribe groups.
 */
final class ListUnsubscribeGroupsParams
{
    public function __construct(
        public readonly ?string $cursor = null,
        public readonly ?int $limit = null,
        public readonly ?string $search = null
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

        if ($this->search !== null && $this->search !== '') {
            $query['search'] = $this->search;
        }

        return $query;
    }
}
