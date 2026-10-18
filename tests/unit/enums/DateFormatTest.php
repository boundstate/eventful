<?php

use boundstate\eventful\enums\DateFormat;
use boundstate\eventful\enums\FormatLength;

describe('parse', function () {
    it('returns the format for a value', function () {
        expect(DateFormat::parse('mediumDate'))->toBe(DateFormat::MEDIUM_DATE)
            ->and(DateFormat::parse(DateFormat::LONG))->toBe(DateFormat::LONG);
    });

    it('throws an exception for an invalid value', function () {
        DateFormat::parse('America/Toronto');
    })->throws(InvalidArgumentException::class, 'Use one of: medium, long, mediumDate, longDate, mediumTime, longTime.');
});

it('returns its length and whether it has dates and times', function (DateFormat $format, array $expected) {
    expect([$format->length(), $format->hasDates(), $format->hasTimes()])->toBe($expected);
})->with([
    [DateFormat::MEDIUM, [FormatLength::MEDIUM, true, true]],
    [DateFormat::LONG, [FormatLength::LONG, true, true]],
    [DateFormat::MEDIUM_DATE, [FormatLength::MEDIUM, true, false]],
    [DateFormat::LONG_DATE, [FormatLength::LONG, true, false]],
    [DateFormat::MEDIUM_TIME, [FormatLength::MEDIUM, false, true]],
    [DateFormat::LONG_TIME, [FormatLength::LONG, false, true]],
]);
