<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Request body for creating a sending domain.
 */
final class CreateDomainRequest implements JsonSerializable
{
    public function __construct(
        public readonly string $domain,
        public readonly string $tracking,
        public readonly string $returnPath,
        public readonly ?bool $requireTls = null,
        public readonly ?string $emailTrackId = null
    ) {
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        $data = [
            'domain' => $this->domain,
            'tracking' => $this->tracking,
            'return_path' => $this->returnPath,
        ];

        if ($this->requireTls !== null) {
            $data['require_tls'] = $this->requireTls;
        }

        if ($this->emailTrackId !== null && $this->emailTrackId !== '') {
            $data['email_track_id'] = $this->emailTrackId;
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
