<?php

namespace Rikudou\Tests\Iterables;

use PHPUnit\Framework\TestCase;
use Rikudou\Iterables\RewindableGenerator;

class RewindableGeneratorTest extends TestCase
{
    public function testIterator(): void
    {
        $calledCount = 0;

        $rawGeneratorFactory = static function () use (&$calledCount) {
            yield from [1, 2, 3];
            ++$calledCount;
        };

        $instance = new RewindableGenerator($rawGeneratorFactory);
        $this->assertSame(0, $calledCount); // initial check
        [...$instance];
        $this->assertSame(1, $calledCount);
        [...$instance];
        $this->assertSame(2, $calledCount);
    }

    public function testCount(): void
    {
        $calledCount = 0;
        $rawGeneratorFactory = static function () use (&$calledCount) {
            yield from [1, 2, 3];
            ++$calledCount;
        };

        $instance = new RewindableGenerator($rawGeneratorFactory);
        $this->assertCount(3, $instance);
        $this->assertSame(1, $calledCount);
        $this->assertCount(3, $instance);
        $this->assertSame(2, $calledCount);
    }
}
