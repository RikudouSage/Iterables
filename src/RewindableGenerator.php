<?php

namespace Rikudou\Iterables;

use Closure;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 */
final readonly class RewindableGenerator implements IteratorAggregate
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
}
