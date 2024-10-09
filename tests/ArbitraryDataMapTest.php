<?php

namespace Rikudou\Tests\Iterables;

use PHPUnit\Framework\Attributes\DataProvider;
use Rikudou\Iterables\ArbitraryDataMap;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplObjectStorage;
use stdClass;

class ArbitraryDataMapTest extends TestCase
{
    public function testConstructorAndIterator()
    {
        $instance = new ArbitraryDataMap([1, 2, 3]);
        self::assertSame([1, 2, 3], [...$instance]);

        $instance = new ArbitraryDataMap(['a' => 'b', 'c' => 'd']);
        self::assertSame(['a' => 'b', 'c' => 'd'], [...$instance]);

        $objectStorage = new SplObjectStorage();
        $objectStorage[new stdClass()] = 'test';
        $instance = new ArbitraryDataMap($objectStorage);
        self::assertCount(1, $instance);
        self::assertInstanceOf(stdClass::class, $instance[0]);
    }

    public function testArrayAccess()
    {
        $instance = new ArbitraryDataMap();
        self::assertFalse(isset($instance['hello']));

        $instance['hello'] = 'world';
        self::assertTrue(isset($instance['hello']));
        self::assertSame('world', $instance['hello']);

        $instance['hello'] = 'world2';
        self::assertSame('world2', $instance['hello']);

        unset($instance['hello']);
        self::assertFalse(isset($instance['hello']));
        unset($instance['hello2']);

        $this->expectException(RuntimeException::class);
        $instance['test'];
    }

    #[DataProvider('exceptionData')]
    public function testException(mixed $key, bool $stringable): void
    {
        $this->expectException(RuntimeException::class);
        if ($stringable) {
            $this->expectExceptionMessage("Invalid offset: {$key}");
        } else {
            $this->expectExceptionMessage("Invalid offset: value omitted because it cannot be cast to a string");
        }

        $instance = new ArbitraryDataMap();
        $instance[$key];
    }

    public static function exceptionData(): iterable
    {
        yield ['1', true];
        yield [1, true];
        yield [0.5, true];
        yield [null, true];
        yield [new class
        {
            public function __toString(): string
            {
                return 'test';
            }
        }, true];
        yield [new stdClass(), false];
    }
}
