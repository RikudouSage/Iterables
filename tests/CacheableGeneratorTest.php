<?php

namespace Rikudou\Tests\Iterables;

use Rikudou\Iterables\CacheableGenerator;
use PHPUnit\Framework\TestCase;

class CacheableGeneratorTest extends TestCase
{
    public function testIterator(): void
    {
        $calledCount = 0;

        $rawGeneratorFactory = static function () use (&$calledCount) {
            yield from [1, 2, 3];
            ++$calledCount;
        };

        $instance = new CacheableGenerator($rawGeneratorFactory());
        $this->assertSame(0, $calledCount); // initial check
        [...$instance];
        $this->assertSame(1, $calledCount);
        [...$instance];
        $this->assertSame(1, $calledCount);
    }
}
