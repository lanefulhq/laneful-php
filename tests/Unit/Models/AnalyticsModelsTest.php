<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use Laneful\Models\DomainSpamRatioRadar;
use Laneful\Models\GooglePostmasterSpamReport;
use Laneful\Models\ListDomainSpamRatioRadarParams;
use Laneful\Models\ListDomainSpamRatioRadarResponse;
use Laneful\Models\ListGooglePostmasterSpamReportsParams;
use Laneful\Models\ListGooglePostmasterSpamReportsResponse;
use Laneful\Models\ListSndsReportsParams;
use Laneful\Models\ListSndsReportsResponse;
use Laneful\Models\SndsReport;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\DomainSpamRatioRadar
 * @covers \Laneful\Models\GooglePostmasterSpamReport
 * @covers \Laneful\Models\SndsReport
 * @covers \Laneful\Models\ListDomainSpamRatioRadarParams
 * @covers \Laneful\Models\ListDomainSpamRatioRadarResponse
 * @covers \Laneful\Models\ListGooglePostmasterSpamReportsParams
 * @covers \Laneful\Models\ListGooglePostmasterSpamReportsResponse
 * @covers \Laneful\Models\ListSndsReportsParams
 * @covers \Laneful\Models\ListSndsReportsResponse
 */
class AnalyticsModelsTest extends TestCase
{
    public function testDomainSpamRatioRadar(): void
    {
        $entry = DomainSpamRatioRadar::fromArray([
            'workspace_id' => 1,
            'domain' => 'example.com',
            'esp' => 'Gmail',
            'spam_ratio' => 0.25,
            'date' => '2026-09-01',
        ]);

        $this->assertSame(1, $entry->workspaceId);
        $this->assertSame('Gmail', $entry->esp);
        $this->assertSame(0.25, $entry->spamRatio);

        $params = new ListDomainSpamRatioRadarParams(
            workspaceIds: [1, 2],
            domain: 'example.com',
            startDate: '2026-09-01',
            endDate: '2026-09-08',
            cursor: 'c1',
            limit: 50
        );
        $this->assertSame(
            [
                'workspace_ids' => [1, 2],
                'domain' => 'example.com',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-08',
                'cursor' => 'c1',
                'limit' => 50,
            ],
            $params->toQuery()
        );

        $response = ListDomainSpamRatioRadarResponse::fromArray([
            'radar' => [$entry->toArray()],
            'next_cursor' => 'next',
        ]);
        $this->assertCount(1, $response->radar);
        $this->assertSame('next', $response->nextCursor);
    }

    public function testGooglePostmasterSpamReport(): void
    {
        $report = GooglePostmasterSpamReport::fromArray([
            'workspace_id' => 3,
            'domain' => 'example.com',
            'date' => '2026-09-01',
            'spam_ratio' => 0.05,
        ]);

        $this->assertSame(3, $report->workspaceId);
        $this->assertSame(0.05, $report->spamRatio);
        $this->assertSame($report->toArray(), $report->jsonSerialize());

        $params = new ListGooglePostmasterSpamReportsParams(domain: 'example.com');
        $this->assertSame(['domain' => 'example.com'], $params->toQuery());

        $response = ListGooglePostmasterSpamReportsResponse::fromArray([
            'spam_reports' => [$report->toArray()],
        ]);
        $this->assertCount(1, $response->spamReports);
        $this->assertNull($response->nextCursor);
    }

    public function testSndsReport(): void
    {
        $report = SndsReport::fromArray([
            'ip' => '203.0.113.5',
            'date' => '2026-09-01',
            'rcpt_commands' => 10,
            'data_commands' => 8,
            'message_recipients' => 20,
            'filter_result' => SndsReport::FILTER_GREEN,
            'complaint_rate' => 0.1,
            'trap_hits' => 2,
        ]);

        $this->assertSame('203.0.113.5', $report->ip);
        $this->assertSame(SndsReport::FILTER_GREEN, $report->filterResult);
        $this->assertSame(2, $report->trapHits);

        $params = new ListSndsReportsParams(ip: '203.0.113.5', limit: 10);
        $this->assertSame(['ip' => '203.0.113.5', 'limit' => 10], $params->toQuery());

        $response = ListSndsReportsResponse::fromArray([
            'snds_reports' => [$report->toArray()],
            'next_cursor' => 'n1',
        ]);
        $this->assertCount(1, $response->sndsReports);
        $this->assertSame('n1', $response->nextCursor);
    }
}
