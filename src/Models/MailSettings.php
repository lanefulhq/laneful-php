<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Request-level mail settings (sandbox mode, return message IDs).
 */
final class MailSettings implements JsonSerializable
{
    public function __construct(
        public readonly ?bool $sandboxMode = null,
        public readonly ?bool $returnMessageIds = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sandboxMode: $data['sandbox_mode'] ?? null,
            returnMessageIds: $data['return_message_ids'] ?? null
        );
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->sandboxMode !== null) {
            $data['sandbox_mode'] = $this->sandboxMode;
        }

        if ($this->returnMessageIds !== null) {
            $data['return_message_ids'] = $this->returnMessageIds;
        }

        return $data;
    }

    /**
     * @return array<string, bool>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
