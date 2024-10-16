<?php

namespace Rikudou\Iterables;

use Countable;
use Ds\Map;
use Generator;
use IteratorAggregate;

/**
 * @template TKey
 * @template TValue
 *
 * @template-implements IteratorAggregate<TKey, TValue>
 */
final class CacheableGenerator implements IteratorAggregate, Countable
{
    private int $count = 0;

    private bool $exhausted = false;

    /**
     * @var Map<TKey, TValue>|ArbitraryDataMap<TKey, TValue>
     */
    private iterable $cache;

    /**
     * @param Generator<TKey, TValue> $generator
     */
    public function __construct(
        private readonly Generator $generator,
    ) {
        // @codeCoverageIgnoreStart
        if (class_exists(Map::class)) {
            $this->cache = new Map();
        } else {
            $this->cache = new ArbitraryDataMap();
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return Generator<TKey, TValue>
     */
    public function getIterator(): Generator
    {
        if (!$this->exhausted) {
            foreach ($this->generator as $key => $value) {
                ++$this->count;

                yield $key => $value;
                $this->cache[$key] = $value;
            }
            $this->exhausted = true;
        } else {
            yield from $this->cache;
        }
    }

    public function count(): int
    {
        if (!$this->exhausted) {
            [...$this->getIterator()];
        }

        return $this->count;
    }
}
