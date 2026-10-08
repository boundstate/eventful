<?php

use boundstate\eventful\translators\Translator;

it('converts recurr placeholders to craft placeholders', function (): void {
    expect((new Translator)->trans('every %count% weeks', ['count' => 2]))->toBe('every 2 weeks')
        ->and((new Translator('de'))->trans('every %count% weeks', ['count' => 2]))->toBe('alle 2 Wochen');
});

it('translates intentionally empty messages to an empty string', function (): void {
    expect((new Translator('de'))->trans('the_for_weekday'))->toBe('');
});

it('returns localized day and month names', function (): void {
    $translator = new Translator('fr');

    expect($translator->trans('day_names'))->toHaveCount(7)
        ->and($translator->trans('day_names')[0])->toBe('dimanche')
        ->and($translator->trans('month_names'))->toHaveCount(12)
        ->and($translator->trans('month_names')[2])->toBe('mars');
});

it('formats a day of a month', function (): void {
    expect((new Translator)->trans('day_month', ['month' => 3, 'day' => 5]))->toBe('March 5');
});

describe('day_date', function (): void {
    // August 2, 2026 03:59:59 UTC (August 1 in Toronto)
    $timestamp = (new DateTime('2026-08-01 23:59:59', new DateTimeZone('America/Toronto')))->getTimestamp();

    it('formats the date in the given timezone', function () use ($timestamp): void {
        expect((new Translator(timezone: 'America/Toronto'))->trans('day_date', ['date' => $timestamp]))
            ->toBe('August 1, 2026');
    });

    it('formats the date in the system timezone by default', function () use ($timestamp): void {
        expect((new Translator)->trans('day_date', ['date' => $timestamp]))->toBe('August 2, 2026');
    });
});

describe('ordinal_number', function (): void {
    it('formats positive ordinals', function (int $number, string $expected): void {
        expect((new Translator)->trans('ordinal_number', ['number' => $number]))->toBe($expected);
    })->with([
        [1, '1st'],
        [2, '2nd'],
        [3, '3rd'],
        [4, '4th'],
        [11, '11th'],
        [22, '22nd'],
    ]);

    it('formats negative ordinals', function (int $number, string $expected): void {
        expect((new Translator)->trans('ordinal_number', ['number' => $number]))->toBe($expected);
    })->with([
        [-1, 'last'],
        [-2, '2nd to the last'],
    ]);

    it('adds a day suffix when negatives are present', function (int $number, string $expected): void {
        expect((new Translator)->trans('ordinal_number', ['number' => $number, 'has_negatives' => true]))
            ->toBe($expected);
    })->with([
        [-1, 'last day'],
        [-3, '3rd to the last day'],
        [5, '5th day'],
    ]);

    it('uses the translator language', function (): void {
        expect((new Translator('fr'))->trans('ordinal_number', ['number' => 1]))->toBe('1er');
    });
});
