<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laneful\Exceptions\ApiException;
use Laneful\Exceptions\HttpException;
use Laneful\Exceptions\ValidationException;
use Laneful\LanefulClient;
use Laneful\Models\Address;
use Laneful\Models\CreateDomainRequest;
use Laneful\Models\Email;
use Laneful\Models\ListDomainSpamRatioRadarParams;
use Laneful\Models\ListDomainsParams;
use Laneful\Models\ListSndsReportsParams;
use Laneful\Models\ListUnsubscribeGroupsParams;
use Laneful\Models\MailSettings;
use Laneful\Models\UpdateDomainRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \Laneful\LanefulClient
 */
class LanefulClientTest extends TestCase
{
    private HttpClient&MockObject $httpClient;
    private LanefulClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->client = new LanefulClient(
            'https://api.example.com',
            'test-token',
            $this->httpClient
        );
    }

    public function testConstructorValidatesBaseUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Base URL cannot be empty');
        
        new LanefulClient('', 'test-token');
    }

    public function testConstructorValidatesAuthToken(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Auth token cannot be empty');
        
        new LanefulClient('https://api.example.com', '');
    }

    public function testSendEmailSuccess(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test Subject',
            textContent: 'Test content'
        );

        $responseBody = '{"status": "accepted"}';
        $response = new Response(200, [], $responseBody);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/v1/email/send',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('json', $options);
                    $this->assertArrayHasKey('headers', $options);
                    $this->assertArrayHasKey('timeout', $options);
                    
                    // Check auth header
                    $this->assertSame('Bearer test-token', $options['headers']['Authorization']);
                    $this->assertSame('application/json', $options['headers']['Content-Type']);
                    $this->assertSame('application/json', $options['headers']['Accept']);
                    $this->assertStringContainsString('laneful-php', $options['headers']['User-Agent']);
                    
                    // Check request body structure
                    $this->assertArrayHasKey('emails', $options['json']);
                    $this->assertCount(1, $options['json']['emails']);
                    
                    $emailData = $options['json']['emails'][0];
                    $this->assertSame('sender@example.com', $emailData['from']['email']);
                    $this->assertSame('recipient@example.com', $emailData['to'][0]['email']);
                    $this->assertSame('Test Subject', $emailData['subject']);
                    $this->assertSame('Test content', $emailData['text_content']);
                    
                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->client->sendEmail($email);
        
        $this->assertSame(['status' => 'accepted'], $result);
    }

    public function testSendEmailsSuccess(): void
    {
        $emails = [
            new Email(
                from: new Address('sender@example.com'),
                to: [new Address('recipient1@example.com')],
                subject: 'Test 1',
                textContent: 'Content 1'
            ),
            new Email(
                from: new Address('sender@example.com'),
                to: [new Address('recipient2@example.com')],
                subject: 'Test 2',
                textContent: 'Content 2'
            )
        ];

        $responseBody = '{"status": "accepted"}';
        $response = new Response(200, [], $responseBody);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/v1/email/send',
                $this->callback(function ($options) {
                    $this->assertCount(2, $options['json']['emails']);
                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->client->sendEmails($emails);
        
        $this->assertSame(['status' => 'accepted'], $result);
    }

    public function testSendEmailsThrowsExceptionForEmptyArray(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Emails array cannot be empty');
        
        $this->client->sendEmails([]);
    }

    public function testSendEmailsThrowsExceptionForInvalidEmailInstance(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('All items in emails array must be Email instances');
        
        $this->client->sendEmails(['invalid-email']);
    }

    public function testSendEmailHandles404Error(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $response = new Response(404, [], 'Not Found');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('API endpoint not found (404). Check your base URL');
        
        $this->client->sendEmail($email);
    }

    public function testSendEmailHandlesApiError(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $responseBody = '{"error": "Invalid API key"}';
        $response = new Response(401, [], $responseBody);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('API request failed');
        
        $this->client->sendEmail($email);
    }

    public function testSendEmailHandlesInvalidJsonResponse(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $response = new Response(200, [], 'Invalid JSON');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Failed to decode JSON response');
        
        $this->client->sendEmail($email);
    }

    public function testSendEmailHandlesNetworkError(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new ConnectException('Connection failed', new Request('POST', 'test')));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('HTTP request failed');
        
        $this->client->sendEmail($email);
    }

    public function testCustomTimeout(): void
    {
        $client = new LanefulClient(
            'https://api.example.com',
            'test-token',
            null,
            60
        );

        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        // We can't easily test the timeout without mocking the HTTP client constructor
        // This test mainly ensures the constructor accepts the timeout parameter
        $this->assertInstanceOf(LanefulClient::class, $client);
    }

    public function testBuildsCorrectApiUrl(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $response = new Response(200, [], '{"status": "accepted"}');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/v1/email/send', // Verify URL construction
                $this->anything()
            )
            ->willReturn($response);

        $this->client->sendEmail($email);
    }

    public function testBuildsCorrectApiUrlWithTrailingSlash(): void
    {
        $client = new LanefulClient(
            'https://api.example.com/', // With trailing slash
            'test-token',
            $this->httpClient
        );

        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $response = new Response(200, [], '{"status": "accepted"}');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/v1/email/send', // Should normalize URL
                $this->anything()
            )
            ->willReturn($response);

        $client->sendEmail($email);
    }

    public function testHandlesLargeJsonResponse(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        // Simulate a large response that gets truncated in error messages
        $largeResponse = str_repeat('x', 600);
        $response = new Response(500, [], $largeResponse);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Failed to decode JSON response');
        
        try {
            $this->client->sendEmail($email);
        } catch (HttpException $e) {
            // Verify truncation in error message
            $this->assertStringContainsString('...', $e->getMessage());
            throw $e;
        }
    }

    public function testSendEmailWithMailSettings(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );

        $response = new Response(200, [], '{"status":"accepted","message_ids":["msg-1"]}');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.example.com/v1/email/send',
                $this->callback(function ($options) {
                    $this->assertSame(
                        [
                            'sandbox_mode' => true,
                            'return_message_ids' => true,
                        ],
                        $options['json']['mail_settings']
                    );
                    $this->assertSame('laneful-php/1.1.0', $options['headers']['User-Agent']);

                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->client->sendEmail(
            $email,
            new MailSettings(sandboxMode: true, returnMessageIds: true)
        );

        $this->assertSame('accepted', $result['status']);
        $this->assertSame(['msg-1'], $result['message_ids']);
    }

    public function testListUnsubscribeGroups(): void
    {
        $body = json_encode([
            'unsubscribe_groups' => [
                [
                    'unsubscribe_group_id' => 9,
                    'name' => 'Newsletters',
                    'created_at' => 1710000000,
                ],
            ],
            'next_cursor' => 'abc',
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.example.com/v1/workspaces/42/unsubscribe-groups?limit=10&search=news',
                $this->anything()
            )
            ->willReturn(new Response(200, [], $body));

        $result = $this->client->listUnsubscribeGroups(
            42,
            new ListUnsubscribeGroupsParams(limit: 10, search: 'news')
        );

        $this->assertCount(1, $result->unsubscribeGroups);
        $this->assertSame('Newsletters', $result->unsubscribeGroups[0]->name);
        $this->assertSame('abc', $result->nextCursor);
    }

    public function testCreateAndUpdateUnsubscribeGroup(): void
    {
        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) {
                if ($method === 'POST') {
                    $this->assertSame(
                        'https://api.example.com/v1/workspaces/42/unsubscribe-groups',
                        $url
                    );
                    $this->assertSame(['name' => 'Newsletters'], $options['json']);

                    return new Response(200, [], json_encode([
                        'unsubscribe_group' => [
                            'unsubscribe_group_id' => 9,
                            'name' => 'Newsletters',
                            'created_at' => 1710000000,
                        ],
                    ]));
                }

                $this->assertSame('PATCH', $method);
                $this->assertSame(
                    'https://api.example.com/v1/workspaces/42/unsubscribe-groups/9',
                    $url
                );
                $this->assertSame(['name' => 'Promos'], $options['json']);

                return new Response(200, [], json_encode([
                    'unsubscribe_group' => [
                        'unsubscribe_group_id' => 9,
                        'name' => 'Promos',
                        'created_at' => 1710000000,
                    ],
                ]));
            });

        $created = $this->client->createUnsubscribeGroup(42, 'Newsletters');
        $this->assertSame(9, $created->unsubscribeGroupId);

        $updated = $this->client->updateUnsubscribeGroup(42, 9, 'Promos');
        $this->assertSame('Promos', $updated->name);
    }

    public function testDomainEndpoints(): void
    {
        $domainJson = json_encode([
            'domain' => 'example.com',
            'tracking' => 'track',
            'return_path' => 'bounce',
            'verified' => true,
            'dmarc_verified' => true,
            'email_track_id' => 'track-1',
        ]);

        $this->httpClient
            ->expects($this->exactly(6))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url) use ($domainJson) {
                return match (true) {
                    $method === 'GET' && str_contains($url, 'filter%5Bdomain%5D=example.com') => new Response(
                        200,
                        [],
                        json_encode([
                            'domains' => [json_decode($domainJson, true)],
                            'pagination' => ['next_cursor' => null],
                        ])
                    ),
                    $method === 'GET' && str_ends_with($url, '/domains/example.com') => new Response(200, [], $domainJson),
                    $method === 'POST' && str_ends_with($url, '/domains') => new Response(200, [], $domainJson),
                    $method === 'PATCH' => new Response(200, [], $domainJson),
                    $method === 'POST' && str_ends_with($url, '/verify') => new Response(200, [], $domainJson),
                    $method === 'DELETE' => new Response(200, [], '{"message":"deleted"}'),
                    default => throw new \RuntimeException("Unexpected request: {$method} {$url}"),
                };
            });

        $list = $this->client->listDomains(42, new ListDomainsParams(filterDomain: 'example.com'));
        $this->assertSame('example.com', $list->domains[0]->domain);

        $got = $this->client->getDomain(42, 'example.com');
        $this->assertTrue($got->dmarcVerified);

        $created = $this->client->createDomain(42, new CreateDomainRequest(
            domain: 'example.com',
            tracking: 'track',
            returnPath: 'bounce'
        ));
        $this->assertSame('example.com', $created->domain);

        $updated = $this->client->updateDomain(42, 'example.com', new UpdateDomainRequest('track-1'));
        $this->assertSame('track-1', $updated->emailTrackId);

        $verified = $this->client->verifyDomain(42, 'example.com');
        $this->assertTrue($verified->verified);

        $deleted = $this->client->deleteDomain(42, 'example.com');
        $this->assertSame('deleted', $deleted->message);
    }

    public function testAnalyticsEndpoints(): void
    {
        $this->httpClient
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url) {
                $this->assertSame('GET', $method);

                if (str_contains($url, '/analytics/radar/domain-spam-ratio')) {
                    $this->assertStringContainsString('workspace_ids=1', $url);
                    $this->assertStringContainsString('workspace_ids=2', $url);

                    return new Response(200, [], json_encode([
                        'radar' => [
                            [
                                'workspace_id' => 1,
                                'domain' => 'example.com',
                                'esp' => 'Gmail',
                                'spam_ratio' => 0.2,
                                'date' => '2026-09-01',
                            ],
                        ],
                    ]));
                }

                if (str_contains($url, '/analytics/google-postmaster/spam-reports')) {
                    return new Response(200, [], json_encode([
                        'spam_reports' => [
                            [
                                'workspace_id' => 1,
                                'domain' => 'example.com',
                                'date' => '2026-09-01',
                                'spam_ratio' => 0.01,
                            ],
                        ],
                    ]));
                }

                $this->assertStringContainsString('/analytics/microsoft-snds/reports', $url);
                $this->assertStringContainsString('ip=203.0.113.5', $url);

                return new Response(200, [], json_encode([
                    'snds_reports' => [
                        [
                            'ip' => '203.0.113.5',
                            'date' => '2026-09-01',
                            'rcpt_commands' => 1,
                            'data_commands' => 1,
                            'message_recipients' => 1,
                            'filter_result' => 'GREEN',
                            'complaint_rate' => 0.0,
                            'trap_hits' => 0,
                        ],
                    ],
                ]));
            });

        $radar = $this->client->listDomainSpamRatioRadar(
            new ListDomainSpamRatioRadarParams(workspaceIds: [1, 2])
        );
        $this->assertSame('Gmail', $radar->radar[0]->esp);

        $postmaster = $this->client->listGooglePostmasterSpamReports();
        $this->assertSame('example.com', $postmaster->spamReports[0]->domain);

        $snds = $this->client->listSndsReports(new ListSndsReportsParams(ip: '203.0.113.5'));
        $this->assertSame('GREEN', $snds->sndsReports[0]->filterResult);
    }
}
