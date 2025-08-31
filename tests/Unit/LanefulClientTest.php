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
use Laneful\Models\Email;
use Laneful\Models\Address;
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
}
