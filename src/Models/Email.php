<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Represents a single email to be sent.
 */
final class Email implements JsonSerializable
{
    /**
     * @param Address $from
     * @param Address[] $to
     * @param Address[] $cc
     * @param Address[] $bcc
     * @param string|null $subject
     * @param string|null $textContent
     * @param string|null $htmlContent
     * @param string|null $templateId
     * @param array<string, mixed>|null $templateData
     * @param Attachment[] $attachments
     * @param array<string, string>|null $headers
     * @param Address|null $replyTo
     * @param int|null $sendTime Unix timestamp for scheduled sending
     * @param array<string, string>|null $webhookData
     * @param string|null $tag
     * @param TrackingSettings|null $tracking
     * @param Address|null $fromHeader Visible From address in the message From header
     */
    public function __construct(
        public Address $from,
        public array $to = [],
        public array $cc = [],
        public array $bcc = [],
        public ?string $subject = null,
        public ?string $textContent = null,
        public ?string $htmlContent = null,
        public ?string $templateId = null,
        public ?array $templateData = null,
        public array $attachments = [],
        public ?array $headers = null,
        public ?Address $replyTo = null,
        public ?int $sendTime = null,
        public ?array $webhookData = null,
        public ?string $tag = null,
        public ?TrackingSettings $tracking = null,
        public ?Address $fromHeader = null
    ) {
        $this->validateEmailAddresses();
        $this->validateContent();
    }

    /**
     * Create an Email from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $from = Address::fromArray($data['from'] ?? []);

        $to = array_map(
            fn(array $address) => Address::fromArray($address),
            $data['to'] ?? []
        );

        $cc = array_map(
            fn(array $address) => Address::fromArray($address),
            $data['cc'] ?? []
        );

        $bcc = array_map(
            fn(array $address) => Address::fromArray($address),
            $data['bcc'] ?? []
        );

        $attachments = array_map(
            fn(array $attachment) => Attachment::fromArray($attachment),
            $data['attachments'] ?? []
        );

        $replyTo = isset($data['reply_to']) ? Address::fromArray($data['reply_to']) : null;
        $tracking = isset($data['tracking']) ? TrackingSettings::fromArray($data['tracking']) : null;
        $fromHeader = isset($data['from_header']) ? Address::fromArray($data['from_header']) : null;

        return new self(
            from: $from,
            to: $to,
            cc: $cc,
            bcc: $bcc,
            subject: $data['subject'] ?? null,
            textContent: $data['text_content'] ?? null,
            htmlContent: $data['html_content'] ?? null,
            templateId: $data['template_id'] ?? null,
            templateData: $data['template_data'] ?? null,
            attachments: $attachments,
            headers: $data['headers'] ?? null,
            replyTo: $replyTo,
            sendTime: $data['send_time'] ?? null,
            webhookData: $data['webhook_data'] ?? null,
            tag: $data['tag'] ?? null,
            tracking: $tracking,
            fromHeader: $fromHeader
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'from' => $this->from->toArray(),
        ];

        if (!empty($this->to)) {
            $data['to'] = array_map(fn(Address $address) => $address->toArray(), $this->to);
        }

        if (!empty($this->cc)) {
            $data['cc'] = array_map(fn(Address $address) => $address->toArray(), $this->cc);
        }

        if (!empty($this->bcc)) {
            $data['bcc'] = array_map(fn(Address $address) => $address->toArray(), $this->bcc);
        }

        if ($this->subject !== null) {
            $data['subject'] = $this->subject;
        }

        if ($this->textContent !== null) {
            $data['text_content'] = $this->textContent;
        }

        if ($this->htmlContent !== null) {
            $data['html_content'] = $this->htmlContent;
        }

        if ($this->templateId !== null) {
            $data['template_id'] = $this->templateId;
        }

        if ($this->templateData !== null) {
            $data['template_data'] = $this->templateData;
        }

        if (!empty($this->attachments)) {
            $data['attachments'] = array_map(
                fn(Attachment $attachment) => $attachment->toArray(),
                $this->attachments
            );
        }

        if ($this->headers !== null) {
            $data['headers'] = $this->headers;
        }

        if ($this->replyTo !== null) {
            $data['reply_to'] = $this->replyTo->toArray();
        }

        if ($this->sendTime !== null) {
            $data['send_time'] = $this->sendTime;
        }

        if ($this->webhookData !== null) {
            $data['webhook_data'] = $this->webhookData;
        }

        if ($this->tag !== null) {
            $data['tag'] = $this->tag;
        }

        if ($this->tracking !== null) {
            $data['tracking'] = $this->tracking->toArray();
        }

        if ($this->fromHeader !== null) {
            $data['from_header'] = $this->fromHeader->toArray();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function validateEmailAddresses(): void
    {
        // Validate that at least one recipient exists
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            throw new \InvalidArgumentException('Email must have at least one recipient (to, cc, or bcc)');
        }

        // Validate maximum recipients limit (1000 total across to/cc/bcc as per documentation)
        $totalRecipients = count($this->to) + count($this->cc) + count($this->bcc);
        if ($totalRecipients > 1000) {
            throw new \InvalidArgumentException('Maximum 1000 recipients total across to/cc/bcc fields');
        }

        // Validate address arrays contain only Address objects
        foreach ($this->to as $address) {
            if (!$address instanceof Address) {
                throw new \InvalidArgumentException('All "to" addresses must be Address instances');
            }
        }

        foreach ($this->cc as $address) {
            if (!$address instanceof Address) {
                throw new \InvalidArgumentException('All "cc" addresses must be Address instances');
            }
        }

        foreach ($this->bcc as $address) {
            if (!$address instanceof Address) {
                throw new \InvalidArgumentException('All "bcc" addresses must be Address instances');
            }
        }
    }

    private function validateContent(): void
    {
        // Must have either content or template
        $hasContent = $this->textContent !== null || $this->htmlContent !== null;
        $hasTemplate = $this->templateId !== null;

        if (!$hasContent && !$hasTemplate) {
            throw new \InvalidArgumentException('Email must have either content (text/HTML) or a template ID');
        }

        // Validate send time (max 72 hours in the future as per documentation)
        if ($this->sendTime !== null) {
            if ($this->sendTime <= time()) {
                throw new \InvalidArgumentException('Send time must be in the future');
            }

            $maxFutureTime = time() + (72 * 60 * 60); // 72 hours
            if ($this->sendTime > $maxFutureTime) {
                throw new \InvalidArgumentException('Send time cannot be more than 72 hours in the future');
            }
        }

        // Validate webhook_data limits (max 20 keys, 50 char keys, 100 char values)
        if ($this->webhookData !== null) {
            if (count($this->webhookData) > 20) {
                throw new \InvalidArgumentException('Webhook data cannot have more than 20 keys');
            }

            foreach ($this->webhookData as $key => $value) {
                if (strlen($key) > 50) {
                    throw new \InvalidArgumentException('Webhook data keys cannot exceed 50 characters');
                }
                if (strlen((string)$value) > 100) {
                    throw new \InvalidArgumentException('Webhook data values cannot exceed 100 characters');
                }
            }
        }

        // Validate tag length (max 100 characters)
        if ($this->tag !== null && strlen($this->tag) > 100) {
            throw new \InvalidArgumentException('Tag cannot exceed 100 characters');
        }
    }
}
