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
- Visible `from_header` and request-level `mail_settings`
- Scheduled sending
- Webhook signature verification
- Domain management (list, create, verify, update email track, delete)
- Unsubscribe groups
- Deliverability analytics (spam-ratio radar, Google Postmaster, Microsoft SNDS)

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
                case 'request':
                    // Handle send request accepted
                    break;
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

### Visible From header

```php
$email = new Email(
    from: new Address('sender@example.com', 'Your Name'),
    to: [new Address('user@example.com')],
    subject: 'Hello',
    textContent: 'Hello',
    fromHeader: new Address('newsletter@example.com', 'Newsletter')
);
```

### Mail settings (sandbox and message IDs)

```php
use Laneful\Models\MailSettings;

$response = $client->sendEmail(
    $email,
    new MailSettings(sandboxMode: true, returnMessageIds: true)
);
// $response['message_ids'] is present when returnMessageIds is true
```

### Tracking with an unsubscribe group

```php
$tracking = new TrackingSettings(
    opens: true,
    clicks: true,
    unsubscribes: true,
    unsubscribeGroupId: 123,
    // ignored if unsubscribeGroupId is set
    unsubscribeGroupName: 'Newsletters'
);
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

## Domain, unsubscribe groups, and analytics

These endpoints live on the organization API host. Point the client at it:

```php
$client = new LanefulClient(
    baseUrl: 'https://api.laneful.net',
    authToken: 'your-auth-token'
);
```

### Unsubscribe groups

```php
use Laneful\Models\ListUnsubscribeGroupsParams;

$groups = $client->listUnsubscribeGroups(42, new ListUnsubscribeGroupsParams(limit: 50));
$created = $client->createUnsubscribeGroup(42, 'Newsletters');
$updated = $client->updateUnsubscribeGroup(42, $created->unsubscribeGroupId, 'Weekly Newsletters');
```

### Domains

```php
use Laneful\Models\CreateDomainRequest;
use Laneful\Models\ListDomainsParams;
use Laneful\Models\UpdateDomainRequest;

$list = $client->listDomains(42, new ListDomainsParams(limit: 50));
$domain = $client->createDomain(42, new CreateDomainRequest(
    domain: 'mydomain.com',
    tracking: 'tracking',
    returnPath: 'return-path'
));
$domain = $client->getDomain(42, 'mydomain.com');
$domain = $client->verifyDomain(42, 'mydomain.com');

// Set the email track; pass '' to clear it, or omit emailTrackId to leave it unchanged
$domain = $client->updateDomain(42, 'mydomain.com', new UpdateDomainRequest(
    emailTrackId: 'e59f0a35-05bc-4516-b585-c06f69c3e67e'
));

$client->deleteDomain(42, 'mydomain.com');
```

### Deliverability analytics

```php
use Laneful\Models\ListDomainSpamRatioRadarParams;
use Laneful\Models\ListGooglePostmasterSpamReportsParams;
use Laneful\Models\ListSndsReportsParams;

$radar = $client->listDomainSpamRatioRadar(new ListDomainSpamRatioRadarParams(
    workspaceIds: [1, 2],
    domain: 'example.com',
    startDate: '2026-09-01',
    endDate: '2026-09-08'
));

$postmaster = $client->listGooglePostmasterSpamReports(
    new ListGooglePostmasterSpamReportsParams(domain: 'example.com')
);

$snds = $client->listSndsReports(new ListSndsReportsParams(ip: '203.0.113.5'));
```
