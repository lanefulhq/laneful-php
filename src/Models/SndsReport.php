<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * A daily Microsoft SNDS report for a sending IP.
 *
 * Filter result is one of GREEN, YELLOW, RED, or empty when unknown.
 */
final class SndsReport implements JsonSerializable
{
    public const FILTER_UNKNOWN = '';
    public const FILTER_GREEN = 'GREEN';
    public const FILTER_YELLOW = 'YELLOW';
    public const FILTER_RED = 'RED';

    public function __construct(
        public readonly string $ip,
        public readonly string $date,
        public readonly int $rcptCommands,
        public readonly int $dataCommands,
        public readonly int $messageRecipients,
        public readonly string $filterResult,
        public readonly float $complaintRate,
        public readonly int $trapHits
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ip: $data['ip'] ?? '',
            date: (string) ($data['date'] ?? ''),
            rcptCommands: (int) ($data['rcpt_commands'] ?? 0),
            dataCommands: (int) ($data['data_commands'] ?? 0),
            messageRecipients: (int) ($data['message_recipients'] ?? 0),
            filterResult: $data['filter_result'] ?? self::FILTER_UNKNOWN,
            complaintRate: (float) ($data['complaint_rate'] ?? 0),
            trapHits: (int) ($data['trap_hits'] ?? 0)
        );
    }

    /**
     * @return array<string, float|int|string>
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'date' => $this->date,
            'rcpt_commands' => $this->rcptCommands,
            'data_commands' => $this->dataCommands,
            'message_recipients' => $this->messageRecipients,
            'filter_result' => $this->filterResult,
            'complaint_rate' => $this->complaintRate,
            'trap_hits' => $this->trapHits,
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
