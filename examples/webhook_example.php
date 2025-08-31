<?php

declare(strict_types=1);

/**
 * Laneful Webhook Handler Example
 * 
 * This example demonstrates how to properly handle Laneful webhooks
 * according to the documentation in Webhooks.tsx
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\Webhooks\WebhookVerifier;

// Configuration
$webhookSecret = $_ENV['LANEFUL_WEBHOOK_SECRET'] ?? 'your-webhook-secret-here';

/**
 * Complete webhook verification and processing workflow
 * following the documentation exactly
 */
function handleWebhook(): void
{
    global $webhookSecret;
    
    try {
        // Step 1: Get raw payload
        $payload = file_get_contents('php://input');
        if (empty($payload)) {
            throw new \Exception('Empty payload received');
        }

        // Step 2: Extract signature from headers (as documented)
        $signature = WebhookVerifier::extractSignatureFromHeaders($_SERVER);
        if (!$signature) {
            throw new \Exception('Missing webhook signature header');
        }

        // Step 3: Verify signature (supports sha256= prefix as documented)
        if (!WebhookVerifier::verifySignature($webhookSecret, $payload, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        // Step 4: Parse and validate payload structure
        $webhookData = WebhookVerifier::parseWebhookPayload($payload);
        
        // Step 5: Process events (handles both batch and single event formats)
        $processedCount = 0;
        foreach ($webhookData['events'] as $event) {
            processWebhookEvent($event);
            $processedCount++;
        }

        // Log successful processing
        error_log(sprintf(
            'Successfully processed %d webhook event(s) in %s mode',
            $processedCount,
            $webhookData['isBatch'] ? 'batch' : 'single'
        ));

        // Return success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'processed' => $processedCount,
            'mode' => $webhookData['isBatch'] ? 'batch' : 'single'
        ]);

    } catch (\InvalidArgumentException $e) {
        // Payload validation error
        error_log('Webhook payload validation error: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload: ' . $e->getMessage()]);
        
    } catch (\Exception $e) {
        // Other errors (signature, missing data, etc.)
        error_log('Webhook processing error: ' . $e->getMessage());
        http_response_code(401);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Process individual webhook events according to documentation
 */
function processWebhookEvent(array $event): void
{
    $eventType = $event['event'];
    $email = $event['email'];
    $messageId = $event['message_id'];
    $timestamp = $event['timestamp'];
    
    // Log basic event info
    error_log("Processing {$eventType} event for {$email} (Message ID: {$messageId})");
    
    // Process based on event type (all types from documentation)
    switch ($eventType) {
        case 'delivery':
            handleDeliveryEvent($event);
            break;
            
        case 'open':
            handleOpenEvent($event);
            break;
            
        case 'click':
            handleClickEvent($event);
            break;
            
        case 'bounce':
            handleBounceEvent($event);
            break;
            
        case 'drop':
            handleDropEvent($event);
            break;
            
        case 'spam_complaint':
            handleSpamComplaintEvent($event);
            break;
            
        case 'unsubscribe':
            handleUnsubscribeEvent($event);
            break;
            
        default:
            error_log("Unknown event type: {$eventType}");
    }
}

/**
 * Handle delivery events
 */
function handleDeliveryEvent(array $event): void
{
    // Update delivery status in your database
    // Example: markEmailAsDelivered($event['message_id'], $event['timestamp']);
    
    error_log("Email delivered successfully to {$event['email']}");
}

/**
 * Handle open events
 */
function handleOpenEvent(array $event): void
{
    // Track email opens
    $clientInfo = [
        'device' => $event['client_device'] ?? 'Unknown',
        'os' => $event['client_os'] ?? 'Unknown',
        'ip' => $event['client_ip'] ?? 'Unknown'
    ];
    
    error_log("Email opened by {$event['email']} on {$clientInfo['device']} ({$clientInfo['os']})");
    
    // Example: trackEmailOpen($event['message_id'], $clientInfo, $event['timestamp']);
}

/**
 * Handle click events
 */
function handleClickEvent(array $event): void
{
    $url = $event['url'] ?? 'Unknown URL';
    
    error_log("Link clicked in email to {$event['email']}: {$url}");
    
    // Example: trackLinkClick($event['message_id'], $url, $event['timestamp']);
}

/**
 * Handle bounce events
 */
function handleBounceEvent(array $event): void
{
    $bounceType = $event['is_hard'] ? 'hard' : 'soft';
    $reason = $event['text'] ?? 'Unknown reason';
    
    error_log("Email bounced ({$bounceType}) for {$event['email']}: {$reason}");
    
    // Handle hard bounces by suppressing the email
    if ($event['is_hard']) {
        // Example: suppressEmail($event['email'], 'hard_bounce');
    }
}

/**
 * Handle drop events
 */
function handleDropEvent(array $event): void
{
    $reason = $event['reason'] ?? 'Unknown reason';
    
    error_log("Email dropped for {$event['email']}: {$reason}");
    
    // Example: handleEmailDrop($event['message_id'], $reason);
}

/**
 * Handle spam complaint events
 */
function handleSpamComplaintEvent(array $event): void
{
    error_log("Spam complaint received for {$event['email']}");
    
    // Automatically unsubscribe users who mark emails as spam
    // Example: unsubscribeEmail($event['email'], 'spam_complaint');
}

/**
 * Handle unsubscribe events
 */
function handleUnsubscribeEvent(array $event): void
{
    $groupId = $event['unsubscribe_group_id'] ?? null;
    
    error_log("Unsubscribe event for {$event['email']}" . ($groupId ? " (Group: {$groupId})" : ''));
    
    // Example: processUnsubscribe($event['email'], $groupId);
}

// Only process webhooks for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleWebhook();
} else {
    // Show usage information for GET requests
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Laneful Webhook Endpoint</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
            .code { background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; }
            .success { color: #28a745; }
            .info { background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0066cc; }
        </style>
    </head>
    <body>
        <h1>🚀 Laneful Webhook Endpoint</h1>
        <p>This endpoint is ready to receive Laneful webhook events.</p>
        
        <div class="info">
            <h3>Webhook Configuration</h3>
            <p><strong>Header:</strong> <?= WebhookVerifier::getSignatureHeaderName() ?></p>
            <p><strong>Supported Events:</strong> delivery, open, click, bounce, drop, spam_complaint, unsubscribe</p>
            <p><strong>Payload Formats:</strong> Single event (object) or Batch mode (array)</p>
        </div>

        <h3>Test Webhook Verification</h3>
        <div class="code">
curl -X POST <?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?> \
  -H "Content-Type: application/json" \
  -H "<?= WebhookVerifier::getSignatureHeaderName() ?>: sha256=<?= WebhookVerifier::generateSignature('test-secret', '{"event":"delivery","email":"test@example.com","lane_id":"5805dd85-ed8c-44db-91a7-1d53a41c86a5","message_id":"test","timestamp":' . time() . '}') ?>" \
  -d '{"event":"delivery","email":"test@example.com","lane_id":"5805dd85-ed8c-44db-91a7-1d53a41c86a5","message_id":"test","timestamp":<?= time() ?>}'
        </div>

        <h3>Implementation Features</h3>
        <ul>
            <li class="success">✅ Signature verification with sha256= prefix support</li>
            <li class="success">✅ Batch and single event mode detection</li>
            <li class="success">✅ Payload structure validation</li>
            <li class="success">✅ All documented event types supported</li>
            <li class="success">✅ Header extraction with fallback formats</li>
            <li class="success">✅ Comprehensive error handling</li>
        </ul>
    </body>
    </html>
    <?php
}
