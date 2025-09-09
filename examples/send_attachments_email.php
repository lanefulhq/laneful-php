<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Models\Attachment;
use Laneful\Models\TrackingSettings;
use Laneful\Exceptions\*;

// Initialize the client
$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://your-endpoint.send.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

// Create attachment from file
$attachment = Attachment::fromFile('/path/to/document.pdf');

// Create email
$email = new Email(
    from: new Address('sender@example.com'),
    to: [new Address('user@example.com')],
    subject: 'Document Attached',
    textContent: 'Please find the document attached.',
    attachments: [$attachment],
    tracking: new TrackingSettings(opens: true, clicks: true)
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