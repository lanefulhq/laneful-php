<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;
use Laneful\Exceptions\ValidationException;

/**
 * Request-level mail settings (sandbox mode, return message IDs, idempotency key).
 *
 * idempotencyKey protects against duplicate sends: Laneful accepts a key once per organization
 * for one hour. A repeated request with the same key is answered with status "accepted" (and no
 * message IDs) and sends nothing. Keys are 1 to 36 bytes.
 */
final class MailSettings implements JsonSerializable
{
    /**
     * @throws ValidationException If idempotencyKey is set and not 1 to 36 bytes long
     */
    public function __construct(
        public readonly ?bool $sandboxMode = null,
        public readonly ?bool $returnMessageIds = null,
        public readonly ?string $idempotencyKey = null
    ) {
        if ($idempotencyKey !== null && (strlen($idempotencyKey) < 1 || strlen($idempotencyKey) > 36)) {
            throw new ValidationException('idempotencyKey must contain between 1 and 36 bytes');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sandboxMode: $data['sandbox_mode'] ?? null,
            returnMessageIds: $data['return_message_ids'] ?? null,
            idempotencyKey: $data['idempotency_key'] ?? null
        );
    }

    /**
     * @return array<string, bool|string>
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

        if ($this->idempotencyKey !== null) {
            $data['idempotency_key'] = $this->idempotencyKey;
        }

        return $data;
    }

    /**
     * @return array<string, bool|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
