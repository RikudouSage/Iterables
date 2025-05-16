A simple set of functions that reimplement PHP's built-in array functions but optimize memory usage by using Generators.
These functions also accept any iterable, not just arrays, making them more flexible when working with large datasets 
or data streams.

So, these two are equivalent:

```php
<?php

use Rikudou\Iterables\Iterables;

$array = [1, 2, 3];

// built-in array_map
$mapped = array_map(fn (int $number) => $number ** 2, $array);
foreach ($mapped as $number) {
    echo $number, PHP_EOL;
}

$mapped = Iterables::map(fn (int $number) => $number ** 2, $array);
foreach ($mapped as $number) {
    echo $number, PHP_EOL;
}
```

The difference is mainly with other iterables:

```php
<?php

use Rikudou\Iterables\Iterables;

// an iterable generator
$generator = function () {
    yield 1;
    yield 2;
    yield 3;
};

// array_map only works with an array, so we need to convert it to array first
$mapped = array_map(fn (int $number) => $number ** 2, iterator_to_array($generator()));

// the library version works directly with iterables
$mapped = Iterables::map(fn (int $number) => $number ** 2, $generator());
```

In the above case there's not really much difference 
(except that you can feed results from methods returning `iterable` directly and avoid unnecessary code),
but imagine there's a billion rows instead of 3. And imagine it's a complex object from the database, not an integer.

Note that some of the functions need to traverse the whole iterable, so you don't gain any memory advantages, but
you can still feed any iterable into it directly, which is nice.

### Function list

- `Iterables::map()` -> `array_map()`
- `Iterables::filter()` -> `array_filter()`
- `Iterables::diff()` -> `array_diff()`
  - requires full traversal of the iterable
- `Iterables::contains()` -> `in_array()` 
  - unlike the built-in version, this is strict by default
  - can potentially traverse the whole iterable, until a match is found
- `Iterables::firstValue()` -> equivalent of `$array[array_key_first($array)]`
- `Iterables::count()` -> `count()`
  - if the iterable does not implement `Countable`, requires full traversal
- `Iterables::find()` -> `array_find()`
  - can potentially traverse the whole iterable, until a match is found
- `Iterables::findKey()` -> `array_find_key()`
  - can potentially traverse the whole iterable, until a match is found
- `Iterables::any()` -> `array_any()`
  - can potentially traverse the whole iterable, until a match is found
- `Iterables::all()` -> `array_all()`
  - can potentially traverse the whole iterable, unless any item does not match the callback earlier
- `Iterables::combine()` -> `array_combine()`
- `Iterables::zip()` -> no php equivalent, simply yields from all input iterables one by one
- `Iterables::reverse()` -> `array_reverse()`
  - requires full traversal of the iterable
- `Iterables::changeKeyCase()` -> `array_change_key_case()`

## Rewindable and cacheable generators

There are two helper objects, `RewindableGenerator` and `CacheableGenerator`. Both solve the issue of iterating over
a `Generator` multiple times in a different way.

`RewindableGenerator` iterates over the generator over and over, while `CacheableGenerator` only traverses it once
and afterwards caches the values in memory.

`RewindableGenerator` is more useful when working with large datasets, because it only ever holds one item in memory.
It accepts a callable argument which will be called every time the original generator is exhausted.

`CacheableGenerator` accepts the `Generator` object directly and is useful if you don't care about all the values being
held in memory at once. It's more memory-efficient if you potentially don't iterate over the whole object.

### Examples

#### Rewindable generator

```php
<?php

use Rikudou\Iterables\Iterables;
use Rikudou\Iterables\RewindableGenerator;

$keys = ['a', 'b', 'c'];
$values = [1, 2, 3];

// RewindableGenerator requires a factory (callable) because Generator instances themselves cannot be rewound.
$generator = new RewindableGenerator(fn () => Iterables::combine($keys, $values));

// let's iterate over the generator twice!
for ($i = 0; $i < 2; ++$i) {
    foreach ($generator as $key => $value) {
        echo "{$key} => {$value},", PHP_EOL;
    }
}
```

When ran, this is the result:

```
a => 1,
b => 2,
c => 3,
a => 1,
b => 2,
c => 3,
```

#### Cacheable generator

> Note: The CacheableGenerator stores data either using the ds extension or an internal fallback class. 
> While the fallback class works, it is not as optimized. For better performance, 
> it's highly recommended to install the ds extension. 
> No additional configuration is needed—the generator will automatically use the ds extension if it's available.

```php
<?php

use Rikudou\Iterables\Iterables;
use Rikudou\Iterables\CacheableGenerator;

$keys = ['a', 'b', 'c'];
$values = [1, 2, 3];

// here we feed the raw generator directly as a parameter
$generator = new CacheableGenerator(Iterables::combine($keys, $values));

// let's iterate over the generator twice!
for ($i = 0; $i < 2; ++$i) {
    foreach ($generator as $key => $value) {
        echo "{$key} => {$value},", PHP_EOL;
    }
}
```

When ran, this is the result:

```
a => 1,
b => 2,
c => 3,
a => 1,
b => 2,
c => 3,
```
