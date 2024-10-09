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
    public function testConstructorAndIterator(): void
    {
        $instance = new ArbitraryDataMap([1, 2, 3]);
        $this->assertSame([1, 2, 3], [...$instance]);

        $instance = new ArbitraryDataMap(['a' => 'b', 'c' => 'd']);
        $this->assertSame(['a' => 'b', 'c' => 'd'], [...$instance]);

        $objectStorage = new SplObjectStorage();
        $objectStorage[new stdClass()] = 'test';
        $instance = new ArbitraryDataMap($objectStorage);
        $this->assertCount(1, $instance);
        $this->assertInstanceOf(stdClass::class, $instance[0]);
    }

    public function testArrayAccess(): void
    {
        $instance = new ArbitraryDataMap();
        $this->assertFalse(isset($instance['hello']));

        $instance['hello'] = 'world';
        $this->assertTrue(isset($instance['hello']));
        $this->assertSame('world', $instance['hello']);

        $instance['hello'] = 'world2';
        $this->assertSame('world2', $instance['hello']);

        unset($instance['hello']);
        $this->assertFalse(isset($instance['hello']));
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
            $this->expectExceptionMessage('Invalid offset: value omitted because it cannot be cast to a string');
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

        yield [new class () {
            public function __toString(): string
            {
                return 'test';
            }
        }, true];

        yield [new stdClass(), false];
    }
}
