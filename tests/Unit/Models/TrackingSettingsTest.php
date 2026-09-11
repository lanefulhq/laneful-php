<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use Laneful\Models\TrackingSettings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\TrackingSettings
 */
class TrackingSettingsTest extends TestCase
{
    public function testCanCreateWithAllSettings(): void
    {
        $tracking = new TrackingSettings(true, false, true, 123);
        
        $this->assertTrue($tracking->opens);
        $this->assertFalse($tracking->clicks);
        $this->assertTrue($tracking->unsubscribes);
        $this->assertSame(123, $tracking->unsubscribeGroupId);
        $this->assertNull($tracking->unsubscribeGroupName);
    }

    public function testCanCreateWithDefaults(): void
    {
        $tracking = new TrackingSettings();
        
        $this->assertNull($tracking->opens);
        $this->assertNull($tracking->clicks);
        $this->assertNull($tracking->unsubscribes);
        $this->assertNull($tracking->unsubscribeGroupId);
    }

    public function testFromArray(): void
    {
        $data = [
            'opens' => true,
            'clicks' => false,
            'unsubscribes' => true,
            'unsubscribe_group_id' => 456
        ];
        
        $tracking = TrackingSettings::fromArray($data);
        
        $this->assertTrue($tracking->opens);
        $this->assertFalse($tracking->clicks);
        $this->assertTrue($tracking->unsubscribes);
        $this->assertSame(456, $tracking->unsubscribeGroupId);
        $this->assertNull($tracking->unsubscribeGroupName);
    }

    public function testUnsubscribeGroupName(): void
    {
        $tracking = new TrackingSettings(
            unsubscribeGroupName: 'Newsletters'
        );

        $this->assertSame('Newsletters', $tracking->unsubscribeGroupName);
        $this->assertSame(
            ['unsubscribe_group_name' => 'Newsletters'],
            $tracking->toArray()
        );

        $fromArray = TrackingSettings::fromArray([
            'unsubscribe_group_name' => 'Promotions',
        ]);
        $this->assertSame('Promotions', $fromArray->unsubscribeGroupName);
    }

    public function testToArray(): void
    {
        $tracking = new TrackingSettings(true, false, true, 789);
        $expected = [
            'opens' => true,
            'clicks' => false,
            'unsubscribes' => true,
            'unsubscribe_group_id' => 789
        ];
        
        $this->assertSame($expected, $tracking->toArray());
    }

    public function testToArrayWithNulls(): void
    {
        $tracking = new TrackingSettings();
        $expected = [];
        
        $this->assertSame($expected, $tracking->toArray());
    }

    public function testToArrayPartial(): void
    {
        $tracking = new TrackingSettings(true, null, false);
        $expected = [
            'opens' => true,
            'unsubscribes' => false
        ];
        
        $this->assertSame($expected, $tracking->toArray());
    }

    public function testJsonSerialize(): void
    {
        $tracking = new TrackingSettings(true, false);
        $expected = [
            'opens' => true,
            'clicks' => false
        ];
        
        $this->assertSame($expected, $tracking->jsonSerialize());
    }
}
