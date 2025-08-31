# Laneful PHP Client

A PHP client library for the Laneful email API.

## Installation

```bash
composer require lanefulhq/laneful-php
```

**Requirements:** PHP 8.1+

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Models\TrackingSettings;

$client = new LanefulClient(
    baseUrl: 'https://your-endpoint.send.laneful.net',
    authToken: 'your-auth-token'
);

$email = new Email(
    from: new Address('sender@example.com', 'Your Name'),
    to: [new Address('recipient@example.com', 'Recipient Name')],
    subject: 'Hello from Laneful',
    textContent: 'This is a test email.',
    htmlContent: '<h1>This is a test email.</h1>',
    tracking: new TrackingSettings(opens: true, clicks: true)
);

try {
    $response = $client->sendEmail($email);
    echo "Email sent successfully\n";
} catch (\Laneful\Exceptions\LanefulException $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}
```

## Features

- Send single or multiple emails
- Plain text and HTML content
- Email templates with dynamic data
- File attachments
- Email tracking (opens, clicks, unsubscribes)
- Custom headers and reply-to addresses
- Scheduled sending
- Webhook signature verification

## Examples

### Template Email

```php
$email = new Email(
    from: new Address('sender@example.com'),
    to: [new Address('user@example.com')],
    subject: 'Welcome to Our Service',
    templateId: 'welcome-template',
    templateData: [
        'name' => 'John Doe',
        'company' => 'Acme Corp',
    ],
    tracking: new TrackingSettings(opens: true, clicks: true)
);

$response = $client->sendEmail($email);
```

### Email with Attachments

```php
use Laneful\Models\Attachment;

$email = new Email(
    from: new Address('sender@example.com'),
    to: [new Address('user@example.com')],
    subject: 'Document Attached',
    textContent: 'Please find the document attached.',
    attachments: [
        Attachment::fromFile('/path/to/document.pdf'),
    ],
    tracking: new TrackingSettings(opens: true, clicks: true)
);

$response = $client->sendEmail($email);
```

### Email with Tracking

```php
use Laneful\Models\TrackingSettings;

$email = new Email(
    from: new Address('sender@example.com'),
    to: [new Address('user@example.com')],
    subject: 'Tracked Email',
    htmlContent: '<p>This email is tracked.</p>',
    tracking: new TrackingSettings(
        opens: true,
        clicks: true,
        unsubscribes: true
    )
);

$response = $client->sendEmail($email);
```

### Web Example (Browser-based Testing)

To run the interactive web example in your browser:

```bash
# Start the web server (nginx + php-fpm)
make web

# Open your browser and navigate to:
# http://localhost:8080/examples/web_example.php
```

## Webhook Verification

```php
use Laneful\Webhooks\WebhookVerifier;

// Get the raw payload
$payload = file_get_contents('php://input');

// Extract signature from headers (handles multiple formats)
$signature = WebhookVerifier::extractSignatureFromHeaders($_SERVER);
$secret = 'your-webhook-secret';

if ($signature && WebhookVerifier::verifySignature($secret, $payload, $signature)) {
    // Parse and validate webhook payload
    try {
        $webhookData = WebhookVerifier::parseWebhookPayload($payload);
        
        // Process events (supports both batch and single event formats)
        foreach ($webhookData['events'] as $event) {
            switch ($event['event']) {
                case 'delivery':
                    // Handle email delivered
                    break;
                case 'open':
                    // Handle email opened
                    break;
                case 'click':
                    // Handle link clicked
                    break;
                case 'bounce':
                    // Handle email bounced
                    break;
                // Add other event types as needed
            }
        }
        
        http_response_code(200);
        echo "OK";
    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        echo "Invalid webhook payload: " . $e->getMessage();
    }
} else {
    http_response_code(401);
    echo "Invalid webhook signature\n";
}
```

## Error Handling

```php
use Laneful\Exceptions\{ApiException, HttpException, ValidationException};

try {
    $response = $client->sendEmail($email);
} catch (ValidationException $e) {
    // Invalid input data
    echo "Validation error: " . $e->getMessage();
} catch (ApiException $e) {
    // API returned an error
    echo "API error: " . $e->getMessage();
} catch (HttpException $e) {
    // Network or HTTP-level error
    echo "HTTP error: " . $e->getMessage();
}
```

## API Reference

### Multiple Emails

```php
$response = $client->sendEmails([$email1, $email2, $email3]);
```

### Custom Timeout

```php
$client = new LanefulClient(
    baseUrl: $baseUrl,
    authToken: $authToken,
    timeout: 60 // seconds
);
```

### Scheduled Email

```php
$email = new Email(
    from: new Address('sender@example.com'),
    to: [new Address('user@example.com')],
    subject: 'Scheduled Email',
    textContent: 'This email was scheduled.',
    sendTime: time() + (24 * 60 * 60), // 24 hours from now
    tracking: new TrackingSettings(opens: true, clicks: true)
);
```
