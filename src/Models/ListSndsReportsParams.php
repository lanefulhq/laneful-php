<?php

declare(strict_types=1);

namespace Laneful\Models;

/**
 * Query parameters for listing Microsoft SNDS reports.
 *
 * Dates are UTC calendar days in YYYY-MM-DD format.
 */
final class ListSndsReportsParams
{
    public function __construct(
        public readonly ?string $ip = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $cursor = null,
        public readonly ?int $limit = null
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function toQuery(): array
    {
        $query = [];

        if ($this->ip !== null && $this->ip !== '') {
            $query['ip'] = $this->ip;
        }

        if ($this->startDate !== null && $this->startDate !== '') {
            $query['start_date'] = $this->startDate;
        }

        if ($this->endDate !== null && $this->endDate !== '') {
            $query['end_date'] = $this->endDate;
        }

        if ($this->cursor !== null && $this->cursor !== '') {
            $query['cursor'] = $this->cursor;
        }

        if ($this->limit !== null && $this->limit > 0) {
            $query['limit'] = $this->limit;
        }

        return $query;
    }
}
