<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Paginated list of Google Postmaster spam reports.
 */
final class ListGooglePostmasterSpamReportsResponse implements JsonSerializable
{
    /**
     * @param GooglePostmasterSpamReport[] $spamReports
     */
    public function __construct(
        public readonly array $spamReports,
        public readonly ?string $nextCursor = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $reports = array_map(
            static fn(array $report) => GooglePostmasterSpamReport::fromArray($report),
            $data['spam_reports'] ?? []
        );

        return new self(
            spamReports: $reports,
            nextCursor: $data['next_cursor'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'spam_reports' => array_map(
                static fn(GooglePostmasterSpamReport $report) => $report->toArray(),
                $this->spamReports
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
