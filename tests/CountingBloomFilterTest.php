<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\CountingBloomFilter;
use RocketLabs\BloomFilter\Hash\Hash;
use RocketLabs\BloomFilter\Persist\BitPersister;
use RocketLabs\BloomFilter\Persist\CountPersister;

class CountingBloomFilterTest extends TestCase
{
    #[Test]
    public function addToFilter(): void
    {
        $bitPersister = $this->createMock(BitPersister::class);

        $bitPersister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $countPersister = $this->createMock(CountPersister::class);
        $countPersister->expects($this->exactly(3))
            ->method('incrementBit')
            ->willReturnOnConsecutiveCalls(42, 1000, 10048);

        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10048');

        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->add('testString');
    }

    #[Test]
    public function deleteFromFilter(): void
    {
        $bitPersister = $this->createMock(BitPersister::class);

        $bitPersister->expects($this->once())
            ->method('unsetBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $countPersister = $this->createMock(CountPersister::class);
        $countPersister->expects($this->exactly(3))
            ->method('decrementBit')
            ->willReturnOnConsecutiveCalls(42, 1000, 232);

        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10048');

        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->delete('testString');
    }

    #[Test]
    public function deleteBulkFilter(): void
    {
        $bitPersister = $this->createMock(BitPersister::class);
        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(9))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '43', '44', '1', '2', '3', '10001', '10002', '10003');

        $bitPersister->expects($this->once())
            ->method('unsetBulk')
            ->willReturn(1)
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]); //calculated bits for hashes

        $countPersister = $this->createMock(CountPersister::class);
        $countPersister->expects($this->exactly(9))
            ->method('decrementBit')
            ->willReturn(1);

        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->deleteBulk(
            ['test String 1',
                'test String 2',
                'test String 3',
            ]
        );
    }

    #[Test]
    public function addBulkFilter(): void
    {
        $bitPersister = $this->createMock(BitPersister::class);
        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(9))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '43', '44', '1', '2', '3', '10001', '10002', '10003');

        $bitPersister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]); //calculated bits for hashes

        $countPersister = $this->createMock(CountPersister::class);
        $countPersister->expects($this->once())
            ->method('incrementBulk')
            ->willReturn([])
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]);

        $filter = new CountingBloomFilter($bitPersister, $countPersister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->addBulk(
            ['test String 1',
            'test String 2',
            'test String 3',
            ]
        );
    }

    #[Test]
    public function existsInFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);

        $hash = $this->createStub(Hash::class);
        $hash->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10001', '42', '1000', '10001');

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 185]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([42, 1000, 185]); //calculated bits for hashes

        $countPersister = $this->createStub(CountPersister::class);

        $filterForSet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');

        $filterForGet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertTrue($filterForGet->has('testString'));
    }

    #[Test]
    public function suspendRestoreFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);

        $hash = $this->createStub(Hash::class);
        $hash->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10001', '42', '1000', '10001');

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 185]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([42, 1000, 185]); //calculated bits for hashes

        $countPersister = $this->createStub(CountPersister::class);

        $filterForSet = new CountingBloomFilter($persister, $countPersister, $hash);

        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');
        $memento = $filterForSet->saveState();

        $filterForGet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForGet->restoreState($memento);
        static::assertTrue($filterForGet->has('testString'));
    }

    #[Test]
    public function DoesNotExistInFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $countPersister = $this->createMock(CountPersister::class);
        $countPersister->expects($this->exactly(3))
            ->method('incrementBit')
            ->willReturnOnConsecutiveCalls(42, 1000, 232);

        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 0, 1])
            ->with([43, 1001, 233]); //calculated bits for hashes

        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(6))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10048', '43', '1001', '10049');

        $filterForSet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('test String');

        $filterForGet = new CountingBloomFilter($persister, $countPersister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertFalse($filterForGet->has('Not Existing test String'));
    }
}
