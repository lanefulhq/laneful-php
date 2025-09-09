<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Exceptions\*;

// Initialize the client
$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://your-endpoint.send.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

// Create email
$emails = [
    new Email(
        from: new Address('sender@example.com'),
        to: [new Address('user1@example.com')],
        subject: 'Email 1',
        textContent: 'First email content.'
    ),
    new Email(
        from: new Address('sender@example.com'),
        to: [new Address('user2@example.com')],
        subject: 'Email 2',
        textContent: 'Second email content.'
    )
];

// Send email
try {
    $response = $client->sendEmails($emails);
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