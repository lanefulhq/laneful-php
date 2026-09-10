<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Paginated list of Microsoft SNDS reports.
 */
final class ListSndsReportsResponse implements JsonSerializable
{
    /**
     * @param SndsReport[] $sndsReports
     */
    public function __construct(
        public readonly array $sndsReports,
        public readonly ?string $nextCursor = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $reports = array_map(
            static fn(array $report) => SndsReport::fromArray($report),
            $data['snds_reports'] ?? []
        );

        return new self(
            sndsReports: $reports,
            nextCursor: $data['next_cursor'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'snds_reports' => array_map(
                static fn(SndsReport $report) => $report->toArray(),
                $this->sndsReports
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
