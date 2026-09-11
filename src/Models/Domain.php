<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * A sending domain and its verification state.
 */
final class Domain implements JsonSerializable
{
    public function __construct(
        public readonly string $domain,
        public readonly string $tracking = '',
        public readonly string $returnPath = '',
        public readonly bool $verified = false,
        public readonly bool $trackingVerified = false,
        public readonly bool $returnPathVerified = false,
        public readonly bool $dkim1Verified = false,
        public readonly bool $dkim2Verified = false,
        public readonly bool $dmarcVerified = false,
        public readonly bool $requireTls = false,
        public readonly string $emailTrackId = ''
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            domain: $data['domain'] ?? '',
            tracking: $data['tracking'] ?? '',
            returnPath: $data['return_path'] ?? '',
            verified: (bool) ($data['verified'] ?? false),
            trackingVerified: (bool) ($data['tracking_verified'] ?? false),
            returnPathVerified: (bool) ($data['return_path_verified'] ?? false),
            dkim1Verified: (bool) ($data['dkim1_verified'] ?? false),
            dkim2Verified: (bool) ($data['dkim2_verified'] ?? false),
            dmarcVerified: (bool) ($data['dmarc_verified'] ?? false),
            requireTls: (bool) ($data['require_tls'] ?? false),
            emailTrackId: $data['email_track_id'] ?? ''
        );
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'tracking' => $this->tracking,
            'return_path' => $this->returnPath,
            'verified' => $this->verified,
            'tracking_verified' => $this->trackingVerified,
            'return_path_verified' => $this->returnPathVerified,
            'dkim1_verified' => $this->dkim1Verified,
            'dkim2_verified' => $this->dkim2Verified,
            'dmarc_verified' => $this->dmarcVerified,
            'require_tls' => $this->requireTls,
            'email_track_id' => $this->emailTrackId,
        ];
    }

    /**
     * @return array<string, bool|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
