<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use Laneful\Exceptions\ValidationException;
use Laneful\Models\MailSettings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\MailSettings
 */
class MailSettingsTest extends TestCase
{
    public function testToArrayOmitsNullsAndKeepsFalse(): void
    {
        $this->assertSame([], (new MailSettings())->toArray());

        $settings = new MailSettings(sandboxMode: true, returnMessageIds: false);
        $this->assertSame(
            [
                'sandbox_mode' => true,
                'return_message_ids' => false,
            ],
            $settings->toArray()
        );
    }

    public function testFromArray(): void
    {
        $settings = MailSettings::fromArray([
            'sandbox_mode' => true,
            'return_message_ids' => true,
        ]);

        $this->assertTrue($settings->sandboxMode);
        $this->assertTrue($settings->returnMessageIds);
        $this->assertSame($settings->toArray(), $settings->jsonSerialize());
    }

    public function testIdempotencyKey(): void
    {
        $this->assertSame(
            ['idempotency_key' => 'order-1234'],
            (new MailSettings(idempotencyKey: 'order-1234'))->toArray()
        );
        $this->assertSame('order-1234', MailSettings::fromArray(['idempotency_key' => 'order-1234'])->idempotencyKey);
        $this->assertSame(str_repeat('k', 36), (new MailSettings(idempotencyKey: str_repeat('k', 36)))->idempotencyKey);
    }

    /**
     * @dataProvider invalidIdempotencyKeys
     */
    public function testRejectsIdempotencyKeyOutsideOneTo36Bytes(string $key): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('between 1 and 36 bytes');

        new MailSettings(idempotencyKey: $key);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIdempotencyKeys(): array
    {
        return [
            'empty' => [''],
            '37 bytes' => [str_repeat('k', 37)],
            '38 bytes, 19 characters' => [str_repeat('é', 19)],
        ];
    }
}
