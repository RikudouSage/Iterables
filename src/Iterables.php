<?php

namespace Rikudou\Iterables;

use Generator;
use JetBrains\PhpStorm\ExpectedValues;
use ValueError;

final readonly class Iterables
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @template InputType
     * @template OutputType
     *
     * @param null|(callable(InputType $item): OutputType) $callback
     * @param iterable<InputType>                          $iterable
     * @param iterable<InputType>                          ...$iterables
     *
     * @return ($callback is null ? Generator<InputType> : Generator<OutputType>)
     */
    public static function map(?callable $callback, iterable $iterable, iterable ...$iterables): Generator
    {
        $iterableGenerator = self::toGenerator($iterable);

        if ($callback === null && !count($iterables)) {
            yield from $iterableGenerator;

            return;
        }
        $callback ??= static function (mixed ...$values) {
            return [...$values];
        };

        /** @var array<Generator<InputType>> $iterablesGenerators */
        $iterablesGenerators = [];
        foreach ($iterables as $tmpIterable) {
            $iterablesGenerators[] = self::toGenerator($tmpIterable);
        }

        $hasOtherIterables = count($iterablesGenerators) > 0;

        $incrementalKey = 0;
        foreach ($iterableGenerator as $key => $value) {
            $valuesToPass = [$value];
            foreach ($iterablesGenerators as $generator) {
                $valuesToPass[] = $generator->current();
                $generator->next();
            }

            $key = $hasOtherIterables ? $incrementalKey++ : $key;

            yield $key => $callback(...$valuesToPass); // @phpstan-ignore-line
        }
    }

    /**
     * @template InputType
     * @template InputKey
     *
     * @param iterable<InputKey, InputType> $iterable
     *
     * @return Generator<InputKey, InputType>
     */
    public static function filter(
        iterable $iterable,
        ?callable $callback = null,
        #[ExpectedValues(values: [ARRAY_FILTER_USE_BOTH, ARRAY_FILTER_USE_KEY, 0])] int $mode = 0
    ): Generator {
        $callback ??= static fn (mixed $item) => !empty($item);
        foreach ($iterable as $key => $value) {
            $toPass = $mode === 0
                ? [$value]
                : (
                    $mode === ARRAY_FILTER_USE_KEY
                    ? [$key]
                    : [$value, $key]
                );

            if ($callback(...$toPass)) {
                yield $key => $value;
            }
        }
    }

    /**
     * @template InputType
     *
     * @param iterable<InputType> $iterable
     * @param iterable<InputType> ...$iterables
     *
     * @return Generator<InputType>
     */
    public static function diff(
        iterable $iterable,
        iterable ...$iterables,
    ): Generator {
        $iterableGenerator = self::toGenerator($iterable);

        if (!count($iterables)) {
            yield from $iterableGenerator;

            return;
        }

        $allValues = self::toGeneratorFromCallable(static function () use (&$iterables) {
            foreach ($iterables as $iterable) {
                foreach ($iterable as $value) {
                    yield $value;
                }
            }
        });

        $allValuesArray = null;
        foreach ($iterableGenerator as $key => $value) {
            $allValuesArray ??= [...$allValues];
            if (!self::contains($value, $allValuesArray)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Contrary to the default in_array, this is strict by default
     *
     * @param iterable<mixed> $iterable
     */
    public static function contains(mixed $needle, iterable $iterable, bool $strict = true): bool
    {
        $compareFn = $strict
            ? static fn (mixed $a, mixed $b) => $a === $b
            : static fn (mixed $a, mixed $b) => $a == $b
        ;
        foreach ($iterable as $value) {
            if ($compareFn($needle, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template InputType
     *
     * @param iterable<InputType> $iterable
     *
     * @return InputType|null
     */
    public static function firstValue(iterable $iterable): mixed
    {
        $iterable = self::toGenerator($iterable);

        return $iterable->current();
    }

    /**
     * @param iterable<mixed> $iterable
     */
    public static function count(iterable $iterable): int
    {
        if (is_countable($iterable)) {
            return count($iterable);
        }

        $count = 0;
        foreach ($iterable as $ignored) {
            ++$count;
        }

        return $count;
    }

    /**
     * @template InputTypeKey
     * @template InputTypeValue
     *
     * @param iterable<InputTypeKey, InputTypeValue>       $iterable
     * @param callable(InputTypeValue, InputTypeKey): bool $callback
     *
     * @return InputTypeValue|null
     */
    public static function find(iterable $iterable, callable $callback): mixed
    {
        foreach ($iterable as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @template InputTypeKey
     * @template InputTypeValue
     *
     * @param iterable<InputTypeKey, InputTypeValue>       $iterable
     * @param callable(InputTypeValue, InputTypeKey): bool $callback
     *
     * @return InputTypeKey|null
     */
    public static function findKey(iterable $iterable, callable $callback): mixed
    {
        foreach ($iterable as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @template InputTypeKey
     * @template InputTypeValue
     *
     * @param iterable<InputTypeKey, InputTypeValue>       $iterable
     * @param callable(InputTypeValue, InputTypeKey): bool $callback
     */
    public static function any(iterable $iterable, callable $callback): bool
    {
        foreach ($iterable as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template InputTypeKey
     * @template InputTypeValue
     *
     * @param iterable<InputTypeKey, InputTypeValue>       $iterable
     * @param callable(InputTypeValue, InputTypeKey): bool $callback
     */
    public static function all(iterable $iterable, callable $callback): bool
    {
        foreach ($iterable as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @template TKey
     * @template TValue
     * @template TArg
     *
     * @param iterable<TKey, TValue>|object                              $iterable
     * @param callable(TValue, TKey, (TArg is null ? null : TArg)): void $callback
     * @param TArg                                                       $arg
     */
    public static function walk(iterable|object $iterable, callable $callback, mixed $arg = null): true
    {
        if (!is_iterable($iterable)) {
            $iterable = (array) $iterable;
        }
        foreach ($iterable as $key => $value) {
            if ($arg === null) {
                $callback($value, $key, null);
            } else {
                $callback($value, $key, $arg);
            }
        }

        return true;
    }

    /**
     * @template TKey
     * @template TValue
     * @template TPreserveKeys of bool
     *
     * @param iterable<TKey, TValue> $iterable
     * @param TPreserveKeys $preserveKeys
     *
     * @return (TPreserveKeys is false ? iterable<int, TValue> : iterable<TKey, TValue>)
     */
    public static function slice(iterable $iterable, int $offset, ?int $length = null, bool $preserveKeys = false): iterable
    {
        if ($offset < 0) {
            // the offset is negative so we need to traverse the whole thing anyway
            return array_slice([...$iterable], $offset, $length, $preserveKeys);
        }

        $i = 0;
        foreach ($iterable as $key => $value) {
            if ($i < $offset) {
                continue;
            }
            if ($length === 0) {
                break;
            }

            yield ($preserveKeys ? $key : $i) => $value;
            ++$i;
            if ($length !== null) {
                $length -= 1;
            }
        }
    }

    /**
     * @template KeyType
     * @template ValueType
     *
     * @param iterable<KeyType>   $keys
     * @param iterable<ValueType> $values
     *
     * @return iterable<KeyType, ValueType>
     */
    public static function combine(iterable $keys, iterable $values): iterable
    {
        $values = self::toGenerator($values);
        foreach ($keys as $key) {
            if (!$values->valid()) {
                throw new ValueError('Iterables::combine(): Argument #1 ($keys) and argument #2 ($values) must have the same number of elements');
            }
            yield $key => $values->current();
            $values->next();
        }

        if ($values->valid()) {
            throw new ValueError('Iterables::combine(): Argument #1 ($keys) and argument #2 ($values) must have the same number of elements');
        }
    }

    /**
     * @template InputType
     *
     * @param iterable<InputType> $iterable
     *
     * @return Generator<InputType>
     */
    private static function toGenerator(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * @template InputType
     *
     * @param callable(): InputType $callable
     *
     * @return Generator<InputType>
     */
    private static function toGeneratorFromCallable(callable $callable): Generator
    {
        yield from $callable();
    }
}
