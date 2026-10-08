<?php

use boundstate\eventful\enums\Color;

it('returns the color at the given index', function (int $index, Color $expected): void {
    expect(Color::at($index))->toBe($expected->value);
})->with([
    'first' => [0, Color::BEETROOT],
    'second' => [1, Color::TANGERINE],
    'last' => [23, Color::BIRCH],
]);

it('wraps around once the palette is exhausted', function (): void {
    $count = count(Color::cases());

    expect(Color::at($count))->toBe(Color::at(0))
        ->and(Color::at($count * 3 + 5))->toBe(Color::at(5));
});
