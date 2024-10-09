<?php

namespace Rikudou\Iterables;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use RuntimeException;
use Stringable;
use Traversable;

/**
 * @template TKey
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 *
 * @internal
 */
final class ArbitraryDataMap implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @var array<int, TKey>
     */
    private array $keys = [];

    /**
     * @var array<int, TValue>
     */
    private array $values = [];

    /**
     * @param iterable<TKey, TValue> $map
     */
    public function __construct(
        iterable $map = [],
    ) {
        foreach ($map as $key => $value) {
            $this->keys[] = $key;
            $this->values[] = $value;
        }
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        for ($i = 0, $count = count($this->keys); $i < $count; ++$i) {
            yield $this->keys[$i] => $this->values[$i];
        }
    }

    /**
     * @param TKey $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, $this->keys, true);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        $index = array_search($offset, $this->keys, true);
        if ($index === false) {
            if (is_scalar($offset) || $offset instanceof Stringable) {
                throw new RuntimeException("Invalid offset: {$offset}");
            } else {
                throw new RuntimeException("Invalid offset: value omitted because it cannot be cast to a string");
            }
        }

        return $this->values[$index];
    }

    /**
     * @param TKey $offset
     * @param TValue $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $index = array_search($offset, $this->keys, true);
        if ($index === false) {
            $this->keys[] = $offset;
            $this->values[] = $value;
        } else {
            $this->values[$index] = $value;
        }
    }

    /**
     * @param TKey $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $index = array_search($offset, $this->keys, true);
        if ($index !== false) {
            array_splice($this->keys, $index, 1);
            array_splice($this->values, $index, 1);
        }
    }

    public function count(): int
    {
        return count($this->keys);
    }
}
