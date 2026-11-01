<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\DynamicBloomFilter;
use RocketLabs\BloomFilter\Hash\Hash;
use RocketLabs\BloomFilter\Persist\BitPersister;

class DynamicBloomFilterTest extends TestCase
{
    #[Test]
    public function addToDynamicFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createStub(Hash::class);
        $hash->method('generate')->willReturn('2');

        $setBulkCalls = [];

        $persister->expects($this->exactly(10))
            ->method('setBulk')
            ->willReturnCallback(function (array $bits) use (&$setBulkCalls): int {
                $setBulkCalls[] = $bits;

                return 1;
            });

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for ($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        static::assertSame(
            [
                [2, 2, 2],
                [2, 2, 2],
                [16, 16, 16],
                [16, 16, 16],
                [16, 16, 16],
                [30, 30, 30],
                [30, 30, 30],
                [30, 30, 30],
                [44, 44, 44],
                [44, 44, 44],
            ],
            $setBulkCalls
        );
    }

    #[Test]
    public function existsInFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createStub(Hash::class);
        $hash->method('generate')->willReturn('2');

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for ($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $getBulkCalls = [];

        $persister->expects($this->exactly(2))
            ->method('getBulk')
            ->willReturnCallback(function (array $bits) use (&$getBulkCalls): array {
                $getBulkCalls[] = $bits;

                return count($getBulkCalls) === 1 ? [1, 0, 1] : [1, 1, 1];
            });

        static::assertTrue($filter->has('testString'));
        static::assertSame([[2, 2, 2], [16, 16, 16]], $getBulkCalls);
    }

    #[Test]
    public function suspendRestoreFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createStub(Hash::class);
        $hash->method('generate')->willReturn('2');

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for ($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $getBulkCalls = [];

        $persister->expects($this->exactly(2))
            ->method('getBulk')
            ->willReturnCallback(function (array $bits) use (&$getBulkCalls): array {
                $getBulkCalls[] = $bits;

                return count($getBulkCalls) === 1 ? [1, 0, 1] : [1, 1, 1];
            });

        $memento = $filter->saveState();

        $restoredFilter = new DynamicBloomFilter($persister, $hash);
        $restoredFilter->restoreState($memento);

        static::assertTrue($restoredFilter->has('testString'));
        static::assertSame([[2, 2, 2], [16, 16, 16]], $getBulkCalls);
    }

    #[Test]
    public function doesNotExistsInFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createStub(Hash::class);
        $hash->method('generate')->willReturn('2');

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for ($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $getBulkCalls = [];

        $persister->expects($this->exactly(4))
            ->method('getBulk')
            ->willReturnCallback(function (array $bits) use (&$getBulkCalls): array {
                $getBulkCalls[] = $bits;

                $results = [
                    [1, 0, 1],
                    [1, 1, 0],
                    [1, 1, 0],
                    [1, 1, 0],
                ];

                return $results[count($getBulkCalls) - 1];
            });

        static::assertFalse($filter->has('testString'));
        static::assertSame(
            [[2, 2, 2], [16, 16, 16], [30, 30, 30], [44, 44, 44]],
            $getBulkCalls
        );
    }
}
