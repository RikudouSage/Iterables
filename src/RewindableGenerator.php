<?php

namespace Rikudou\Iterables;

use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 */
final readonly class RewindableGenerator implements IteratorAggregate, Countable
{
    /**
     * @param Closure(): Generator<TKey, TValue> $generator
     */
    public function __construct(
        private Closure $generator,
    ) {
    }

    public function getIterator(): Traversable
    {
        yield from ($this->generator)();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this as $ignored) {
            ++$count;
        }

        return $count;
    }
}
