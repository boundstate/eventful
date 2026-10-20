<?php

use boundstate\eventful\events\DefineTimezoneLabelEvent;
use boundstate\eventful\helpers\DateHelper;
use yii\base\Event;

function torontoDate(string $date): DateTime
{
    return new DateTime($date, new DateTimeZone('America/Toronto'));
}

describe('setTime', function () {
    it('combines the date of one value with the time of another', function () {
        $result = DateHelper::setTime(torontoDate('2026-03-05 08:15'), torontoDate('2020-01-01 17:45'));

        expect($result->format('Y-m-d H:i'))->toBe('2026-03-05 17:45');
    });

    it('does not mutate the given date', function () {
        $date = torontoDate('2026-03-05 08:15');

        DateHelper::setTime($date, torontoDate('2020-01-01 17:45'));

        expect($date->format('H:i'))->toBe('08:15');
    });
});

describe('endOfDay', function () {
    it('returns the last microsecond of the day', function () {
        $result = DateHelper::endOfDay(torontoDate('2026-03-05'));

        expect($result->format('Y-m-d H:i:s.u'))->toBe('2026-03-05 23:59:59.999999');
    });

    it('does not mutate the given date', function () {
        $date = torontoDate('2026-03-05');

        DateHelper::endOfDay($date);

        expect($date->format('Y-m-d H:i'))->toBe('2026-03-05 00:00');
    });
});

describe('hasSameOffsets', function () {
    it('compares the offsets of two timezones around a date', function (string $timezone, string $otherTimezone, string $date, bool $expected) {
        expect(DateHelper::hasSameOffsets($timezone, $otherTimezone, new DateTime($date)))->toBe($expected);
    })->with([
        'same rules' => ['America/Toronto', 'America/New_York', '2026-01-15', true],
        'different DST, in winter when the offsets match' => ['America/Phoenix', 'America/Denver', '2026-01-15', false],
        'different standard time' => ['America/Halifax', 'America/Toronto', '2026-07-15', false],
        // São Paulo observed DST until February 2019, and Buenos Aires doesn't
        'rules differ within a year' => ['America/Sao_Paulo', 'America/Argentina/Buenos_Aires', '2019-06-15', false],
        'rules differ more than a year before' => ['America/Sao_Paulo', 'America/Argentina/Buenos_Aires', '2021-06-15', true],
    ]);
});

describe('formatDate', function () {
    it('formats a date', function (string $format, string $expected) {
        expect(DateHelper::formatDate(torontoDate('2026-03-05 10:00'), $format))->toBeFormattedAs($expected);
    })->with([
        'medium' => ['medium', 'Mar 5, 2026'],
        'long' => ['long', 'March 5, 2026'],
    ]);

    it('formats in the given timezone', function () {
        expect(DateHelper::formatDate(torontoDate('2026-03-05 23:30'), timezone: 'Europe/Paris'))
            ->toBe('Mar 6, 2026');
    });
});

describe('formatDateRange', function () {
    it('collapses the range', function (string $start, ?string $end, string $format, string $expected) {
        $endDate = $end ? torontoDate($end) : null;

        expect(DateHelper::formatDateRange(torontoDate($start), $endDate, $format))->toBeFormattedAs($expected);
    })->with([
        'no end date' => ['2026-03-05 10:00', null, 'medium', 'Mar 5, 2026'],
        'same day' => ['2026-03-05 10:00', '2026-03-05 18:00', 'medium', 'Mar 5, 2026'],
        'same month' => ['2026-03-05 10:00', '2026-03-08 10:00', 'medium', 'Mar 5 – 8, 2026'],
        'same year' => ['2026-03-05 10:00', '2026-04-07 10:00', 'medium', 'Mar 5 – Apr 7, 2026'],
        'same year (long)' => ['2026-03-05 10:00', '2026-04-07 10:00', 'long', 'March 5 – April 7, 2026'],
        'different years' => ['2026-12-30 10:00', '2027-01-02 10:00', 'medium', 'Dec 30, 2026 – Jan 2, 2027'],
        'overnight' => ['2026-03-05 23:30', '2026-03-06 00:30', 'medium', 'Mar 5 – 6, 2026'],
        'next day, less than 24 hours' => ['2026-03-05 14:00', '2026-03-06 13:00', 'medium', 'Mar 5 – 6, 2026'],
    ]);

    it('compares dates in the given timezone', function () {
        $utc = new DateTimeZone('UTC');

        // Feb 28, 10PM - Mar 3, 10AM in Toronto
        expect(DateHelper::formatDateRange(
            new DateTime('2026-03-01 03:00', $utc),
            new DateTime('2026-03-03 15:00', $utc),
            timezone: 'America/Toronto',
        ))->toBeFormattedAs('Feb 28 – Mar 3, 2026');
    });
});

describe('formatTimeRange', function () {
    it('omits minutes when both times are on the hour for the medium format', function () {
        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:00')))
            ->toBeFormattedAs('10 – 11 AM');
    });

    it('includes minutes for both times unless both are on the hour', function () {
        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:30')))
            ->toBeFormattedAs('10:00 – 11:30 AM');
    });

    it('shows AM/PM for both times when they differ', function () {
        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 11:30'), torontoDate('2026-03-05 13:00')))
            ->toBeFormattedAs('11:30 AM – 1:00 PM');
    });

    it('always includes minutes for other formats', function () {
        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:00'), 'long'))
            ->toBeFormattedAs('10:00 – 11:00 AM');
    });

    it('optionally displays the timezone', function () {
        expect(DateHelper::formatTimeRange(
            torontoDate('2026-03-05 10:00'),
            torontoDate('2026-03-05 11:00'),
            displayTimezone: true,
        ))->toBeFormattedAs('10 – 11 AM EST');
    });

    it('formats in the given timezone', function () {
        expect(DateHelper::formatTimeRange(
            torontoDate('2026-03-05 10:00'),
            torontoDate('2026-03-05 11:00'),
            timezone: 'America/Vancouver',
        ))->toBeFormattedAs('7 – 8 AM');
    });
});

