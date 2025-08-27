<?php

declare(strict_types=1);

namespace Laneful\Tests\Unit\Models;

use InvalidArgumentException;
use Laneful\Models\Address;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laneful\Models\Address
 */
class AddressTest extends TestCase
{
    public function testCanCreateValidAddress(): void
    {
        $address = new Address('test@example.com', 'Test User');
        
        $this->assertSame('test@example.com', $address->email);
        $this->assertSame('Test User', $address->name);
    }

    public function testCanCreateAddressWithoutName(): void
    {
        $address = new Address('test@example.com');
        
        $this->assertSame('test@example.com', $address->email);
        $this->assertNull($address->name);
    }

    public function testThrowsExceptionForEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email address cannot be empty');
        
        new Address('');
    }

    public function testThrowsExceptionForInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address format');
        
        new Address('invalid-email');
    }

    public function testFromArray(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];
        
        $address = Address::fromArray($data);
        
        $this->assertSame('test@example.com', $address->email);
        $this->assertSame('Test User', $address->name);
    }

    public function testFromArrayWithoutName(): void
    {
        $data = ['email' => 'test@example.com'];
        
        $address = Address::fromArray($data);
        
        $this->assertSame('test@example.com', $address->email);
        $this->assertNull($address->name);
    }

    public function testToArray(): void
    {
        $address = new Address('test@example.com', 'Test User');
        $expected = [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];
        
        $this->assertSame($expected, $address->toArray());
    }

    public function testToArrayWithoutName(): void
    {
        $address = new Address('test@example.com');
        $expected = ['email' => 'test@example.com'];
        
        $this->assertSame($expected, $address->toArray());
    }

    public function testJsonSerialize(): void
    {
        $address = new Address('test@example.com', 'Test User');
        $expected = [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];
        
        $this->assertSame($expected, $address->jsonSerialize());
    }
}
