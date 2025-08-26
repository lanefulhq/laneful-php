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
    public function testVerifyValidSignature(): void
    {
        $secret = 'test-secret-key';
        $payload = '{"event":"email.delivered","email_id":"123"}';
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        $result = WebhookVerifier::verifySignature($secret, $payload, $expectedSignature);
        
        $this->assertTrue($result);
    }

    public function testVerifyInvalidSignature(): void
    {
        $secret = 'test-secret-key';
        $payload = '{"event":"email.delivered","email_id":"123"}';
        $invalidSignature = 'invalid-signature';
        
        $result = WebhookVerifier::verifySignature($secret, $payload, $invalidSignature);
        
        $this->assertFalse($result);
    }

    public function testVerifySignatureWithDifferentSecret(): void
    {
        $secret = 'test-secret-key';
        $payload = '{"event":"email.delivered","email_id":"123"}';
        $wrongSecret = 'wrong-secret';
        $wrongSignature = hash_hmac('sha256', $payload, $wrongSecret);
        
        $result = WebhookVerifier::verifySignature($secret, $payload, $wrongSignature);
        
        $this->assertFalse($result);
    }

    public function testVerifySignatureWithEmptyInputs(): void
    {
        $this->assertFalse(WebhookVerifier::verifySignature('', 'payload', 'signature'));
        $this->assertFalse(WebhookVerifier::verifySignature('secret', '', 'signature'));
        $this->assertFalse(WebhookVerifier::verifySignature('secret', 'payload', ''));
    }

    public function testGenerateSignature(): void
    {
        $secret = 'test-secret-key';
        $payload = '{"event":"email.delivered","email_id":"123"}';
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        $generatedSignature = WebhookVerifier::generateSignature($secret, $payload);
        
        $this->assertSame($expectedSignature, $generatedSignature);
    }

    public function testGenerateAndVerifySignature(): void
    {
        $secret = 'test-secret-key';
        $payload = '{"event":"email.delivered","email_id":"123"}';
        
        $signature = WebhookVerifier::generateSignature($secret, $payload);
        $isValid = WebhookVerifier::verifySignature($secret, $payload, $signature);
        
        $this->assertTrue($isValid);
    }
}
