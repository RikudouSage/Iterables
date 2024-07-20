<?php

namespace Rikudou\Tests\Iterables;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\DataProvider;
use Rikudou\Iterables\Iterables;
use PHPUnit\Framework\TestCase;
use Traversable;

class IterablesTest extends TestCase
{
    #[DataProvider('mapData')]
    public function testMap(?callable $callback, iterable $iterable, iterable ...$iterables): void
    {
        $array = $this->toArray($iterable);
        $arrays = array_map(fn (iterable $iterable) => $this->toArray($iterable), $iterables);

        $this->assertSame(
            array_map($callback, $array, ...$arrays),
            $this->toArray(Iterables::map($callback, $iterable, ...$iterables)),
        );
    }

    #[DataProvider('filterData')]
    public function testFilter(iterable $iterable, ?callable $callback, int $mode = 0): void
    {
        $array = $this->toArray($iterable);

        $this->assertSame(
            array_filter($array, $callback, $mode),
            $this->toArray(Iterables::filter($iterable, $callback, $mode)),
        );
    }

    #[DataProvider('containsData')]
    public function testContains(mixed $needle, iterable $iterable, bool $strict): void
    {
        $this->assertSame(
            in_array($needle, $this->toArray($iterable), $strict),
            Iterables::contains($needle, $iterable, $strict),
        );
    }

    #[DataProvider('diffData')]
    public function testDiff(iterable $iterable, iterable ...$iterables): void
    {
        $array = $this->toArray($iterable);
        $arrays = array_map(fn (iterable $iterable) => $this->toArray($iterable), $iterables);

        $this->assertSame(
            array_diff($array, ...$arrays),
            $this->toArray(Iterables::diff($iterable, ...$iterables)),
        );
    }

    #[DataProvider('firstValueData')]
    public function testFirstValue(iterable $iterable): void
    {
        $phpFunction = static fn (array $array) => $array[array_key_first($array)] ?? null;

        $this->assertSame(
            $phpFunction($this->toArray($iterable)),
            Iterables::firstValue($iterable),
        );
    }

    #[DataProvider('countData')]
    public function testCount(iterable $iterable): void
    {
        $this->assertSame(
            count($this->toArray($iterable)),
            Iterables::count($iterable),
        );
    }

    #[DataProvider('findData')]
    public function testFind(iterable $iterable, callable $callback): void
    {
        $this->assertSame(
            array_find($this->toArray($iterable), $callback),
            Iterables::find($iterable, $callback),
        );
    }

    public function testCountCountable(): void
    {
        // real length is zero, but countable interface is implemented and it should take precedence
        $countable = new class () implements IteratorAggregate, Countable {
            public function getIterator(): Traversable
            {
                return new ArrayIterator([]);
            }

            public function count(): int
            {
                return 6;
            }
        };
        $this->assertSame(6, Iterables::count($countable));
    }

    public static function countData(): iterable
    {
        yield [[1, 2, 3]];

        yield [new class () implements IteratorAggregate {
            public function getIterator(): Traversable
            {
                return new ArrayIterator([1, 2]);
            }
        }];

        yield [new class () implements IteratorAggregate, Countable {
            public function getIterator(): Traversable
            {
                return new ArrayIterator([1]);
            }

            public function count(): int
            {
                return 1;
            }
        }];
    }

    public static function mapData(): iterable
    {
        yield [null, [1]]; // nothing should change

        yield [null, [1], [2], [3]]; // zips array to [1, 2, 3]

        yield [static fn (int $number) => $number * 2, [1]]; // should return [2]

        yield [static fn (int $number) => $number * 2, [1, 2, 3]]; // should return [2, 4, 6]

        yield [
            static fn (int $number1, int $number2, int $number3) => $number1 + $number2 + $number3,
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]; // should return [12, 15, 18]

        yield [
            null,
            [1, 2, 3],
            ['one', 'two', 'three'],
        ]; // should return [[1, 'one'], [2, 'two'], [3, 'three']]

        yield [
            static fn (int $value) => $value ** 2,
            ['key1' => 5, 'key2' => 10],
        ]; // should return ['key1' => 25, 'key2' => 100]

        yield [
            static fn (int $value) => $value ** 2,
            ['key1' => 5, 'key2' => 10],
            ['key3' => 5, 'key4' => 10],
        ]; // should return [25, 100]

        yield [
            static fn (int $value) => $value ** 2,
            ['key1' => 5, 'key2' => 10],
            ['key1' => 5, 'key2' => 10],
        ]; // should return [25, 100]
    }

    public static function filterData(): iterable
    {
        yield [
            [0, 1, 2],
            null,
        ]; // should return [1, 2]

        yield [
            [0, '', false, 0.0, -0.0, '0', null, []],
            null,
        ]; // should return []

        yield [
            [0, 1, 2],
            null,
            ARRAY_FILTER_USE_KEY,
        ]; // should return [1, 2]

        yield [
            [0, 1, 2],
            null,
            ARRAY_FILTER_USE_BOTH,
        ]; // should return [1, 2]

        yield [
            [0, 1, 2],
            static fn (int $number) => $number !== 1,
        ]; // should return [0, 2]

        yield [
            [3, 4, 5],
            static fn (int $key) => $key !== 1,
            ARRAY_FILTER_USE_KEY,
        ]; // should return [3, 5]

        yield [
            [3, 0, 2],
            static fn (int $value, int $key) => $value >= $key,
            ARRAY_FILTER_USE_BOTH,
        ]; // should return [3, 2]
    }

    public static function containsData(): iterable
    {
        yield [
            1,
            [1, 2],
            true,
        ]; // true

        yield [
            '1',
            [1, 2],
            true,
        ]; // false

        yield [
            '1',
            [1, 2],
            false,
        ]; // true
    }

    public static function diffData(): iterable
    {
        yield [[1, 2, 3]]; // [1, 2, 3]

        yield [[]]; // []

        yield [
            [1, 2, 3],
            [2],
        ]; // [1, 3]

        yield [
            [1, 2, 3],
            [2],
            [3],
            [1, 2],
        ]; // []

        yield [
            [],
            [1, 2],
        ]; // []
    }

    public static function firstValueData(): iterable
    {
        yield [[1, 2, 3]]; // 1

        yield [[]];
    }

    public static function findData(): iterable
    {
        yield [[1, 2, 3], static fn (int $number) => $number === 0];

        yield [[1, 2, 3], static fn (int $number) => $number === 1];

        yield [[1, 2, 3], static fn (int $number) => $number === 2];

        yield [[1, 2, 3], static fn (int $number) => $number === 3];

        yield [[1, 2, 3], static fn (int $number) => $number === 4];

        yield [[1, 2, 3], static fn (int $number, int $key) => $key === 0];

        yield [[1, 2, 3], static fn (int $number, int $key) => $key === 3];

        yield [[1, 2, 3], static fn (string $number) => $number === '2'];

        yield [new RewindableGenerator(static function () {
            yield 1;

            yield 2;

            yield 3;
        }), static fn (int $number) => $number === 2];
    }

    private function toArray(iterable $iterable): array
    {
        return is_array($iterable) ? $iterable : iterator_to_array($iterable);
    }
}
