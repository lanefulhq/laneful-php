<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

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
}
