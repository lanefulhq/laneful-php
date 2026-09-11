<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use InvalidArgumentException;
use Laneful\Models\Email;
use Laneful\Models\Address;
use Laneful\Models\Attachment;
use Laneful\Models\TrackingSettings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\Email
 */
class EmailTest extends TestCase
{
    public function testCanCreateValidEmail(): void
    {
        $from = new Address('sender@example.com', 'Sender Name');
        $to = [new Address('recipient@example.com', 'Recipient Name')];
        
        $email = new Email(
            from: $from,
            to: $to,
            subject: 'Test Subject',
            textContent: 'Test content'
        );
        
        $this->assertSame($from, $email->from);
        $this->assertSame($to, $email->to);
        $this->assertSame('Test Subject', $email->subject);
        $this->assertSame('Test content', $email->textContent);
        $this->assertSame([], $email->cc);
        $this->assertSame([], $email->bcc);
        $this->assertNull($email->htmlContent);
    }

    public function testCanCreateEmailWithAllFields(): void
    {
        $from = new Address('sender@example.com', 'Sender');
        $to = [new Address('to@example.com', 'To')];
        $cc = [new Address('cc@example.com', 'CC')];
        $bcc = [new Address('bcc@example.com', 'BCC')];
        $replyTo = new Address('reply@example.com', 'Reply');
        $attachments = [new Attachment('text/plain', 'test.txt', 'content')];
        $tracking = new TrackingSettings(true, true, true, 123);
        $headers = ['X-Custom' => 'value'];
        $templateData = ['name' => 'John'];
        $webhookData = ['campaign_id' => 'camp_123'];
        
        $email = new Email(
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc,
            subject: 'Test Subject',
            textContent: 'Text content',
            htmlContent: '<p>HTML content</p>',
            templateId: 'template_123',
            templateData: $templateData,
            attachments: $attachments,
            headers: $headers,
            replyTo: $replyTo,
            sendTime: time() + 3600,
            webhookData: $webhookData,
            tag: 'newsletter',
            tracking: $tracking
        );
        
        $this->assertSame($from, $email->from);
        $this->assertSame($to, $email->to);
        $this->assertSame($cc, $email->cc);
        $this->assertSame($bcc, $email->bcc);
        $this->assertSame('Test Subject', $email->subject);
        $this->assertSame('Text content', $email->textContent);
        $this->assertSame('<p>HTML content</p>', $email->htmlContent);
        $this->assertSame('template_123', $email->templateId);
        $this->assertSame($templateData, $email->templateData);
        $this->assertSame($attachments, $email->attachments);
        $this->assertSame($headers, $email->headers);
        $this->assertSame($replyTo, $email->replyTo);
        $this->assertSame($webhookData, $email->webhookData);
        $this->assertSame('newsletter', $email->tag);
        $this->assertSame($tracking, $email->tracking);
        $this->assertNull($email->fromHeader);
    }

    public function testThrowsExceptionWithNoRecipients(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email must have at least one recipient (to, cc, or bcc)');
        
        new Email(
            from: new Address('sender@example.com'),
            subject: 'Test',
            textContent: 'Content'
        );
    }

    public function testThrowsExceptionWithNoContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email must have either content (text/HTML) or a template ID');
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test'
        );
    }

    public function testThrowsExceptionWithPastSendTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Send time must be in the future');
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            sendTime: time() - 3600 // Past time
        );
    }

    public function testThrowsExceptionWithInvalidToAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All "to" addresses must be Address instances');
        
        new Email(
            from: new Address('sender@example.com'),
            to: ['invalid-address'], // Should be Address instance
            subject: 'Test',
            textContent: 'Content'
        );
    }

    public function testThrowsExceptionWithInvalidCcAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All "cc" addresses must be Address instances');
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            cc: ['invalid-address'], // Should be Address instance
            subject: 'Test',
            textContent: 'Content'
        );
    }

    public function testThrowsExceptionWithInvalidBccAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All "bcc" addresses must be Address instances');
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            bcc: ['invalid-address'], // Should be Address instance
            subject: 'Test',
            textContent: 'Content'
        );
    }

    public function testAcceptsOnlyCcRecipients(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            cc: [new Address('cc@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );
        
        $this->assertSame([], $email->to);
        $this->assertCount(1, $email->cc);
    }

    public function testAcceptsOnlyBccRecipients(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            bcc: [new Address('bcc@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );
        
        $this->assertSame([], $email->to);
        $this->assertCount(1, $email->bcc);
    }

    public function testAcceptsOnlyTemplateId(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            templateId: 'template_123'
        );
        
        $this->assertNull($email->textContent);
        $this->assertNull($email->htmlContent);
        $this->assertSame('template_123', $email->templateId);
    }

    public function testFromArray(): void
    {
        $data = [
            'from' => ['email' => 'sender@example.com', 'name' => 'Sender'],
            'to' => [['email' => 'to@example.com', 'name' => 'To']],
            'cc' => [['email' => 'cc@example.com', 'name' => 'CC']],
            'bcc' => [['email' => 'bcc@example.com']],
            'subject' => 'Test Subject',
            'text_content' => 'Text content',
            'html_content' => '<p>HTML content</p>',
            'template_id' => 'template_123',
            'template_data' => ['name' => 'John'],
            'attachments' => [['content_type' => 'text/plain', 'file_name' => 'test.txt']],
            'headers' => ['X-Custom' => 'value'],
            'reply_to' => ['email' => 'reply@example.com'],
            'send_time' => time() + 3600,
            'webhook_data' => ['campaign_id' => 'camp_123'],
            'tag' => 'newsletter',
            'tracking' => ['opens' => true, 'clicks' => false]
        ];
        
        $email = Email::fromArray($data);
        
        $this->assertSame('sender@example.com', $email->from->email);
        $this->assertSame('Sender', $email->from->name);
        $this->assertCount(1, $email->to);
        $this->assertSame('to@example.com', $email->to[0]->email);
        $this->assertCount(1, $email->cc);
        $this->assertSame('cc@example.com', $email->cc[0]->email);
        $this->assertCount(1, $email->bcc);
        $this->assertSame('bcc@example.com', $email->bcc[0]->email);
        $this->assertSame('Test Subject', $email->subject);
        $this->assertSame('Text content', $email->textContent);
        $this->assertSame('<p>HTML content</p>', $email->htmlContent);
        $this->assertSame('template_123', $email->templateId);
        $this->assertSame(['name' => 'John'], $email->templateData);
        $this->assertCount(1, $email->attachments);
        $this->assertSame(['X-Custom' => 'value'], $email->headers);
        $this->assertSame('reply@example.com', $email->replyTo->email);
        $this->assertSame(time() + 3600, $email->sendTime);
        $this->assertSame(['campaign_id' => 'camp_123'], $email->webhookData);
        $this->assertSame('newsletter', $email->tag);
        $this->assertTrue($email->tracking->opens);
        $this->assertFalse($email->tracking->clicks);
        $this->assertNull($email->fromHeader);
    }

    public function testToArray(): void
    {
        $email = new Email(
            from: new Address('sender@example.com', 'Sender'),
            to: [new Address('to@example.com', 'To')],
            cc: [new Address('cc@example.com')],
            subject: 'Test Subject',
            textContent: 'Text content',
            htmlContent: '<p>HTML content</p>',
            templateId: 'template_123',
            templateData: ['name' => 'John'],
            attachments: [new Attachment('text/plain', 'test.txt', 'content')],
            headers: ['X-Custom' => 'value'],
            replyTo: new Address('reply@example.com'),
            sendTime: time() + 3600,
            webhookData: ['campaign_id' => 'camp_123'],
            tag: 'newsletter',
            tracking: new TrackingSettings(true, false, true, 123)
        );
        
        $array = $email->toArray();
        
        $expected = [
            'from' => ['email' => 'sender@example.com', 'name' => 'Sender'],
            'to' => [['email' => 'to@example.com', 'name' => 'To']],
            'cc' => [['email' => 'cc@example.com']],
            'subject' => 'Test Subject',
            'text_content' => 'Text content',
            'html_content' => '<p>HTML content</p>',
            'template_id' => 'template_123',
            'template_data' => ['name' => 'John'],
            'attachments' => [['content_type' => 'text/plain', 'file_name' => 'test.txt', 'content' => 'content']],
            'headers' => ['X-Custom' => 'value'],
            'reply_to' => ['email' => 'reply@example.com'],
            'send_time' => time() + 3600,
            'webhook_data' => ['campaign_id' => 'camp_123'],
            'tag' => 'newsletter',
            'tracking' => ['opens' => true, 'clicks' => false, 'unsubscribes' => true, 'unsubscribe_group_id' => 123]
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testFromHeaderSerializesAsFromHeader(): void
    {
        $fromHeader = new Address('display@example.com', 'Display Name');
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('to@example.com')],
            subject: 'Test',
            textContent: 'Content',
            fromHeader: $fromHeader
        );

        $this->assertSame($fromHeader, $email->fromHeader);
        $this->assertSame(
            ['email' => 'display@example.com', 'name' => 'Display Name'],
            $email->toArray()['from_header']
        );

        $fromArray = Email::fromArray([
            'from' => ['email' => 'sender@example.com'],
            'from_header' => ['email' => 'display@example.com', 'name' => 'Display Name'],
            'to' => [['email' => 'to@example.com']],
            'subject' => 'Test',
            'text_content' => 'Content',
        ]);

        $this->assertSame('display@example.com', $fromArray->fromHeader->email);
        $this->assertSame('Display Name', $fromArray->fromHeader->name);
    }

    public function testAcceptsTwentyWebhookDataKeys(): void
    {
        $webhookData = [];
        for ($i = 0; $i < 20; $i++) {
            $webhookData["key{$i}"] = "value{$i}";
        }

        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            webhookData: $webhookData
        );

        $this->assertCount(20, $email->webhookData);
    }

    public function testToArrayWithMinimalData(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('to@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );
        
        $array = $email->toArray();
        
        $expected = [
            'from' => ['email' => 'sender@example.com'],
            'to' => [['email' => 'to@example.com']],
            'subject' => 'Test',
            'text_content' => 'Content'
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testJsonSerialize(): void
    {
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('to@example.com')],
            subject: 'Test',
            textContent: 'Content'
        );
        
        $this->assertEquals($email->toArray(), $email->jsonSerialize());
    }

    public function testThrowsExceptionWhenTooManyRecipients(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum 1000 recipients total across to/cc/bcc fields');
        
        // Create 1001 recipients to exceed the limit
        $recipients = [];
        for ($i = 0; $i <= 1000; $i++) {
            $recipients[] = new Address("user{$i}@example.com");
        }
        
        new Email(
            from: new Address('sender@example.com'),
            to: $recipients,
            subject: 'Test',
            textContent: 'Content'
        );
    }

    public function testThrowsExceptionWhenSendTimeTooFarInFuture(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Send time cannot be more than 72 hours in the future');
        
        $futureTime = time() + (73 * 60 * 60); // 73 hours
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            sendTime: $futureTime
        );
    }

    public function testThrowsExceptionWhenWebhookDataHasTooManyKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhook data cannot have more than 20 keys');
        
        $webhookData = [];
        for ($i = 0; $i < 21; $i++) {
            $webhookData["key{$i}"] = "value{$i}";
        }
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            webhookData: $webhookData
        );
    }

    public function testThrowsExceptionWhenWebhookDataKeyTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhook data keys cannot exceed 50 characters');
        
        $longKey = str_repeat('a', 51); // 51 characters
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            webhookData: [$longKey => 'value']
        );
    }

    public function testThrowsExceptionWhenWebhookDataValueTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhook data values cannot exceed 100 characters');
        
        $longValue = str_repeat('a', 101); // 101 characters
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            webhookData: ['key' => $longValue]
        );
    }

    public function testThrowsExceptionWhenTagTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag cannot exceed 100 characters');
        
        $longTag = str_repeat('a', 101); // 101 characters
        
        new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            tag: $longTag
        );
    }

    public function testAcceptsValidLimits(): void
    {
        // Test exactly at the limits to ensure they're accepted
        $webhookData = [];
        for ($i = 0; $i < 10; $i++) {
            $key = str_repeat('a', 50); // Exactly 50 characters
            $value = str_repeat('b', 100); // Exactly 100 characters
            $webhookData["{$key}{$i}"] = "{$value}{$i}";
        }
        
        $email = new Email(
            from: new Address('sender@example.com'),
            to: [new Address('recipient@example.com')],
            subject: 'Test',
            textContent: 'Content',
            sendTime: time() + (72 * 60 * 60), // Exactly 72 hours
            webhookData: ['key' => str_repeat('c', 100)], // Exactly 100 chars
            tag: str_repeat('d', 100) // Exactly 100 characters
        );
        
        $this->assertInstanceOf(Email::class, $email);
    }
}
