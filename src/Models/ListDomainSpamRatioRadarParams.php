<?php

declare(strict_types=1);

namespace Laneful\Models;

/**
 * Query parameters for listing domain spam-ratio radar entries.
 *
 * Dates are UTC calendar days in YYYY-MM-DD format.
 */
final class ListDomainSpamRatioRadarParams
{
    /**
     * @param int[] $workspaceIds
     */
    public function __construct(
        public readonly array $workspaceIds = [],
        public readonly ?string $domain = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $cursor = null,
        public readonly ?int $limit = null
    ) {
    }

    /**
     * @return array<string, array<int, int>|int|string>
     */
    public function toQuery(): array
    {
        $query = [];

        if ($this->workspaceIds !== []) {
            $query['workspace_ids'] = $this->workspaceIds;
        }

        if ($this->domain !== null && $this->domain !== '') {
            $query['domain'] = $this->domain;
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
