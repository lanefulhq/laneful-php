<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Models\Attachment;
use Laneful\Models\TrackingSettings;

echo "🚀 Laneful PHP SDK - Advanced Email Examples\n";
echo "============================================\n\n";

// Initialize the client
$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://your-endpoint.send.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

try {
    // Example 1: Simple email with content
    echo "📧 Example 1: Simple email with HTML/text content\n";
    $simpleEmail = new Email(
        from: new Address('sender@yourdomain.com', 'Your App'),
        to: [new Address('recipient@example.com', 'John Doe')],
        subject: 'Welcome to our service!',
        htmlContent: '<h1>Welcome John!</h1><p>Thank you for signing up.</p>',
        textContent: 'Welcome John! Thank you for signing up.',
        tracking: new TrackingSettings(opens: true, clicks: true)
    );

    $response = $client->sendEmail($simpleEmail);
    echo "   ✅ Sent successfully! Status: " . ($response['status'] ?? 'Unknown') . "\n\n";

    // Example 2: Email with attachment
    echo "📎 Example 2: Email with attachment\n";
    
    // Create a sample text file for demonstration
    $sampleContent = "This is a sample attachment file.\nGenerated at: " . date('Y-m-d H:i:s');
    $tempFile = tempnam(sys_get_temp_dir(), 'laneful_sample_');
    file_put_contents($tempFile, $sampleContent);

    $emailWithAttachment = new Email(
        from: new Address('sender@yourdomain.com', 'Your App'),
        to: [new Address('recipient@example.com', 'John Doe')],
        subject: 'Document attached',
        htmlContent: '<h1>Hello!</h1><p>Please find the attached document.</p>',
        attachments: [
            Attachment::fromFile($tempFile, 'sample_document.txt'),
            // You can also create attachments from content directly:
            new Attachment(
                contentType: 'text/plain',
                fileName: 'info.txt',
                content: base64_encode('Additional information here.')
            )
        ],
        tracking: new TrackingSettings(opens: true, clicks: true)
    );

    $response = $client->sendEmail($emailWithAttachment);
    echo "   ✅ Sent with attachments! Status: " . ($response['status'] ?? 'Unknown') . "\n\n";
    
    // Clean up temp file
    unlink($tempFile);

    // Example 3: Template-based email
    echo "📝 Example 3: Template-based email\n";
    $templateEmail = new Email(
        from: new Address('sender@yourdomain.com', 'Your App'),
        to: [new Address('recipient@example.com', 'John Doe')],
        subject: 'Welcome {{name}}!',
        templateId: 'welcome_template',
        templateData: [
            'name' => 'John Doe',
            'company' => 'Acme Inc',
            'login_url' => 'https://app.example.com/login',
            'support_email' => 'support@yourdomain.com'
        ],
        tracking: new TrackingSettings(opens: true, clicks: true)
    );

    $response = $client->sendEmail($templateEmail);
    echo "   ✅ Sent with template! Status: " . ($response['status'] ?? 'Unknown') . "\n\n";

    // Example 4: Multiple recipients (single email)
    echo "👥 Example 4: Single email to multiple recipients\n";
    $multiRecipientEmail = new Email(
        from: new Address('sender@yourdomain.com', 'Your App'),
        to: [
            new Address('user1@example.com', 'User One'),
            new Address('user2@example.com', 'User Two'),
            new Address('user3@example.com', 'User Three'),
        ],
        cc: [new Address('manager@example.com', 'Manager')],
        subject: 'Team update',
        htmlContent: '<h1>Team Update</h1><p>This message is for the entire team.</p>',
        tracking: new TrackingSettings(opens: true, clicks: true)
    );

    $response = $client->sendEmail($multiRecipientEmail);
    echo "   ✅ Sent to multiple recipients! Status: " . ($response['status'] ?? 'Unknown') . "\n\n";

    // Example 5: Multiple individual emails (bulk sending)
    echo "📬 Example 5: Multiple personalized emails (bulk sending)\n";
    $users = [
        ['email' => 'alice@example.com', 'name' => 'Alice Smith', 'plan' => 'Premium'],
        ['email' => 'bob@example.com', 'name' => 'Bob Johnson', 'plan' => 'Basic'],
        ['email' => 'carol@example.com', 'name' => 'Carol Williams', 'plan' => 'Premium'],
    ];

    $emails = [];
    foreach ($users as $user) {
        $emails[] = new Email(
            from: new Address('sender@yourdomain.com', 'Your App'),
            to: [new Address($user['email'], $user['name'])],
            subject: "Your {$user['plan']} account update",
            htmlContent: "<h1>Hello {$user['name']}!</h1><p>Your {$user['plan']} plan has been updated.</p>",
            textContent: "Hello {$user['name']}! Your {$user['plan']} plan has been updated.",
            tracking: new TrackingSettings(opens: true, clicks: true)
        );
    }

    $response = $client->sendEmails($emails);
    echo "   ✅ Sent " . count($emails) . " personalized emails! Status: " . ($response['status'] ?? 'Unknown') . "\n\n";

    // Example 6: Advanced email with all features
    echo "🌟 Example 6: Advanced email with all features\n";
    $advancedEmail = new Email(
        from: new Address('sender@yourdomain.com', 'Your App'),
        to: [new Address('vip@example.com', 'VIP Customer')],
        cc: [new Address('sales@yourdomain.com', 'Sales Team')],
        bcc: [new Address('audit@yourdomain.com')],
        subject: 'VIP Account Summary',
        templateId: 'vip_summary',
        templateData: [
            'customer_name' => 'VIP Customer',
            'account_balance' => '$5,000.00',
            'points_earned' => 2500,
            'next_tier' => 'Platinum'
        ],
        attachments: [
            new Attachment(
                contentType: 'application/pdf',
                fileName: 'account_statement.pdf',
                content: base64_encode('Sample PDF content would go here...')
            )
        ],
        headers: [
            'X-Priority' => '1',
            'X-Customer-Tier' => 'VIP'
        ],
        replyTo: new Address('vip-support@yourdomain.com', 'VIP Support'),
        tag: 'vip-communication',
        webhookData: [
            'customer_id' => 'cust_12345',
            'campaign_id' => 'vip_monthly_summary'
        ],
        tracking: new TrackingSettings(
            opens: true,
            clicks: true,
            unsubscribes: true
        )
    );

    $response = $client->sendEmail($advancedEmail);
    echo "   ✅ Sent advanced email with all features! Status: " . ($response['status'] ?? 'Unknown') . "\n\n";

    echo "🎉 All examples completed successfully!\n";

} catch (\Laneful\Exceptions\ValidationException $e) {
    echo "❌ Validation error: " . $e->getMessage() . "\n";
} catch (\Laneful\Exceptions\ApiException $e) {
    echo "❌ API error: " . $e->getMessage() . "\n";
    echo "HTTP Status: " . $e->getCode() . "\n";
} catch (\Laneful\Exceptions\HttpException $e) {
    echo "❌ HTTP error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}

echo "\n💡 Tips:\n";
echo "- Set LANEFUL_BASE_URL and LANEFUL_AUTH_TOKEN environment variables\n";
echo "- Templates must be created in your Laneful account first\n";
echo "- Use sendEmails() for bulk operations (better performance)\n";
echo "- Attachments are automatically base64 encoded\n";
echo "- Tracking settings help you monitor email engagement\n";
