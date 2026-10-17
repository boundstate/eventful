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

function allDayTorontoEventDate(array $config = []): EventDate
{
    // Monday, March 2
    return new EventDate(array_merge([
        'start' => '2026-03-02 05:00:00',
        'timezone' => 'America/Toronto',
        'allDay' => true,
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

describe('formatDate', function () {
    $recurrence = fn () => new Recurrence(
        new DateTime('2026-03-02 10:00', new DateTimeZone('America/Toronto')),
        new DateTime('2026-03-02 11:30', new DateTimeZone('America/Toronto')),
    );

    it('formats the date and times', function () use ($recurrence) {
        expect(EventDateHelper::formatDate($recurrence()))->toBe('Mar 2, 2026 ⋅ 10AM – 11:30AM')
            ->and(EventDateHelper::formatDate($recurrence(), displayTimezone: true))
            ->toBe('Mar 2, 2026 ⋅ 10AM – 11:30AM EST');
    });

    it('formats only the date for all day events', function () use ($recurrence) {
        expect(EventDateHelper::formatDate($recurrence(), 'long', allDay: true))->toBe('March 2, 2026');
    });

    it('formats only the date for occurrences of all day events', function () {
        $occurrence = allDayTorontoEventDate()->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence))->toBe('Mar 2, 2026');
    });

    it('formats the range of dates for occurrences of all day events that last several days', function () {
        $occurrence = allDayTorontoEventDate(['end' => '2026-03-04 05:00:00'])->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence))->toBe('Mar 2 - 4, 2026')
            ->and(EventDateHelper::formatDate($occurrence, 'longDate'))->toBe('March 2 - 4, 2026');
    });

    it('does not convert all day dates to the given timezone', function () {
        $occurrence = allDayTorontoEventDate()->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence, timezone: 'America/Vancouver'))->toBe('Mar 2, 2026');
    });
});

describe('formatDateRange', function () {
    it('formats a non-repeating event', function (string $format, string $expected) {
        expect(EventDateHelper::formatDateRange(torontoEventDate(), $format))->toBe($expected);
    })->with([
        'medium' => ['medium', 'Mar 2, 2026 ⋅ 10AM – 11:30AM'],
        'mediumDate' => ['mediumDate', 'Mar 2, 2026'],
        'longDate' => ['longDate', 'March 2, 2026'],
        'mediumTime' => ['mediumTime', '10AM – 11:30AM'],
        'longTime' => ['longTime', '10:00AM – 11:30AM'],
    ]);

    it('formats an all day event without times', function (string $format, string $expected) {
        expect(EventDateHelper::formatDateRange(allDayTorontoEventDate(), $format))->toBe($expected);
    })->with([
        'medium' => ['medium', 'Mar 2, 2026'],
        'longDate' => ['longDate', 'March 2, 2026'],
        'mediumTime' => ['mediumTime', 'All day'],
    ]);

    it('formats an all day event that lasts several days as the range of dates', function () {
        expect(EventDateHelper::formatDateRange(allDayTorontoEventDate(['end' => '2026-03-04 05:00:00'])))
            ->toBe('Mar 2 - 4, 2026');
    });

    it('formats a repeating all day event as the range of dates', function () {
        $eventDate = allDayTorontoEventDate([
            'repeat' => true,
            'freq' => EventDate::FREQ_WEEKLY,
            'byDay' => ['MO'],
            'ends' => EventDate::ENDS_COUNT,
            'count' => 3,
        ]);

        expect(EventDateHelper::formatDateRange($eventDate))->toBe('Mar 2 - 16, 2026');
    });

    it('does not convert all day dates to the given timezone', function () {
        expect(EventDateHelper::formatDateRange(allDayTorontoEventDate(), timezone: 'America/Vancouver'))
            ->toBe('Mar 2, 2026');
    });

    it('formats a repeating event as the range of dates and times', function (string $format, string $expected) {
        expect(EventDateHelper::formatDateRange(weeklyTorontoEventDate(), $format))->toBe($expected);
    })->with([
        'medium' => ['medium', 'Mar 2 - 16, 2026 ⋅ 10AM – 11:30AM'],
        'mediumDate' => ['mediumDate', 'Mar 2 - 16, 2026'],
        'longDate' => ['longDate', 'March 2 - 16, 2026'],
        'mediumTime' => ['mediumTime', '10AM – 11:30AM'],
    ]);

    it('optionally displays the timezone', function () {
        expect(EventDateHelper::formatDateRange(torontoEventDate(), displayTimezone: true))
            ->toBe('Mar 2, 2026 ⋅ 10AM – 11:30AM EST')
            ->and(EventDateHelper::formatDateRange(weeklyTorontoEventDate(), displayTimezone: true))
            ->toBe('Mar 2 - 16, 2026 ⋅ 10AM – 11:30AM EST');
    });

    it('formats in the given timezone', function () {
        expect(EventDateHelper::formatDateRange(torontoEventDate(), timezone: 'America/Vancouver'))
            ->toBe('Mar 2, 2026 ⋅ 7AM – 8:30AM');
    });
});
