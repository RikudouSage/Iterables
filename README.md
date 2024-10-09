A simple set of functions that reimplement the existing php array functions, but use Generators to consume as little
memory as possible, and accept any iterable, not just array.

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
- `Iterables::contains()` -> `in_array()` - unlike the built-in version, this is strict by default
- `Iterables::firstValue()` -> equivalent of `$array[array_key_first($array)]`
- `Iterables::count()` -> `count()`
- `Iterables::find()` -> `array_find()`
- `Iterables::findKey()` -> `array_find_key()`
- `Iterables::any()` -> `array_any()`
- `Iterables::all()` -> `array_all()`
- `Iterables::combine()` -> `array_combine()`
