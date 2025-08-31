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
     * @param string $signature The signature to verify against (may include 'sha256=' prefix)
     * @return bool True if the signature is valid, false otherwise
     */
    public static function verifySignature(string $secret, string $payload, string $signature): bool
    {
        if (empty($secret) || empty($payload) || empty($signature)) {
            return false;
        }

        // Handle sha256= prefix as documented in Webhooks.tsx
        $cleanSignature = str_starts_with($signature, 'sha256=') 
            ? substr($signature, 7) 
            : $signature;

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($cleanSignature, $expectedSignature);
    }

    /**
     * Generate a signature for a payload (useful for testing).
     *
     * @param string $secret The webhook secret key
     * @param string $payload The raw webhook payload
     * @param bool $includePrefix Whether to include the 'sha256=' prefix
     * @return string The generated HMAC-SHA256 signature
     */
    public static function generateSignature(string $secret, string $payload, bool $includePrefix = false): string
    {
        $signature = hash_hmac('sha256', $payload, $secret);
        return $includePrefix ? 'sha256=' . $signature : $signature;
    }

    /**
     * Validate webhook payload structure and extract events.
     *
     * @param string $payload The raw webhook payload JSON
     * @return array{isBatch: bool, events: array} Parsed webhook data
     * @throws \InvalidArgumentException If payload is invalid JSON or structure
     */
    public static function parseWebhookPayload(string $payload): array
    {
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload: ' . json_last_error_msg());
        }

        // Determine if this is batch mode (array) or single event (object)
        if (is_array($data) && isset($data[0])) {
            // Batch mode: array of events
            foreach ($data as $event) {
                self::validateEventStructure($event);
            }
            return ['isBatch' => true, 'events' => $data];
        } elseif (is_array($data) && isset($data['event'])) {
            // Single event mode
            self::validateEventStructure($data);
            return ['isBatch' => false, 'events' => [$data]];
        } else {
            throw new \InvalidArgumentException('Invalid webhook payload structure');
        }
    }

    /**
     * Validate individual event structure according to documentation.
     *
     * @param array $event The event data
     * @throws \InvalidArgumentException If event structure is invalid
     */
    private static function validateEventStructure(array $event): void
    {
        $requiredFields = ['event', 'email', 'lane_id', 'message_id', 'timestamp'];
        
        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate event types according to documentation
        $validEventTypes = [
            'delivery', 'open', 'click', 'drop', 'spam_complaint', 
            'unsubscribe', 'bounce'
        ];
        
        if (!in_array($event['event'], $validEventTypes, true)) {
            throw new \InvalidArgumentException("Invalid event type: {$event['event']}");
        }

        // Validate email format
        if (!filter_var($event['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$event['email']}");
        }

        // Validate timestamp is numeric
        if (!is_numeric($event['timestamp'])) {
            throw new \InvalidArgumentException("Invalid timestamp format");
        }

        // Validate lane_id is a valid UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $event['lane_id'])) {
            throw new \InvalidArgumentException("Invalid lane_id format: {$event['lane_id']}");
        }
    }

    /**
     * Get the webhook header name as documented.
     *
     * @return string The correct header name for webhook signatures
     */
    public static function getSignatureHeaderName(): string
    {
        return 'x-webhook-signature';
    }

    /**
     * Extract webhook signature from HTTP headers (supports both formats).
     *
     * @param array $headers HTTP headers array
     * @return string|null The webhook signature or null if not found
     */
    public static function extractSignatureFromHeaders(array $headers): ?string
    {
        // Try documented header name first
        $documentedHeader = 'x-webhook-signature';
        if (isset($headers[$documentedHeader])) {
            return $headers[$documentedHeader];
        }

        // Try uppercase version
        $upperHeader = strtoupper(str_replace('-', '_', $documentedHeader));
        if (isset($headers[$upperHeader])) {
            return $headers[$upperHeader];
        }

        // Try with HTTP_ prefix (common in PHP $_SERVER)
        $serverHeader = 'HTTP_' . $upperHeader;
        if (isset($headers[$serverHeader])) {
            return $headers[$serverHeader];
        }

        return null;
    }
}
