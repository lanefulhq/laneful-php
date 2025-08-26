<?php

declare(strict_types=1);

namespace Laneful\Webhooks;

/**
 * Utility class for verifying webhook signatures.
 */
final class WebhookVerifier
{
    /**
     * Verify the signature of a webhook payload using HMAC-SHA256.
     *
     * @param string $secret The webhook secret key
     * @param string $payload The raw webhook payload
     * @param string $signature The signature to verify against
     * @return bool True if the signature is valid, false otherwise
     */
    public static function verifySignature(string $secret, string $payload, string $signature): bool
    {
        if (empty($secret) || empty($payload) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Generate a signature for a payload (useful for testing).
     *
     * @param string $secret The webhook secret key
     * @param string $payload The raw webhook payload
     * @return string The generated HMAC-SHA256 signature
     */
    public static function generateSignature(string $secret, string $payload): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
