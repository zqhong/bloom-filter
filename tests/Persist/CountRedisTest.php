<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Persist\CountRedis;

class CountRedisTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not available.');
        }
    }

    #[Test]
    public function incrementBit(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(1)
            ->with(CountRedis::DEFAULT_KEY, 100, 1);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        self::assertEquals(1, $persister->incrementBit(100));
    }

    #[Test]
    public function decrementBit(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(0)
            ->with(CountRedis::DEFAULT_KEY, 100, -1);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        self::assertEquals(0, $persister->decrementBit(100));
    }

    #[Test]
    public function getBit(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('hGet')
            ->willReturn(10)
            ->with(CountRedis::DEFAULT_KEY, 100);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        self::assertEquals(10, $persister->get(100));
    }

    #[Test]
    public function negativeDecrement(): void
    {
        $this->expectException(\RuntimeException::class);

        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(-1)
            ->with(CountRedis::DEFAULT_KEY, 100, -1);
        $redisMock->expects($this->once())
            ->method('hSet')
            ->willReturn(0)
            ->with(CountRedis::DEFAULT_KEY, 100, 0);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        $persister->decrementBit(100);
    }

    #[Test]
    public function reset(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('del')
            ->willReturn(1)
            ->with(CountRedis::DEFAULT_KEY);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        $persister->reset();
    }
}
