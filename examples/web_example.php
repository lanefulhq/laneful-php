<?php

declare(strict_types=1);

// Web example for running in browser via nginx/php-fpm

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Models\Attachment;
use Laneful\Models\TrackingSettings;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laneful PHP SDK - Web Example</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #e9ecef; border: 1px solid #dee2e6; color: #495057; cursor: pointer; border-radius: 4px 4px 0 0; margin-right: 2px; }
        .tab:hover { background: #d4dde5; }
        .tab.active { background: #007cba; color: white; border-color: #007cba; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .recipients-list { border: 1px solid #ddd; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
        .recipient-item { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .recipient-item input { flex: 1; }
        .remove-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .add-btn { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🚀 Laneful PHP SDK - Web Example</h1>
    <p>Send emails with attachments, templates, and multiple recipients using the Laneful API.</p>

    <?php
    $message = '';
    $messageType = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $baseUrl = $_POST['base_url'] ?? '';
            $authToken = $_POST['auth_token'] ?? '';
            $fromEmail = $_POST['from_email'] ?? '';
            $fromName = $_POST['from_name'] ?? '';
            $emailType = $_POST['email_type'] ?? 'content';
            $subject = $_POST['subject'] ?? '';
            $htmlContent = $_POST['html_content'] ?? '';
            $textContent = $_POST['text_content'] ?? '';
            $templateId = $_POST['template_id'] ?? '';
            $templateData = $_POST['template_data'] ?? '';
            $recipients = $_POST['recipients'] ?? [];
            $sendMultiple = isset($_POST['send_multiple']);

            if (empty($baseUrl) || empty($authToken) || empty($fromEmail)) {
                throw new \InvalidArgumentException('Please fill in base URL, auth token, and from email');
            }

            if (empty($recipients)) {
                throw new \InvalidArgumentException('Please add at least one recipient');
            }

            $client = new LanefulClient($baseUrl, $authToken);

            // Parse recipients
            $toAddresses = [];
            foreach ($recipients as $recipient) {
                if (!empty($recipient['email'])) {
                    $toAddresses[] = new Address(
                        $recipient['email'], 
                        !empty($recipient['name']) ? $recipient['name'] : null
                    );
                }
            }

            if (empty($toAddresses)) {
                throw new \InvalidArgumentException('No valid recipients found');
            }

            // Handle attachments
            $attachments = [];
            if (isset($_FILES['attachments'])) {
                $uploadedFiles = $_FILES['attachments'];
                $fileCount = is_array($uploadedFiles['name']) ? count($uploadedFiles['name']) : 1;
                
                for ($i = 0; $i < $fileCount; $i++) {
                    $fileName = is_array($uploadedFiles['name']) ? $uploadedFiles['name'][$i] : $uploadedFiles['name'];
                    $tmpName = is_array($uploadedFiles['tmp_name']) ? $uploadedFiles['tmp_name'][$i] : $uploadedFiles['tmp_name'];
                    $fileSize = is_array($uploadedFiles['size']) ? $uploadedFiles['size'][$i] : $uploadedFiles['size'];
                    
                    if (!empty($fileName) && !empty($tmpName) && $fileSize > 0) {
                        $content = file_get_contents($tmpName);
                        if ($content !== false) {
                            $mimeType = mime_content_type($tmpName) ?: 'application/octet-stream';
                            $attachments[] = new Attachment(
                                contentType: $mimeType,
                                fileName: $fileName,
                                content: base64_encode($content)
                            );
                        }
                    }
                }
            }

            // Prepare template data if using template
            $templateDataArray = null;
            if ($emailType === 'template' && !empty($templateData)) {
                $templateDataArray = json_decode($templateData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Invalid JSON in template data: ' . json_last_error_msg());
                }
            }

            $emails = [];
            
            if ($sendMultiple) {
                // Send separate email to each recipient
                foreach ($toAddresses as $toAddress) {
                    $emails[] = new Email(
                        from: new Address($fromEmail, $fromName ?: null),
                        to: [$toAddress],
                        subject: $subject,
                        htmlContent: $emailType === 'content' ? ($htmlContent ?: null) : null,
                        textContent: $emailType === 'content' ? ($textContent ?: null) : null,
                        templateId: $emailType === 'template' ? ($templateId ?: null) : null,
                        templateData: $emailType === 'template' ? $templateDataArray : null,
                        attachments: $attachments,
                        tracking: new TrackingSettings(opens: true, clicks: true)
                    );
                }
            } else {
                // Send single email to all recipients
                $emails[] = new Email(
                    from: new Address($fromEmail, $fromName ?: null),
                    to: $toAddresses,
                    subject: $subject,
                    htmlContent: $emailType === 'content' ? ($htmlContent ?: null) : null,
                    textContent: $emailType === 'content' ? ($textContent ?: null) : null,
                    templateId: $emailType === 'template' ? ($templateId ?: null) : null,
                    templateData: $emailType === 'template' ? $templateDataArray : null,
                    attachments: $attachments,
                    tracking: new TrackingSettings(opens: true, clicks: true)
                );
            }

            $response = $client->sendEmails($emails);
            
            $emailCount = count($emails);
            $recipientCount = $sendMultiple ? $emailCount : count($toAddresses);
            $message = "✅ Successfully sent {$emailCount} email(s) to {$recipientCount} recipient(s)! " . 
                      (isset($response['status']) ? "Status: {$response['status']}" : '');
            $messageType = 'success';

        } catch (\Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Display environment variables for convenience
    $envBaseUrl = $_ENV['LANEFUL_BASE_URL'] ?? '';
    $envAuthToken = $_ENV['LANEFUL_AUTH_TOKEN'] ?? '';
    ?>

    <?php if ($message): ?>
        <div class="<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- API Configuration -->
        <div class="form-row">
            <div class="form-group">
                <label for="base_url">Laneful Base URL *</label>
                <input type="url" id="base_url" name="base_url" 
                       value="<?= htmlspecialchars($_POST['base_url'] ?? $envBaseUrl) ?>" 
                       placeholder="https://your-endpoint.send.laneful.net" required>
            </div>
            <div class="form-group">
                <label for="auth_token">Auth Token *</label>
                <input type="password" id="auth_token" name="auth_token" 
                       value="<?= htmlspecialchars($_POST['auth_token'] ?? $envAuthToken) ?>" 
                       placeholder="your-auth-token" required>
            </div>
        </div>

        <!-- From Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="from_email">From Email *</label>
                <input type="email" id="from_email" name="from_email" 
                       value="<?= htmlspecialchars($_POST['from_email'] ?? '') ?>" 
                       placeholder="sender@yourdomain.com" required>
            </div>
            <div class="form-group">
                <label for="from_name">From Name</label>
                <input type="text" id="from_name" name="from_name" 
                       value="<?= htmlspecialchars($_POST['from_name'] ?? '') ?>" 
                       placeholder="Your Name">
            </div>
        </div>

        <!-- Recipients -->
        <div class="form-group">
            <label>Recipients *</label>
            <div id="recipients-container">
                <div class="recipient-item">
                    <input type="email" name="recipients[0][email]" placeholder="recipient@example.com" required>
                    <input type="text" name="recipients[0][name]" placeholder="Name (optional)">
                    <button type="button" class="remove-btn" onclick="removeRecipient(this)" style="display: none;">Remove</button>
                </div>
            </div>
            <button type="button" class="add-btn" onclick="addRecipient()">+ Add Recipient</button>
        </div>

        <!-- Email Sending Options -->
        <div class="form-group">
            <label>
                <input type="checkbox" name="send_multiple" value="1"> 
                Send separate email to each recipient (instead of one email to all)
            </label>
        </div>

        <!-- Subject -->
        <div class="form-group">
            <label for="subject">Subject *</label>
            <input type="text" id="subject" name="subject" 
                   value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" 
                   placeholder="Email subject" required>
        </div>

        <!-- Email Type Tabs -->
        <div class="tabs">
            <button type="button" class="tab active" onclick="switchTab('content')">Content</button>
            <button type="button" class="tab" onclick="switchTab('template')">Template</button>
        </div>

        <!-- Content Tab -->
        <div id="content-tab" class="tab-content active">
            <input type="hidden" name="email_type" value="content">
            
            <div class="form-group">
                <label for="html_content">HTML Content</label>
                <textarea id="html_content" name="html_content" rows="6" 
                          placeholder="<h1>Hello {{name}}!</h1><p>This is an HTML email.</p>"><?= htmlspecialchars($_POST['html_content'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="text_content">Text Content</label>
                <textarea id="text_content" name="text_content" rows="6" 
                          placeholder="Hello {{name}}! This is a plain text email."><?= htmlspecialchars($_POST['text_content'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Template Tab -->
        <div id="template-tab" class="tab-content">
            <div class="form-group">
                <label for="template_id">Template ID *</label>
                <input type="text" id="template_id" name="template_id" 
                       value="<?= htmlspecialchars($_POST['template_id'] ?? '') ?>" 
                       placeholder="Your template ID">
            </div>

            <div class="form-group">
                <label for="template_data">Template Data (JSON)</label>
                <textarea id="template_data" name="template_data" rows="6" 
                          placeholder='{"name": "John Doe", "company": "Acme Inc", "login_url": "https://app.example.com"}'><?= htmlspecialchars($_POST['template_data'] ?? '') ?></textarea>
                <small>Variables available in your template (JSON format)</small>
            </div>
        </div>

        <!-- Attachments -->
        <div class="form-group">
            <label for="attachments">Attachments</label>
            <input type="file" id="attachments" name="attachments[]" multiple>
            <small>Select one or more files to attach to the email</small>
        </div>

        <button type="submit">📧 Send Email(s)</button>
    </form>

    <script>
        let recipientCount = 1;

        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // Update hidden input for email type
            const emailTypeInput = document.querySelector('input[name="email_type"]');
            if (emailTypeInput) {
                emailTypeInput.value = tabName;
            }
        }

        function addRecipient() {
            const container = document.getElementById('recipients-container');
            const newRecipient = document.createElement('div');
            newRecipient.className = 'recipient-item';
            newRecipient.innerHTML = `
                <input type="email" name="recipients[${recipientCount}][email]" placeholder="recipient@example.com" required>
                <input type="text" name="recipients[${recipientCount}][name]" placeholder="Name (optional)">
                <button type="button" class="remove-btn" onclick="removeRecipient(this)">Remove</button>
            `;
            container.appendChild(newRecipient);
            recipientCount++;
            
            updateRemoveButtons();
        }

        function removeRecipient(button) {
            button.parentElement.remove();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const recipients = document.querySelectorAll('.recipient-item');
            recipients.forEach((item, index) => {
                const removeBtn = item.querySelector('.remove-btn');
                if (recipients.length > 1) {
                    removeBtn.style.display = 'inline-block';
                } else {
                    removeBtn.style.display = 'none';
                }
            });
        }

        // Initialize remove button visibility
        updateRemoveButtons();
    </script>

    <div class="code">
        <strong>💡 Tip:</strong> Environment variables are automatically loaded from Docker:<br>
        LANEFUL_BASE_URL: <?= $envBaseUrl ? htmlspecialchars($envBaseUrl) : 'Not set' ?><br>
        LANEFUL_AUTH_TOKEN: <?= $envAuthToken ? '***' . substr($envAuthToken, -4) : 'Not set' ?>
    </div>

    <div class="code">
        <strong>🐳 Docker Commands:</strong><br>
        • <code>make web</code> - Start web server<br>
        • <code>make shell</code> - Open container shell<br>
        • <code>make test</code> - Run tests<br>
        • <code>docker-compose logs -f nginx</code> - View web logs
    </div>
</body>
</html>
