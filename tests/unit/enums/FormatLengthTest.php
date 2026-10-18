<?php

use boundstate\eventful\enums\FormatLength;

describe('parse', function () {
    it('returns the length for a value', function () {
        expect(FormatLength::parse('long'))->toBe(FormatLength::LONG)
            ->and(FormatLength::parse(FormatLength::MEDIUM))->toBe(FormatLength::MEDIUM);
    });

    it('throws an exception for an invalid value', function () {
        FormatLength::parse('mediumDate');
    })->throws(InvalidArgumentException::class, 'Use one of: medium, long.');
});
