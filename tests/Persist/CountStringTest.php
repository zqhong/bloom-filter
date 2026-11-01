<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Persist\CountString15;

class CountStringTest extends TestCase
{
    #[Test]
    public function createWithDefaultSize(): void
    {
        $persister = new CountString15();

        $class = new \ReflectionClass(CountString15::class);
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");

        static::assertEquals(CountString15::DEFAULT_BYTE_SIZE, $propertySize->getValue($persister));
        static::assertEquals(CountString15::DEFAULT_BYTE_SIZE, strlen($propertyBytes->getValue($persister)));
    }

    #[Test]
    public function incrementBit(): void
    {
        $persister = new CountString15();
        static::assertEquals(1, $persister->incrementBit(0));
        static::assertEquals(2, $persister->incrementBit(0));
        static::assertEquals(1, $persister->incrementBit(1));
        static::assertEquals(1, $persister->incrementBit(2));
        static::assertEquals(1, $persister->incrementBit(2048));
        static::assertEquals(2, $persister->incrementBit(2048));
    }

    #[Test]
    public function getBit(): void
    {
        $persister = new CountString15();
        for ($i = 0; $i < 7; $i++) {
            $persister->incrementBit(13);
        }
        $persister->incrementBit(14);
        $persister->incrementBit(10001);
        $persister->decrementBit(10002);

        static::assertEquals(0, $persister->get(12));
        static::assertEquals(7, $persister->get(13));
        static::assertEquals(1, $persister->get(10001));
        static::assertEquals(0, $persister->get(10002));
    }

    #[Test]
    public function incrementOverflow(): void
    {
        $this->expectException(\RuntimeException::class);

        $persister = new CountString15();
        for ($i = 0; $i < 16; $i++) {
            $persister->incrementBit(42);
        }
    }

    #[Test]
    public function reset(): void
    {
        $persister = new CountString15();
        $persister->incrementBit(42);
        $persister->reset();

        static::assertEquals(0, $persister->get(42));
    }
}
