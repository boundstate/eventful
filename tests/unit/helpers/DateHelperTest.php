<?php

use boundstate\eventful\helpers\DateHelper;

function torontoDate(string $date): DateTime
{
    return new DateTime($date, new DateTimeZone('America/Toronto'));
}

describe('setTime', function (): void {
    it('combines the date of one value with the time of another', function (): void {
        $result = DateHelper::setTime(torontoDate('2026-03-05 08:15'), torontoDate('2020-01-01 17:45'));

        expect($result->format('Y-m-d H:i'))->toBe('2026-03-05 17:45');
    });

    it('does not mutate the given date', function (): void {
        $date = torontoDate('2026-03-05 08:15');

        DateHelper::setTime($date, torontoDate('2020-01-01 17:45'));

        expect($date->format('H:i'))->toBe('08:15');
    });
});

describe('endOfDay', function (): void {
    it('returns the last microsecond of the day', function (): void {
        $result = DateHelper::endOfDay(torontoDate('2026-03-05'));

        expect($result->format('Y-m-d H:i:s.u'))->toBe('2026-03-05 23:59:59.999999');
    });

    it('does not mutate the given date', function (): void {
        $date = torontoDate('2026-03-05');

        DateHelper::endOfDay($date);

        expect($date->format('Y-m-d H:i'))->toBe('2026-03-05 00:00');
    });
});

describe('formatDate', function (): void {
    it('formats a date', function (string $format, string $expected): void {
        expect(DateHelper::formatDate(torontoDate('2026-03-05 10:00'), $format))->toBe($expected);
    })->with([
        'medium' => ['medium', 'Mar 5, 2026'],
        'long' => ['long', 'March 5, 2026'],
    ]);

    it('formats in the given timezone', function (): void {
        expect(DateHelper::formatDate(torontoDate('2026-03-05 23:30'), timezone: 'Europe/Paris'))
            ->toBe('Mar 6, 2026');
    });
});

describe('formatDateRange', function (): void {
    it('collapses the range', function (string $start, ?string $end, string $format, string $expected): void {
        $endDate = $end ? torontoDate($end) : null;

        expect(DateHelper::formatDateRange(torontoDate($start), $endDate, $format))->toBe($expected);
    })->with([
        'no end date' => ['2026-03-05 10:00', null, 'medium', 'Mar 5, 2026'],
        'same day' => ['2026-03-05 10:00', '2026-03-05 18:00', 'medium', 'Mar 5, 2026'],
        'same month' => ['2026-03-05 10:00', '2026-03-08 10:00', 'medium', 'Mar 5 - 8, 2026'],
        'same year' => ['2026-03-05 10:00', '2026-04-07 10:00', 'medium', 'Mar 5 - Apr 7, 2026'],
        'same year (long)' => ['2026-03-05 10:00', '2026-04-07 10:00', 'long', 'March 5 - April 7, 2026'],
        'different years' => ['2026-12-30 10:00', '2027-01-02 10:00', 'medium', 'Dec 30, 2026 - Jan 2, 2027'],
        'overnight' => ['2026-03-05 23:30', '2026-03-06 00:30', 'medium', 'Mar 5 - 6, 2026'],
        'next day, less than 24 hours' => ['2026-03-05 14:00', '2026-03-06 13:00', 'medium', 'Mar 5 - 6, 2026'],
    ]);

    it('compares dates in the given timezone', function (): void {
        $utc = new DateTimeZone('UTC');

        // Feb 28, 10PM - Mar 3, 10AM in Toronto
        expect(DateHelper::formatDateRange(
            new DateTime('2026-03-01 03:00', $utc),
            new DateTime('2026-03-03 15:00', $utc),
            timezone: 'America/Toronto',
        ))->toBe('Feb 28 - Mar 3, 2026');
    });
});

describe('formatTimeRange', function (): void {
    it('omits minutes on the hour for the medium format', function (): void {
        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:30')))
            ->toBe('10AM – 11:30AM');
    });

    it('always includes minutes for other formats', function (): void {
        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:00'), 'long'))
            ->toBe('10:00AM – 11:00AM');
    });

    it('optionally displays the timezone', function (): void {
        expect(DateHelper::formatTimeRange(
            torontoDate('2026-03-05 10:00'),
            torontoDate('2026-03-05 11:00'),
            displayTimezone: true,
        ))->toBe('10AM – 11AM EST');
    });

    it('formats in the given timezone', function (): void {
        expect(DateHelper::formatTimeRange(
            torontoDate('2026-03-05 10:00'),
            torontoDate('2026-03-05 11:00'),
            timezone: 'America/Vancouver',
        ))->toBe('7AM – 8AM');
    });
});

describe('formatDatetimeRange', function (): void {
    it('shows the date once when start and end are on the same day', function (): void {
        expect(DateHelper::formatDatetimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:30')))
            ->toBe('Mar 5, 2026 ⋅ 10AM – 11:30AM');
    });

    it('shows both dates when the range spans multiple days', function (): void {
        expect(DateHelper::formatDatetimeRange(
            torontoDate('2026-03-05 22:00'),
            torontoDate('2026-03-06 01:00'),
            'long',
            displayTimezone: true,
        ))->toBe('March 5, 2026 ⋅ 10:00PM – March 6, 2026 ⋅ 1:00AM EST');
    });

    it('determines whether it is the same day in the given timezone', function (): void {
        expect(DateHelper::formatDatetimeRange(
            torontoDate('2026-03-05 23:30'),
            torontoDate('2026-03-06 00:30'),
            timezone: 'America/Vancouver',
        ))->toBe('Mar 5, 2026 ⋅ 8:30PM – 9:30PM');
    });

    it('delegates to the time or date range formatter', function (string $format, string $expected): void {
        expect(DateHelper::formatDatetimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-08 11:30'), $format))
            ->toBe($expected);
    })->with([
        'mediumTime' => ['mediumTime', '10AM – 11:30AM'],
        'longTime' => ['longTime', '10:00AM – 11:30AM'],
        'mediumDate' => ['mediumDate', 'Mar 5 - 8, 2026'],
        'longDate' => ['longDate', 'March 5 - 8, 2026'],
    ]);
});
