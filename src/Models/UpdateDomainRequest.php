<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Request body for updating a domain's mutable settings.
 *
 * Pass a track ID to set the email track, an empty string to clear it
 * (the domain falls back to the default track), or null to leave it unchanged.
 */
final class UpdateDomainRequest implements JsonSerializable
{
    public function __construct(
        public readonly ?string $emailTrackId = null
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->emailTrackId !== null) {
            $data['email_track_id'] = $this->emailTrackId;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
