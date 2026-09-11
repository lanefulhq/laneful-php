<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Paginated list of unsubscribe groups.
 */
final class ListUnsubscribeGroupsResponse implements JsonSerializable
{
    /**
     * @param UnsubscribeGroup[] $unsubscribeGroups
     */
    public function __construct(
        public readonly array $unsubscribeGroups,
        public readonly ?string $nextCursor = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $groups = array_map(
            static fn(array $group) => UnsubscribeGroup::fromArray($group),
            $data['unsubscribe_groups'] ?? []
        );

        return new self(
            unsubscribeGroups: $groups,
            nextCursor: $data['next_cursor'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'unsubscribe_groups' => array_map(
                static fn(UnsubscribeGroup $group) => $group->toArray(),
                $this->unsubscribeGroups
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
