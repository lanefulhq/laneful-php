<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Models\TrackingSettings;
use Laneful\Exceptions\*;

// Initialize the client
$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://your-endpoint.send.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

// Create tracking settings
$tracking = new TrackingSettings(opens: true, clicks: true, unsubscribes: true);

// Create email
$email = new Email(
    from: new Address('sender@example.com', 'Your Name'),
    to: [new Address('recipient@example.com', 'Recipient Name')],
    subject: 'HTML Email with Tracking',
    htmlContent: '<h1>Welcome!</h1><p>This is an <strong>HTML email</strong> with tracking enabled.</p>',
    textContent: 'Welcome! This is an HTML email with tracking enabled.',
    tracking: $tracking,
    tag: 'welcome-email'
);

// Send email
try {
    $response = $client->sendEmail($email);
    echo "✓ Email sent successfully!\n";
    echo "Response: " . json_encode($response) . "\n";
} catch (ValidationException $e) {
    echo "✗ Validation error: " . $e->getMessage() . "\n";
} catch (ApiException $e) {
    echo "✗ API error: " . $e->getMessage() . "\n";
    echo "Status code: " . $e->getCode() . "\n";
} catch (HttpException $e) {
    echo "✗ HTTP error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Unexpected error: " . $e->getMessage() . "\n";
}

?>