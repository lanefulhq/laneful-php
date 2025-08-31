<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Webhooks;

use Laneful\Webhooks\WebhookVerifier;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Webhooks\WebhookVerifier
 */
class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'test-secret-key';

    // Test signature verification without prefix (legacy support)
    public function testVerifyValidSignatureWithoutPrefix(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com","lane_id":"5805dd85-ed8c-44db-91a7-1d53a41c86a5","message_id":"H-1-019844e340027d728a7cfda632e14d0a","timestamp":1753502407}';
        $signature = hash_hmac('sha256', $payload, self::SECRET);
        
        $result = WebhookVerifier::verifySignature(self::SECRET, $payload, $signature);
        
        $this->assertTrue($result);
    }

    // Test signature verification with sha256= prefix (as documented)
    public function testVerifyValidSignatureWithPrefix(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com","lane_id":"5805dd85-ed8c-44db-91a7-1d53a41c86a5","message_id":"H-1-019844e340027d728a7cfda632e14d0a","timestamp":1753502407}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, self::SECRET);
        
        $result = WebhookVerifier::verifySignature(self::SECRET, $payload, $signature);
        
        $this->assertTrue($result);
    }

    public function testVerifyInvalidSignature(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com"}';
        $invalidSignature = 'invalid-signature';
        
        $result = WebhookVerifier::verifySignature(self::SECRET, $payload, $invalidSignature);
        
        $this->assertFalse($result);
    }

    public function testVerifySignatureWithDifferentSecret(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com"}';
        $wrongSecret = 'wrong-secret';
        $wrongSignature = hash_hmac('sha256', $payload, $wrongSecret);
        
        $result = WebhookVerifier::verifySignature(self::SECRET, $payload, $wrongSignature);
        
        $this->assertFalse($result);
    }

    public function testVerifySignatureWithEmptyInputs(): void
    {
        $this->assertFalse(WebhookVerifier::verifySignature('', 'payload', 'signature'));
        $this->assertFalse(WebhookVerifier::verifySignature('secret', '', 'signature'));
        $this->assertFalse(WebhookVerifier::verifySignature('secret', 'payload', ''));
    }

    public function testGenerateSignatureWithoutPrefix(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com"}';
        $expectedSignature = hash_hmac('sha256', $payload, self::SECRET);
        
        $generatedSignature = WebhookVerifier::generateSignature(self::SECRET, $payload);
        
        $this->assertSame($expectedSignature, $generatedSignature);
    }

    public function testGenerateSignatureWithPrefix(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com"}';
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, self::SECRET);
        
        $generatedSignature = WebhookVerifier::generateSignature(self::SECRET, $payload, true);
        
        $this->assertSame($expectedSignature, $generatedSignature);
    }

    public function testGenerateAndVerifySignatureRoundTrip(): void
    {
        $payload = '{"event":"delivery","email":"user@example.com"}';
        
        // Test without prefix
        $signature = WebhookVerifier::generateSignature(self::SECRET, $payload);
        $this->assertTrue(WebhookVerifier::verifySignature(self::SECRET, $payload, $signature));
        
        // Test with prefix
        $signatureWithPrefix = WebhookVerifier::generateSignature(self::SECRET, $payload, true);
        $this->assertTrue(WebhookVerifier::verifySignature(self::SECRET, $payload, $signatureWithPrefix));
    }

    // Test single event payload parsing
    public function testParseSingleEventPayload(): void
    {
        $payload = json_encode([
            'event' => 'delivery',
            'email' => 'user@example.com',
            'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'timestamp' => 1753502407,
            'metadata' => ['campaign_id' => 'test'],
            'tag' => 'newsletter'
        ]);

        $result = WebhookVerifier::parseWebhookPayload($payload);

        $this->assertFalse($result['isBatch']);
        $this->assertCount(1, $result['events']);
        $this->assertSame('delivery', $result['events'][0]['event']);
    }

    // Test batch mode payload parsing (as documented)
    public function testParseBatchEventPayload(): void
    {
        $payload = json_encode([
            [
                'event' => 'delivery',
                'email' => 'user1@example.com',
                'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
                'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
                'timestamp' => 1753502407
            ],
            [
                'event' => 'open',
                'email' => 'user2@example.com',
                'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
                'message_id' => 'H-1-019844e340027d728a7cfda632e14d0b',
                'timestamp' => 1753502500
            ]
        ]);

        $result = WebhookVerifier::parseWebhookPayload($payload);

        $this->assertTrue($result['isBatch']);
        $this->assertCount(2, $result['events']);
        $this->assertSame('delivery', $result['events'][0]['event']);
        $this->assertSame('open', $result['events'][1]['event']);
    }

    // Test all documented event types
    /**
     * @dataProvider documentedEventTypesProvider
     */
    public function testValidEventTypes(string $eventType): void
    {
        $payload = json_encode([
            'event' => $eventType,
            'email' => 'user@example.com',
            'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'timestamp' => 1753502407
        ]);

        $result = WebhookVerifier::parseWebhookPayload($payload);
        
        $this->assertSame($eventType, $result['events'][0]['event']);
    }

    public static function documentedEventTypesProvider(): array
    {
        return [
            ['delivery'],
            ['open'],
            ['click'],
            ['drop'],
            ['spam_complaint'],
            ['unsubscribe'],
            ['bounce']
        ];
    }

    // Test payload validation errors
    public function testParseInvalidJsonPayload(): void
    {
        $invalidJson = '{"event":"delivery","email"';
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON payload');
        
        WebhookVerifier::parseWebhookPayload($invalidJson);
    }

    public function testParsePayloadMissingRequiredFields(): void
    {
        $payload = json_encode(['event' => 'delivery']); // Missing required fields
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: email');
        
        WebhookVerifier::parseWebhookPayload($payload);
    }

    public function testParsePayloadInvalidEventType(): void
    {
        $payload = json_encode([
            'event' => 'invalid_event_type',
            'email' => 'user@example.com',
            'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'timestamp' => 1753502407
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event type: invalid_event_type');
        
        WebhookVerifier::parseWebhookPayload($payload);
    }

    public function testParsePayloadInvalidEmail(): void
    {
        $payload = json_encode([
            'event' => 'delivery',
            'email' => 'not-an-email',
            'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'timestamp' => 1753502407
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        WebhookVerifier::parseWebhookPayload($payload);
    }

    public function testParsePayloadInvalidLaneId(): void
    {
        $payload = json_encode([
            'event' => 'delivery',
            'email' => 'user@example.com',
            'lane_id' => 'not-a-uuid',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'timestamp' => 1753502407
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid lane_id format');
        
        WebhookVerifier::parseWebhookPayload($payload);
    }

    public function testParsePayloadInvalidTimestamp(): void
    {
        $payload = json_encode([
            'event' => 'delivery',
            'email' => 'user@example.com',
            'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'timestamp' => 'not-a-timestamp'
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timestamp format');
        
        WebhookVerifier::parseWebhookPayload($payload);
    }

    // Test header extraction
    public function testGetSignatureHeaderName(): void
    {
        $this->assertSame('x-webhook-signature', WebhookVerifier::getSignatureHeaderName());
    }

    public function testExtractSignatureFromHeaders(): void
    {
        // Test standard header
        $headers = ['x-webhook-signature' => 'sha256=abc123'];
        $this->assertSame('sha256=abc123', WebhookVerifier::extractSignatureFromHeaders($headers));

        // Test uppercase header
        $headers = ['X_WEBHOOK_SIGNATURE' => 'sha256=def456'];
        $this->assertSame('sha256=def456', WebhookVerifier::extractSignatureFromHeaders($headers));

        // Test HTTP_ prefixed header (common in PHP $_SERVER)
        $headers = ['HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=ghi789'];
        $this->assertSame('sha256=ghi789', WebhookVerifier::extractSignatureFromHeaders($headers));

        // Test missing header
        $headers = ['other-header' => 'value'];
        $this->assertNull(WebhookVerifier::extractSignatureFromHeaders($headers));
    }

    // Test comprehensive webhook verification workflow
    public function testCompleteWebhookVerificationWorkflow(): void
    {
        // Test data from documentation examples
        $eventData = [
            'event' => 'delivery',
            'email' => 'user@example.com',
            'lane_id' => '5805dd85-ed8c-44db-91a7-1d53a41c86a5',
            'message_id' => 'H-1-019844e340027d728a7cfda632e14d0a',
            'metadata' => [
                'campaign_id' => 'camp_456',
                'user_id' => 'user_123'
            ],
            'tag' => 'newsletter-campaign',
            'timestamp' => 1753502407
        ];

        $payload = json_encode($eventData);
        $signature = WebhookVerifier::generateSignature(self::SECRET, $payload, true);

        // Simulate HTTP headers
        $headers = ['x-webhook-signature' => $signature];

        // Step 1: Extract signature from headers
        $extractedSignature = WebhookVerifier::extractSignatureFromHeaders($headers);
        $this->assertNotNull($extractedSignature);

        // Step 2: Verify signature
        $this->assertTrue(WebhookVerifier::verifySignature(self::SECRET, $payload, $extractedSignature));

        // Step 3: Parse payload
        $parsed = WebhookVerifier::parseWebhookPayload($payload);
        $this->assertFalse($parsed['isBatch']);
        $this->assertCount(1, $parsed['events']);
        $this->assertSame('delivery', $parsed['events'][0]['event']);
        $this->assertSame('user@example.com', $parsed['events'][0]['email']);
    }
}
