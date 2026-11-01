<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Hash\Murmur;

class MurmurTest extends TestCase
{
    #[Test]
    public function hash(): void
    {
        $hash = new Murmur();
        $value = 'test value';
        $expected = 3804435892;

        static::assertEquals($expected, $hash->generate($value));
    }
}
