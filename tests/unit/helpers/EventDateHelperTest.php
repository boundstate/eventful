<?php

use boundstate\eventful\helpers\EventDateHelper;
use boundstate\eventful\models\EventDate;
use Recurr\Recurrence;

function torontoEventDate(array $config = []): EventDate
{
    return new EventDate(array_merge([
        'start' => '2026-03-02 15:00:00',
        'end' => '2026-03-02 16:30:00',
        'timezone' => 'America/Toronto',
    ], $config));
}

function weeklyTorontoEventDate(): EventDate
{
    // Mondays, 10-11:30AM, 3 times
    return torontoEventDate([
        'repeat' => true,
        'freq' => EventDate::FREQ_WEEKLY,
        'byDay' => ['MO'],
        'ends' => EventDate::ENDS_COUNT,
        'count' => 3,
    ]);
}

describe('formatDate', function (): void {
    $recurrence = fn (): Recurrence => new Recurrence(
        new DateTime('2026-03-02 10:00', new DateTimeZone('America/Toronto')),
        new DateTime('2026-03-02 11:30', new DateTimeZone('America/Toronto')),
    );

    it('formats the date and times', function () use ($recurrence): void {
        expect(EventDateHelper::formatDate($recurrence()))->toBe('Mar 2, 2026 ⋅ 10AM – 11:30AM')
            ->and(EventDateHelper::formatDate($recurrence(), displayTimezone: true))
            ->toBe('Mar 2, 2026 ⋅ 10AM – 11:30AM EST');
    });

    it('formats only the date for all day events', function () use ($recurrence): void {
        expect(EventDateHelper::formatDate($recurrence(), 'long', allDay: true))->toBe('March 2, 2026');
    });
});

describe('formatDateRange', function (): void {
    it('formats a non-repeating event', function (string $format, string $expected): void {
        expect(EventDateHelper::formatDateRange(torontoEventDate(), $format))->toBe($expected);
    })->with([
        'medium' => ['medium', 'Mar 2, 2026 ⋅ 10AM – 11:30AM'],
        'mediumDate' => ['mediumDate', 'Mar 2, 2026'],
        'longDate' => ['longDate', 'March 2, 2026'],
        'mediumTime' => ['mediumTime', '10AM – 11:30AM'],
        'longTime' => ['longTime', '10:00AM – 11:30AM'],
    ]);

    it('formats a repeating event as the range of dates and times', function (string $format, string $expected): void {
        expect(EventDateHelper::formatDateRange(weeklyTorontoEventDate(), $format))->toBe($expected);
    })->with([
        'medium' => ['medium', 'Mar 2 - 16, 2026 · 10AM – 11:30AM'],
        'mediumDate' => ['mediumDate', 'Mar 2 - 16, 2026'],
        'longDate' => ['longDate', 'March 2 - 16, 2026'],
        'mediumTime' => ['mediumTime', '10AM – 11:30AM'],
    ]);

    it('optionally displays the timezone', function (): void {
        expect(EventDateHelper::formatDateRange(torontoEventDate(), displayTimezone: true))
            ->toBe('Mar 2, 2026 ⋅ 10AM – 11:30AM EST')
            ->and(EventDateHelper::formatDateRange(weeklyTorontoEventDate(), displayTimezone: true))
            ->toBe('Mar 2 - 16, 2026 · 10AM – 11:30AM EST');
    });

    it('formats in the given timezone', function (): void {
        expect(EventDateHelper::formatDateRange(torontoEventDate(), timezone: 'America/Vancouver'))
            ->toBe('Mar 2, 2026 ⋅ 7AM – 8:30AM');
    });
});
