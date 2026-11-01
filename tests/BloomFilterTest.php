<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\BloomFilter;
use RocketLabs\BloomFilter\Hash\Hash;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Persist\BitPersister;
use RocketLabs\BloomFilter\Persist\BitString;

class BloomFilterTest extends TestCase
{
    #[Test]
    #[DataProvider('createBloomFilterDataProvider')]
    public function createBloomFilter($size, $probability, $expectedHashSize, $expectedBitSize): void
    {
        $persister = new BitString();
        $hash = new Murmur();

        $class = new \ReflectionClass(BloomFilter::class);
        $propertyHashes = $class->getProperty('hashCount');
        $propertyBitSize = $class->getProperty('bitSize');
        $filter = new BloomFilter($persister, $hash);
        $filter->setSize($size)->setFalsePositiveProbability($probability);

        static::assertEquals($expectedHashSize, $propertyHashes->getValue($filter));
        static::assertEquals($expectedBitSize, $propertyBitSize->getValue($filter));
    }

    /**
     * @return array
     */
    public static function createBloomFilterDataProvider(): array
    {
        return [
            'Size: 100, probability: 99.9%' => [
                'size' => 100,
                'probability' => 0.001,
                'expectedHashSize' => 10,
                'expectedBitSize' => 1438
            ],
            'Size: 1000, probability: 99%' => [
                'size' => 1000,
                'probability' => 0.01,
                'expectedHashSize' => 7,
                'expectedBitSize' => 9585
            ],
            'Size: 1000, probability: 99,99%' => [
                'size' => 1000,
                'probability' => 0.0001,
                'expectedHashSize' => 13,
                'expectedBitSize' => 19170
            ],
            'Size: 1000000, probability: 99,99%' => [
                'size' => 1000000,
                'probability' => 0.0001,
                'expectedHashSize' => 13,
                'expectedBitSize' => 19170117 //2.3Mb
            ]
        ];
    }

    #[Test]
    public function addToFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10048');

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $filter = new BloomFilter($persister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->add('testString');
    }

    #[Test]
    public function filterHasNotBeenInitialized(): void
    {
        $this->expectException(\LogicException::class);

        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createMock(Hash::class);
        $hash->expects($this->never())
            ->method('generate');

        $persister->expects($this->never())
            ->method('setBulk');

        $filter = new BloomFilter($persister, $hash);
        $filter->add('testString');
    }

    #[Test]
    public function addBulkFilter(): void
    {
        $persister = $this->createMock(BitPersister::class);
        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(9))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '43', '44', '1', '2', '3', '10001', '10002', '10003');

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]); //calculated bits for hashes

        $filter = new BloomFilter($persister, $hash);
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

        $filterForSet = new BloomFilter($persister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');

        $filterForGet = new BloomFilter($persister, $hash);
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

        $filterForSet = new BloomFilter($persister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');
        $memento = $filterForSet->saveState();

        $filterForGet = new BloomFilter($persister, $hash);
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
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 0, 1])
            ->with([43, 1001, 233]); //calculated bits for hashes

        $hash = $this->createMock(Hash::class);
        $hash->expects($this->exactly(6))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('42', '1000', '10048', '43', '1001', '10049');

        $filterForSet = new BloomFilter($persister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('test String');

        $filterForGet = new BloomFilter($persister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertFalse($filterForGet->has('Not Existing test String'));
    }
}
