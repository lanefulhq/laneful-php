<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use Laneful\Models\CreateDomainRequest;
use Laneful\Models\Domain;
use Laneful\Models\ListDomainsParams;
use Laneful\Models\ListDomainsResponse;
use Laneful\Models\UpdateDomainRequest;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\Domain
 * @covers \Laneful\Models\CreateDomainRequest
 * @covers \Laneful\Models\UpdateDomainRequest
 * @covers \Laneful\Models\ListDomainsParams
 * @covers \Laneful\Models\ListDomainsResponse
 */
class DomainTest extends TestCase
{
    public function testFromAndToArray(): void
    {
        $data = [
            'domain' => 'example.com',
            'tracking' => 'track',
            'return_path' => 'bounce',
            'verified' => true,
            'tracking_verified' => true,
            'return_path_verified' => false,
            'dkim1_verified' => true,
            'dkim2_verified' => true,
            'dmarc_verified' => true,
            'require_tls' => true,
            'email_track_id' => 'track-1',
        ];

        $domain = Domain::fromArray($data);

        $this->assertSame('example.com', $domain->domain);
        $this->assertTrue($domain->dmarcVerified);
        $this->assertTrue($domain->requireTls);
        $this->assertSame('track-1', $domain->emailTrackId);
        $this->assertSame($data, $domain->toArray());
        $this->assertSame($data, $domain->jsonSerialize());
    }

    public function testCreateDomainRequestOmitsEmptyOptionalFields(): void
    {
        $request = new CreateDomainRequest(
            domain: 'example.com',
            tracking: 'track',
            returnPath: 'bounce',
            requireTls: true,
            emailTrackId: 'track-1'
        );

        $this->assertSame(
            [
                'domain' => 'example.com',
                'tracking' => 'track',
                'return_path' => 'bounce',
                'require_tls' => true,
                'email_track_id' => 'track-1',
            ],
            $request->toArray()
        );

        $minimal = new CreateDomainRequest('example.com', 'track', 'bounce');
        $this->assertSame(
            [
                'domain' => 'example.com',
                'tracking' => 'track',
                'return_path' => 'bounce',
            ],
            $minimal->toArray()
        );
    }

    public function testUpdateDomainRequestThreeWayTrack(): void
    {
        $this->assertSame([], (new UpdateDomainRequest())->toArray());
        $this->assertSame(['email_track_id' => ''], (new UpdateDomainRequest(''))->toArray());
        $this->assertSame(
            ['email_track_id' => 'track-1'],
            (new UpdateDomainRequest('track-1'))->toArray()
        );
    }

    public function testListDomainsParamsAndResponse(): void
    {
        $params = new ListDomainsParams(cursor: 'abc', limit: 50, filterDomain: 'example.com');
        $this->assertSame(
            [
                'cursor' => 'abc',
                'limit' => 50,
                'filter[domain]' => 'example.com',
            ],
            $params->toQuery()
        );

        $response = ListDomainsResponse::fromArray([
            'domains' => [
                ['domain' => 'example.com', 'verified' => true],
            ],
            'pagination' => ['next_cursor' => 'next'],
        ]);

        $this->assertCount(1, $response->domains);
        $this->assertSame('example.com', $response->domains[0]->domain);
        $this->assertSame('next', $response->nextCursor);
    }
}
