<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Address;
use Laneful\Models\Email;
use Laneful\Models\MailSettings;
use Laneful\Models\TrackingSettings;

$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://your-endpoint.send.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

$email = new Email(
    from: new Address('sender@example.com', 'Your Name'),
    to: [new Address('recipient@example.com', 'Recipient Name')],
    subject: 'Sandbox email',
    textContent: 'This email is sent with sandbox mode and returns message IDs.',
    fromHeader: new Address('newsletter@example.com', 'Newsletter'),
    tracking: new TrackingSettings(opens: true, clicks: true, unsubscribeGroupName: 'Newsletters')
);

try {
    $response = $client->sendEmail(
        $email,
        new MailSettings(sandboxMode: true, returnMessageIds: true)
    );
    echo "Status: " . ($response['status'] ?? 'unknown') . "\n";
    if (isset($response['message_ids'])) {
        echo "Message IDs: " . implode(', ', $response['message_ids']) . "\n";
    }
} catch (\Laneful\Exceptions\LanefulException $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}
