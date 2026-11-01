<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Persist\BitRedis;

class BitRedisTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not available.');
        }
    }

    #[Test]
    public function setBit(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with(BitRedis::DEFAULT_KEY, 100, true);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set(100);
    }

    #[Test]
    public function unsetBit(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with(BitRedis::DEFAULT_KEY, 100, false);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->unset(100);
    }

    #[Test]
    public function getBit(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('getBit')
            ->willReturn(0)
            ->with(BitRedis::DEFAULT_KEY, 100);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        static::assertEquals(0, $persister->get(100));
    }

    #[Test]
    public function setNegativeBit(): void
    {
        $this->expectException(\LogicException::class);

        $redisMock = $this->createStub(\Redis::class);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set(-1);
    }

    #[Test]
    public function getNegativeBit(): void
    {
        $this->expectException(\LogicException::class);

        $redisMock = $this->createStub(\Redis::class);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set(-1);
        $persister->set(-1);

    }

    #[Test]
    public function getWrongBitValue(): void
    {
        $this->expectException(\TypeError::class);

        $redisMock = $this->createStub(\Redis::class);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set('test');
    }

    #[Test]
    public function setBits(): void
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->createMock(\Redis::class);
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $setBitCalls = [];

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->willReturnCallback(function (...$arguments) use (&$setBitCalls): int {
                $setBitCalls[] = $arguments;

                return 1;
            });

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn([]);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->setBulk($bits);

        static::assertSame(
            [
                [BitRedis::DEFAULT_KEY, $bits[0], true],
                [BitRedis::DEFAULT_KEY, $bits[1], true],
                [BitRedis::DEFAULT_KEY, $bits[2], true],
            ],
            $setBitCalls
        );
    }

    #[Test]
    public function unsetBits(): void
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->createMock(\Redis::class);
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $setBitCalls = [];

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->willReturnCallback(function (...$arguments) use (&$setBitCalls): int {
                $setBitCalls[] = $arguments;

                return 1;
            });

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn([]);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->unsetBulk($bits);

        static::assertSame(
            [
                [BitRedis::DEFAULT_KEY, $bits[0], false],
                [BitRedis::DEFAULT_KEY, $bits[1], false],
                [BitRedis::DEFAULT_KEY, $bits[2], false],
            ],
            $setBitCalls
        );
    }

    #[Test]
    public function reset(): void
    {
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('del')
            ->with(BitRedis::DEFAULT_KEY);

        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->reset();
    }

    #[Test]
    public function getBits(): void
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->createMock(\Redis::class);
        $redisMock = $this->createMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $getBitCalls = [];

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('getBit')
            ->willReturnCallback(function (...$arguments) use (&$getBitCalls): int {
                $getBitCalls[] = $arguments;

                return 1;
            });

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn([1]);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->getBulk($bits);

        static::assertSame(
            [
                [BitRedis::DEFAULT_KEY, $bits[0]],
                [BitRedis::DEFAULT_KEY, $bits[1]],
                [BitRedis::DEFAULT_KEY, $bits[2]],
            ],
            $getBitCalls
        );
    }
}