describe('formatDatetimeRange', function () {
    it('shows the date once when start and end are on the same day', function () {
        expect(DateHelper::formatDatetimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:30')))
            ->toBeFormattedAs('Mar 5, 2026 ⋅ 10:00 – 11:30 AM');
    });

    it('shows both dates when the range spans multiple days', function () {
        expect(DateHelper::formatDatetimeRange(
            torontoDate('2026-03-05 22:00'),
            torontoDate('2026-03-06 01:00'),
            'long',
            displayTimezone: true,
        ))->toBeFormattedAs('March 5, 2026 ⋅ 10:00 PM – March 6, 2026 ⋅ 1:00 AM EST');
    });

    it('determines whether it is the same day in the given timezone', function () {
        expect(DateHelper::formatDatetimeRange(
            torontoDate('2026-03-05 23:30'),
            torontoDate('2026-03-06 00:30'),
            timezone: 'America/Vancouver',
        ))->toBeFormattedAs('Mar 5, 2026 ⋅ 8:30 – 9:30 PM');
    });

    it('delegates to the time or date range formatter', function (string $format, string $expected) {
        expect(DateHelper::formatDatetimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-08 11:30'), $format))
            ->toBeFormattedAs($expected);
    })->with([
        'mediumTime' => ['mediumTime', '10:00 AM – 11:30 AM'],
        'longTime' => ['longTime', '10:00 AM – 11:30 AM'],
        'mediumDate' => ['mediumDate', 'Mar 5 – 8, 2026'],
        'longDate' => ['longDate', 'March 5 – 8, 2026'],
    ]);
});

describe('formatting in other locales', function () {
    it('formats with the locale\'s clock, order and range patterns', function (string $locale, array $expected) {
        Craft::$app->getFormatter()->locale = $locale;

        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:30')))
            ->toBeFormattedAs($expected[0])
            ->and(DateHelper::formatTimeRange(torontoDate('2026-03-05 11:30'), torontoDate('2026-03-05 13:00')))
            ->toBeFormattedAs($expected[1])
            ->and(DateHelper::formatDateRange(torontoDate('2026-03-05'), torontoDate('2026-03-08')))
            ->toBeFormattedAs($expected[2]);
    })->with([
        'en-CA' => ['en-CA', ['10:00–11:30 a.m.', '11:30 a.m.–1:00 p.m.', 'Mar 5–8, 2026']],
        'de' => ['de', ['10:00–11:30 Uhr', '11:30–13:00 Uhr', '5.–8. März 2026']],
        'ko' => ['ko', ['오전 10:00~11:30', '오전 11:30 ~ 오후 1:00', '2026년 3월 5일~8일']],
    ]);

    it('falls back to the patterns of parent locales', function () {
        // en-GB gets its time range patterns from en-001
        Craft::$app->getFormatter()->locale = 'en-GB';

        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:30')))
            ->toBeFormattedAs('10:00–11:30');
    });

    it('joins dates and times on different days with the locale\'s separator', function () {
        Craft::$app->getFormatter()->locale = 'ja';

        expect(DateHelper::formatDatetimeRange(torontoDate('2026-03-05 22:00'), torontoDate('2026-03-06 01:00')))
            ->toBeFormattedAs('2026年3月5日 ⋅ 22時～2026年3月6日 ⋅ 1時');
    });
});

describe('timezone label', function () {
    afterEach(function () {
        Event::off(DateHelper::class, DateHelper::EVENT_DEFINE_TIMEZONE_LABEL);
    });

    it('can be overridden by an event handler', function () {
        Event::on(DateHelper::class, DateHelper::EVENT_DEFINE_TIMEZONE_LABEL, function (DefineTimezoneLabelEvent $event) {
            $event->label = 'Toronto time';
        });

        expect(DateHelper::formatTimeRange(torontoDate('2026-03-05 10:00'), torontoDate('2026-03-05 11:00'), displayTimezone: true))
            ->toBeFormattedAs('10 – 11 AM Toronto time');
    });

    it('gives the handler the date in the timezone it is displayed in', function () {
        $events = [];
        Event::on(DateHelper::class, DateHelper::EVENT_DEFINE_TIMEZONE_LABEL, function (DefineTimezoneLabelEvent $event) use (&$events) {
            $events[] = $event;
        });

        DateHelper::formatDatetimeRange(
            torontoDate('2026-03-05 10:00'),
            torontoDate('2026-03-05 11:00'),
            timezone: 'America/Vancouver',
            displayTimezone: true,
        );

        expect($events)->toHaveCount(1)
            ->and($events[0]->date->format('Y-m-d H:i e'))->toBe('2026-03-05 07:00 America/Vancouver')
            ->and($events[0]->timezone)->toBe('America/Vancouver')
            ->and($events[0]->locale)->toBe('en-US')
            ->and($events[0]->label)->toBe('PST');
    });
});
