<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Persist\BitString;

class BitStringTest extends TestCase
{
    #[Test]
    public function createWithDefaultSize(): void
    {
        $persister = new BitString();

        $class = new \ReflectionClass(BitString::class);
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");

        static::assertEquals(BitString::DEFAULT_BYTE_SIZE, $propertySize->getValue($persister));
        static::assertEquals(BitString::DEFAULT_BYTE_SIZE, strlen($propertyBytes->getValue($persister)));
    }

    #[Test]
    public function setBit(): void
    {
        $persister = new BitString();
        $persister->set(100);
        static::assertEquals(1, $persister->get(100));

        $allNotSetBitsAreOff = true;

        for ($i = 0; $i < BitString::DEFAULT_BYTE_SIZE * 8; $i++) {
            if ($i == 100) {
                continue;
            }
            $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
        }

        static::assertTrue($allNotSetBitsAreOff);

    }

    #[Test]
    public function unsetBit(): void
    {
        $persister = new BitString();
        $persister->set(100);
        static::assertEquals(1, $persister->get(100));
        $persister->unset(99);
        static::assertEquals(1, $persister->get(100));
        $persister->unset(100);
        static::assertEquals(0, $persister->get(100));
    }

    #[Test]
    public function bitIsNotSet(): void
    {
        $persister = new BitString();
        $allNotSetBitsAreOff = true;

        for ($i = 0; $i < BitString::DEFAULT_BYTE_SIZE * 8; $i++) {
            $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
        }

        static::assertTrue($allNotSetBitsAreOff);
    }

    #[Test]
    public function setNegativeBit(): void
    {
        $this->expectException(\LogicException::class);

        $persister = new BitString();
        $persister->set(-1);
    }

    #[Test]
    public function getWrongBitValue(): void
    {
        $this->expectException(\TypeError::class);

        $persister = new BitString();
        $persister->set('test');
    }

    #[Test]
    public function setBits(): void
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);

        foreach ($bits as $bit) {
            static::assertEquals(1, $persister->get($bit));
        }
    }

    #[Test]
    public function unsetBits(): void
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);
        $persister->unsetBulk($bits);

        foreach ($bits as $bit) {
            static::assertEquals(0, $persister->get($bit));
        }
    }

    #[Test]
    public function reset(): void
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);
        $persister->reset();

        foreach ($bits as $bit) {
            static::assertEquals(0, $persister->get($bit));
        }
    }

    #[Test]
    public function getBits(): void
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);

        static::assertEquals([1, 1, 1, 1, 0], $persister->getBulk(array_merge($bits, [512])));
    }

    #[Test]
    public function increaseSize(): void
    {
        $persister = new BitString();

        $class = new \ReflectionClass(BitString::class);
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");

        $bit = BitString::DEFAULT_BYTE_SIZE * 8 * 3 + 2;
        $increasedSize =  BitString::DEFAULT_BYTE_SIZE * 3 + BitString::DEFAULT_BYTE_SIZE;

        $persister->set($bit);
        $allNotSetBitsAreOff = true;
         for ($i = 0; $i < $increasedSize; $i++) {
             if ($i == $bit) {
                 continue;
             }
             $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
         }


        static::assertTrue($allNotSetBitsAreOff);
        static::assertEquals($increasedSize, $propertySize->getValue($persister));
        static::assertEquals($increasedSize, strlen($propertyBytes->getValue($persister)));
    }
}
