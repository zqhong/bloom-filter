<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Hash\Crc32b;

class Crc32bTest extends TestCase
{
    #[Test]
    public function hash(): void
    {
        $hash = new Crc32b();
        $value = 'test value';
        $expected = 3973923115;

        static::assertEquals($expected, $hash->generate($value));
    }
}
