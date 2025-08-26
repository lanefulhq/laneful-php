<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use InvalidArgumentException;
use Laneful\Models\Attachment;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\Attachment
 */
class AttachmentTest extends TestCase
{
    public function testCanCreateValidAttachment(): void
    {
        $attachment = new Attachment(
            'application/pdf',
            'document.pdf',
            'base64-content',
            'inline-id'
        );
        
        $this->assertSame('application/pdf', $attachment->contentType);
        $this->assertSame('document.pdf', $attachment->fileName);
        $this->assertSame('base64-content', $attachment->content);
        $this->assertSame('inline-id', $attachment->inlineId);
    }

    public function testCanCreateAttachmentWithMinimalData(): void
    {
        $attachment = new Attachment('application/pdf');
        
        $this->assertSame('application/pdf', $attachment->contentType);
        $this->assertNull($attachment->fileName);
        $this->assertNull($attachment->content);
        $this->assertNull($attachment->inlineId);
    }

    public function testThrowsExceptionForEmptyContentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Content type cannot be empty');
        
        new Attachment('');
    }

    public function testFromArray(): void
    {
        $data = [
            'content_type' => 'application/pdf',
            'file_name' => 'document.pdf',
            'content' => 'base64-content',
            'inline_id' => 'inline-id'
        ];
        
        $attachment = Attachment::fromArray($data);
        
        $this->assertSame('application/pdf', $attachment->contentType);
        $this->assertSame('document.pdf', $attachment->fileName);
        $this->assertSame('base64-content', $attachment->content);
        $this->assertSame('inline-id', $attachment->inlineId);
    }

    public function testToArray(): void
    {
        $attachment = new Attachment(
            'application/pdf',
            'document.pdf',
            'base64-content',
            'inline-id'
        );
        
        $expected = [
            'content_type' => 'application/pdf',
            'file_name' => 'document.pdf',
            'content' => 'base64-content',
            'inline_id' => 'inline-id'
        ];
        
        $this->assertSame($expected, $attachment->toArray());
    }

    public function testToArrayWithMinimalData(): void
    {
        $attachment = new Attachment('application/pdf');
        $expected = ['content_type' => 'application/pdf'];
        
        $this->assertSame($expected, $attachment->toArray());
    }

    public function testJsonSerialize(): void
    {
        $attachment = new Attachment('application/pdf', 'document.pdf');
        $expected = [
            'content_type' => 'application/pdf',
            'file_name' => 'document.pdf'
        ];
        
        $this->assertSame($expected, $attachment->jsonSerialize());
    }
}
