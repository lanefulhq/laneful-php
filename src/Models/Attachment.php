<?php

declare(strict_types=1);

namespace Laneful\Models;

use JsonSerializable;

/**
 * Represents an email attachment.
 */
final class Attachment implements JsonSerializable
{
    public function __construct(
        public readonly string $contentType,
        public readonly ?string $fileName = null,
        public readonly ?string $content = null,
        /** @deprecated Reserved for future inline attachment support. Not currently used by the API. */
        public readonly ?string $inlineId = null
    ) {
        if (empty($this->contentType)) {
            throw new \InvalidArgumentException('Content type cannot be empty');
        }
    }

    /**
     * Create an Attachment from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contentType: $data['content_type'] ?? '',
            fileName: $data['file_name'] ?? null,
            content: $data['content'] ?? null,
            inlineId: $data['inline_id'] ?? null
        );
    }

    /**
     * Create an attachment from a file path.
     */
    public static function fromFile(string $filePath, ?string $fileName = null): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$filePath}");
        }

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileName = $fileName ?? basename($filePath);

        return new self(
            contentType: $mimeType,
            fileName: $fileName,
            content: base64_encode($content)
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = ['content_type' => $this->contentType];

        if ($this->fileName !== null) {
            $data['file_name'] = $this->fileName;
        }

        if ($this->content !== null) {
            $data['content'] = $this->content;
        }

        if ($this->inlineId !== null) {
            $data['inline_id'] = $this->inlineId;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
