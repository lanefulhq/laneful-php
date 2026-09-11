<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use Laneful\Models\ListUnsubscribeGroupsParams;
use Laneful\Models\ListUnsubscribeGroupsResponse;
use Laneful\Models\UnsubscribeGroup;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\UnsubscribeGroup
 * @covers \Laneful\Models\ListUnsubscribeGroupsParams
 * @covers \Laneful\Models\ListUnsubscribeGroupsResponse
 */
class UnsubscribeGroupTest extends TestCase
{
    public function testFromAndToArray(): void
    {
        $data = [
            'unsubscribe_group_id' => 12,
            'name' => 'Newsletters',
            'created_at' => 1710000000,
        ];

        $group = UnsubscribeGroup::fromArray($data);

        $this->assertSame(12, $group->unsubscribeGroupId);
        $this->assertSame('Newsletters', $group->name);
        $this->assertSame(1710000000, $group->createdAt);
        $this->assertSame($data, $group->toArray());
        $this->assertSame($data, $group->jsonSerialize());
    }

    public function testListParamsAndResponse(): void
    {
        $params = new ListUnsubscribeGroupsParams(cursor: 'c1', limit: 25, search: 'news');
        $this->assertSame(
            ['cursor' => 'c1', 'limit' => 25, 'search' => 'news'],
            $params->toQuery()
        );

        $response = ListUnsubscribeGroupsResponse::fromArray([
            'unsubscribe_groups' => [
                ['unsubscribe_group_id' => 1, 'name' => 'A', 'created_at' => 1],
            ],
            'next_cursor' => 'c2',
        ]);

        $this->assertCount(1, $response->unsubscribeGroups);
        $this->assertSame('A', $response->unsubscribeGroups[0]->name);
        $this->assertSame('c2', $response->nextCursor);
    }
}
