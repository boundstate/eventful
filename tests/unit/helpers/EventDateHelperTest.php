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

describe('formatDate of an occurrence', function () {
    $recurrence = fn () => new Recurrence(
        new DateTime('2026-03-02 10:00', new DateTimeZone('America/Toronto')),
        new DateTime('2026-03-02 11:30', new DateTimeZone('America/Toronto')),
    );

    it('formats the date and times', function () use ($recurrence) {
        expect(EventDateHelper::formatDate($recurrence()))->toBeFormattedAs('Mar 2, 2026 ⋅ 10:00 – 11:30 AM')
            ->and(EventDateHelper::formatDate($recurrence(), displayTimezone: true))
            ->toBeFormattedAs('Mar 2, 2026 ⋅ 10:00 – 11:30 AM EST')
            ->and(EventDateHelper::formatDate($recurrence(), 'mediumTime', displayTimezone: true))
            ->toBeFormattedAs('10:00 – 11:30 AM EST');
    });

    it('formats only the date for all day events', function () use ($recurrence) {
        expect(EventDateHelper::formatDate($recurrence(), 'long', allDay: true))->toBeFormattedAs('March 2, 2026');
    });

    it('formats only the date for occurrences of all day events', function () {
        $occurrence = allDayTorontoEventDate()->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence))->toBeFormattedAs('Mar 2, 2026');
    });

    it('formats the range of dates for occurrences of all day events that last several days', function () {
        $occurrence = allDayTorontoEventDate(['end' => '2026-03-04 05:00:00'])->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence))->toBeFormattedAs('Mar 2 – 4, 2026')
            ->and(EventDateHelper::formatDate($occurrence, 'longDate'))->toBeFormattedAs('March 2 – 4, 2026');
    });

    it('formats occurrences of all day events as "All day" for the time formats', function () {
        $occurrence = allDayTorontoEventDate()->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence, 'mediumTime'))->toBeFormattedAs('All day');
    });

    it('does not convert all day dates to the given timezone', function () {
        $occurrence = allDayTorontoEventDate()->getOccurrences()->first();

        expect(EventDateHelper::formatDate($occurrence, timezone: 'America/Vancouver'))->toBeFormattedAs('Mar 2, 2026');
    });
});

describe('formatDate of an EventDate', function () {
    it('formats a non-repeating event the same as its occurrence', function (EventDate $eventDate, string $format) {
        expect(EventDateHelper::formatDate($eventDate, $format, displayTimezone: true))
            ->toBe(EventDateHelper::formatDate($eventDate->getOccurrences()->first(), $format, displayTimezone: true));
    })->with([
        'timed' => fn () => torontoEventDate(),
        'all day' => fn () => allDayTorontoEventDate(),
    ])->with(['medium', 'long', 'mediumDate', 'longTime']);

    it('formats a non-repeating event', function (string $format, string $expected) {
        expect(EventDateHelper::formatDate(torontoEventDate(), $format))->toBeFormattedAs($expected);
    })->with([
        'medium' => ['medium', 'Mar 2, 2026 ⋅ 10:00 – 11:30 AM'],
        'long' => ['long', 'March 2, 2026 ⋅ 10:00 – 11:30 AM'],
        'mediumDate' => ['mediumDate', 'Mar 2, 2026'],
        'longDate' => ['longDate', 'March 2, 2026'],
        'mediumTime' => ['mediumTime', '10:00 – 11:30 AM'],
        'longTime' => ['longTime', '10:00 – 11:30 AM'],
    ]);

    it('formats an all day event without times', function (string $format, string $expected) {
        expect(EventDateHelper::formatDate(allDayTorontoEventDate(), $format))->toBeFormattedAs($expected);
    })->with([
        'medium' => ['medium', 'Mar 2, 2026'],
        'longDate' => ['longDate', 'March 2, 2026'],
        'mediumTime' => ['mediumTime', 'All day'],
    ]);

    it('formats an all day event that lasts several days as the range of dates', function () {
        expect(EventDateHelper::formatDate(allDayTorontoEventDate(['end' => '2026-03-04 05:00:00'])))
            ->toBeFormattedAs('Mar 2 – 4, 2026');
    });

    it('formats a repeating all day event as the range of dates', function () {
        $eventDate = allDayTorontoEventDate([
            'repeat' => true,
            'freq' => EventDate::FREQ_WEEKLY,
            'byDay' => ['MO'],
            'ends' => EventDate::ENDS_COUNT,
            'count' => 3,
        ]);

        expect(EventDateHelper::formatDate($eventDate))->toBeFormattedAs('Mar 2 – 16, 2026');
    });

    it('does not convert all day dates to the given timezone', function () {
        expect(EventDateHelper::formatDate(allDayTorontoEventDate(), timezone: 'America/Vancouver'))
            ->toBeFormattedAs('Mar 2, 2026');
    });

    it('formats a repeating event as the range of dates and times', function (string $format, string $expected) {
        expect(EventDateHelper::formatDate(weeklyTorontoEventDate(), $format))->toBeFormattedAs($expected);
    })->with([
        'medium' => ['medium', 'Mar 2 – 16, 2026 ⋅ 10:00 – 11:30 AM'],
        'mediumDate' => ['mediumDate', 'Mar 2 – 16, 2026'],
        'longDate' => ['longDate', 'March 2 – 16, 2026'],
        'mediumTime' => ['mediumTime', '10:00 – 11:30 AM'],
    ]);

    it('optionally displays the timezone', function () {
        expect(EventDateHelper::formatDate(torontoEventDate(), displayTimezone: true))
            ->toBeFormattedAs('Mar 2, 2026 ⋅ 10:00 – 11:30 AM EST')
            ->and(EventDateHelper::formatDate(torontoEventDate(), 'mediumTime', displayTimezone: true))
            ->toBeFormattedAs('10:00 – 11:30 AM EST')
            ->and(EventDateHelper::formatDate(weeklyTorontoEventDate(), displayTimezone: true))
            ->toBeFormattedAs('Mar 2 – 16, 2026 ⋅ 10:00 – 11:30 AM EST');
    });

    it('formats in the given timezone', function () {
        expect(EventDateHelper::formatDate(torontoEventDate(), timezone: 'America/Vancouver'))
            ->toBeFormattedAs('Mar 2, 2026 ⋅ 7:00 – 8:30 AM');
    });
});
