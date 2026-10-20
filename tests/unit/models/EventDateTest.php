<?php

use boundstate\eventful\fields\EventDate as EventDateField;
use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\Occurrence;
use Recurr\Rule;
use Recurr\Transformer\Constraint\BetweenConstraint;

/**
 * Mondays & Wednesdays, 10-11AM (Toronto), starting Monday, March 2, 2026.
 */
function weeklyEventDate(array $config = []): EventDate
{
    return new EventDate(array_merge([
        'start' => '2026-03-02 15:00:00',
        'end' => '2026-03-02 16:00:00',
        'timezone' => 'America/Toronto',
        'repeat' => true,
        'freq' => EventDate::FREQ_WEEKLY,
        'byDay' => ['MO', 'WE'],
        'ends' => EventDate::ENDS_COUNT,
        'count' => 4,
    ], $config));
}

function occurrenceStarts(EventDate $eventDate): array
{
    return array_map(
        fn ($recurrence) => $recurrence->getStart()->format('Y-m-d H:i'),
        $eventDate->getOccurrences()->toArray(),
    );
}

/**
 * Daily, 10AM-12PM (UTC), starting Sunday, March 1, 2026.
 */
function dailyEventDate(array $config = []): EventDate
{
    return new EventDate(array_merge([
        'start' => '2026-03-01 10:00:00',
        'end' => '2026-03-01 12:00:00',
        'timezone' => 'UTC',
        'repeat' => true,
        'freq' => EventDate::FREQ_DAILY,
        'ends' => EventDate::ENDS_COUNT,
        'count' => 10,
    ], $config));
}

function startsOf(iterable $occurrences): array
{
    $starts = [];
    foreach ($occurrences as $occurrence) {
        $starts[] = $occurrence->getStart()->format('Y-m-d H:i');
    }

    return $starts;
}

describe('constructor', function () {
    it('converts UTC dates to the event timezone', function () {
        $eventDate = weeklyEventDate();

        expect($eventDate->start->format('Y-m-d H:i T'))->toBe('2026-03-02 10:00 EST')
            ->and($eventDate->end->format('Y-m-d H:i T'))->toBe('2026-03-02 11:00 EST');
    });

    it('accepts date and time arrays', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'time' => '10:00', 'timezone' => 'America/Toronto'],
            'end' => ['date' => '2026-03-02', 'time' => '11:30', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->start->format('Y-m-d H:i'))->toBe('2026-03-02 10:00')
            ->and($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-02 11:30');
    });

    it('uses the start date when the end only has a time', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'time' => '10:00', 'timezone' => 'America/Toronto'],
            'end' => ['time' => '11:30', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-02 11:30');
    });

    it('sets the until time to the end of the day when only a date is given', function () {
        $eventDate = weeklyEventDate([
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2026-03-31', 'timezone' => 'America/Toronto'],
        ]);

        expect($eventDate->until->format('Y-m-d H:i:s'))->toBe('2026-03-31 23:59:59');
    });

    it('ignores empty dates', function () {
        $eventDate = new EventDate(['start' => '', 'end' => null, 'timezone' => 'America/Toronto']);

        expect($eventDate->start)->toBeNull()
            ->and($eventDate->end)->toBeNull();
    });

    it('ends the next day when the end time is before the start time', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'time' => '23:30', 'timezone' => 'America/Toronto'],
            'end' => ['time' => '00:30', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-03 00:30');
    });

    it('fixes a stored end that is before the start', function () {
        $eventDate = new EventDate([
            'start' => '2026-03-03 04:30:00',
            'end' => '2026-03-02 05:30:00',
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->start->format('Y-m-d H:i'))->toBe('2026-03-02 23:30')
            ->and($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-03 00:30');
    });

    it('keeps an end on a later day', function () {
        $eventDate = new EventDate([
            'start' => '2026-03-02 15:00:00',
            'end' => '2026-03-04 14:00:00',
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-04 09:00');
    });

    it('defaults the end to an hour after the start when the end time is blank', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-10-07', 'time' => '9:00 AM', 'timezone' => 'America/Vancouver'],
            'end' => ['time' => '', 'timezone' => 'America/Vancouver'],
            'timezone' => 'America/Vancouver',
        ]);

        expect($eventDate->validate())->toBeTrue()
            ->and($eventDate->end->format('Y-m-d H:i'))->toBe('2026-10-07 10:00');
    });

    it('defaults the end when loading a draft saved without one', function () {
        $eventDate = new EventDate([
            'start' => '2026-10-07 07:00:00',
            'end' => null,
            'timezone' => 'America/Vancouver',
        ]);

        expect($eventDate->validate())->toBeTrue()
            ->and($eventDate->end->format('Y-m-d H:i T'))->toBe('2026-10-07 01:00 PDT');
    });

    it('ends all day events at the end of the start day', function () {
        $eventDate = new EventDate([
            'start' => '2026-10-07 07:00:00',
            'timezone' => 'America/Vancouver',
            'allDay' => true,
        ]);

        expect($eventDate->start->format('Y-m-d H:i T'))->toBe('2026-10-07 00:00 UTC')
            ->and($eventDate->end->format('Y-m-d H:i:s T'))->toBe('2026-10-07 23:59:59 UTC');
    });

    it('keeps the days of all day events in the system timezone', function () {
        setSystemTimezone('America/Los_Angeles');
        $field = new EventDateField(['handle' => 'date', 'allDayMode' => EventDateField::ALL_DAY_ALWAYS]);

        // created from the calendar for Tuesday, March 3 in Tokyo, which is still Monday in Los Angeles
        $eventDate = $field->normalizeValue([
            'start' => '2026-03-02T15:30:00Z',
            'timezone' => 'Asia/Tokyo',
        ], null);
        $reloaded = $field->normalizeValue($field->serializeValue($eventDate, null), null);

        foreach ([$eventDate, $reloaded] as $value) {
            expect($value->start->format('Y-m-d H:i T'))->toBe('2026-03-03 00:00 PST')
                ->and($value->end->format('Y-m-d H:i:s T'))->toBe('2026-03-03 23:59:59 PST');
        }
    });

    it('ends all day events from the request at the end of the start day', function () {
        // the timezone isn't posted for all day events
        $eventDate = new EventDate([
            'start' => ['date' => '2026-10-07', 'timezone' => 'UTC'],
            'allDay' => true,
        ]);

        expect($eventDate->validate())->toBeTrue()
            ->and($eventDate->start->format('Y-m-d H:i'))->toBe('2026-10-07 00:00')
            ->and($eventDate->end->format('Y-m-d H:i:s'))->toBe('2026-10-07 23:59:59');
    });

    it('ends all day events at the end of the end date', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-10-07', 'timezone' => 'UTC'],
            'end' => ['date' => '2026-10-09', 'timezone' => 'UTC'],
            'allDay' => true,
        ]);

        expect($eventDate->validate())->toBeTrue()
            ->and($eventDate->end->format('Y-m-d H:i:s'))->toBe('2026-10-09 23:59:59');
    });

    it('does not allow all day events to end before the start date', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-10-07', 'timezone' => 'UTC'],
            'end' => ['date' => '2026-10-06', 'timezone' => 'UTC'],
            'allDay' => true,
        ]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('end');
    });

    it('repeats all day events that last several days', function () {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'timezone' => 'UTC'],
            'end' => ['date' => '2026-03-03', 'timezone' => 'UTC'],
            'allDay' => true,
            'repeat' => true,
            'freq' => EventDate::FREQ_WEEKLY,
            'byDay' => ['MO'],
            'ends' => EventDate::ENDS_COUNT,
            'count' => 3,
        ]);

        expect(occurrenceStarts($eventDate))->toBe(['2026-03-02 00:00', '2026-03-09 00:00', '2026-03-16 00:00'])
            ->and($eventDate->getLastEndDate()->format('Y-m-d H:i:s'))->toBe('2026-03-17 23:59:59');
    });

    it('repeats all day events', function () {
        $eventDate = new EventDate([
            'start' => '2026-03-02 05:00:00',
            'timezone' => 'America/Toronto',
            'allDay' => true,
            'repeat' => true,
            'freq' => EventDate::FREQ_WEEKLY,
            'byDay' => ['MO'],
            'ends' => EventDate::ENDS_COUNT,
            'count' => 3,
        ]);

        expect(occurrenceStarts($eventDate))->toBe(['2026-03-02 00:00', '2026-03-09 00:00', '2026-03-16 00:00'])
            ->and($eventDate->getLastEndDate()->format('Y-m-d H:i:s'))->toBe('2026-03-16 23:59:59');
    });

    it('marks occurrences of all day events as all day', function (bool $allDay) {
        $eventDate = weeklyEventDate(['allDay' => $allDay]);

        expect($eventDate->getOccurrences()->toArray())->each->toBeInstanceOf(Occurrence::class)
            ->and(array_map(fn (Occurrence $o) => $o->allDay, $eventDate->getOccurrences()->toArray()))
            ->each->toBe($allDay);
    })->with([true, false]);

    it('populates attributes from a legacy rule', function () {
        $eventDate = new EventDate([
            'rule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU;COUNT=5;EXDATE=20260324,20260310;RDATE=20260305',
            'start' => new DateTime('2026-03-03 10:00'),
            'end' => new DateTime('2026-03-03 11:00'),
        ]);

        expect($eventDate)
            ->repeat->toBeTrue()
            ->freq->toBe(EventDate::FREQ_WEEKLY)
            ->interval->toBe(2)
            ->byDay->toBe(['TU'])
            ->ends->toBe(EventDate::ENDS_COUNT)
            ->count->toBe(5)
            ->inDates->toBe(['2026-03-05'])
            ->and(array_values($eventDate->exDates))->toBe(['2026-03-10', '2026-03-24']);
    });

    it('populates the until date from a legacy rule', function () {
        $eventDate = new EventDate([
            'rule' => 'FREQ=DAILY;UNTIL=20260310T235959Z',
            'start' => new DateTime('2026-03-03 10:00'),
            'end' => new DateTime('2026-03-03 11:00'),
        ]);

        expect($eventDate->ends)->toBe(EventDate::ENDS_UNTIL)
            ->and($eventDate->until->format('Y-m-d'))->toBe('2026-03-10');
    });
});

describe('getRule', function () {
    it('returns null when the event does not repeat', function () {
        expect(weeklyEventDate(['repeat' => false])->getRule())->toBeNull();
    });

    it('returns null without a start and end date', function () {
        $eventDate = weeklyEventDate();
        $eventDate->end = null;

        expect($eventDate->getRule())->toBeNull();
    });

    it('builds a rule from the attributes', function () {
        $rule = weeklyEventDate([
            'interval' => 2,
            'exDates' => ['2026-03-04'],
            'inDates' => ['2026-03-06'],
        ])->getRule();

        expect($rule)->toBeInstanceOf(Rule::class)
            ->and($rule->getString())
            ->toBe('FREQ=WEEKLY;COUNT=4;DTEND=20260302T110000;INTERVAL=2;BYDAY=MO,WE;RDATE=20260306;EXDATE=20260304');
    });

    it('uses the until date when the event repeats until a date', function () {
        $rule = weeklyEventDate([
            'ends' => EventDate::ENDS_UNTIL,
            'until' => '2026-03-31 23:59:59',
        ])->getRule();

        expect($rule->getCount())->toBeNull()
            ->and($rule->getUntil()->format('Y-m-d'))->toBe('2026-03-31');
    });

    it('caches the rule', function () {
        $eventDate = weeklyEventDate();
        $rule = $eventDate->getRule();
        $eventDate->count = 10;

        expect($eventDate->getRule())->toBe($rule);
    });
});

describe('validation', function () {
    it('passes for a valid event', function () {
        expect(weeklyEventDate()->validate())->toBeTrue();
    });

    it('requires a start date', function () {
        $eventDate = weeklyEventDate(['start' => null]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('start');
    });

    it('requires an end date and timezone unless all day', function () {
        $eventDate = new EventDate(['start' => new DateTime('2026-03-02')]);
        $allDay = new EventDate(['start' => new DateTime('2026-03-02'), 'allDay' => true]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKeys(['end', 'timezone'])
            ->and($allDay->validate())->toBeTrue();
    });

    it('requires days for weekly events', function () {
        $eventDate = weeklyEventDate(['byDay' => []]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('byDay');
    });

    it('requires the event to end unless never-ending events are allowed', function () {
        expect(weeklyEventDate(['ends' => null])->validate())->toBeFalse()
            ->and(weeklyEventDate(['ends' => null, 'allowNeverEnding' => true])->validate())->toBeTrue();
    });

    it('requires a positive count', function () {
        $eventDate = weeklyEventDate(['count' => 0]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('count');
    });

    it('requires an until date when repeating until a date', function () {
        $eventDate = weeklyEventDate(['ends' => EventDate::ENDS_UNTIL, 'until' => null]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('until');
    });

    it('does not allow the until date to be before the start date', function () {
        $eventDate = weeklyEventDate(['ends' => EventDate::ENDS_UNTIL, 'until' => '2026-03-01 12:00:00']);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getFirstError('until'))->toBe('Until cannot be before start date');
    });

    it('allows the until date to be on the start date', function () {
        expect(weeklyEventDate(['ends' => EventDate::ENDS_UNTIL, 'until' => '2026-03-02 23:59:59'])->validate())
            ->toBeTrue();
    });
});

describe('getRepeatDescription', function () {
    it('describes the repeat rule', function () {
        expect(weeklyEventDate()->getRepeatDescription())->toBe('Weekly on Monday and Wednesday 4 times');
    });

    it('describes a monthly rule repeating until a date in the event timezone', function (string $systemTimezone) {
        Craft::$app->timeZone = $systemTimezone;

        $eventDate = weeklyEventDate([
            'freq' => EventDate::FREQ_MONTHLY,
            'byDay' => ['-1FR'],
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2026-08-01', 'timezone' => 'America/Toronto'],
        ]);

        expect($eventDate->getRepeatDescription())->toBe('Monthly on the last Friday until August 1, 2026');
    })->with(['UTC', 'America/Toronto', 'Pacific/Auckland']);

    it('is translated into the application language', function () {
        Craft::$app->language = 'fr';

        expect(weeklyEventDate()->getRepeatDescription())->toBe('Chaque semaine le lundi et mercredi 4 fois');
    });

    it('is null when the event does not repeat', function () {
        expect(weeklyEventDate(['repeat' => false])->getRepeatDescription())->toBeNull();
    });
});

describe('occurrences', function () {
    it('has a single occurrence when the event does not repeat', function () {
        $eventDate = weeklyEventDate(['repeat' => false]);

        expect(occurrenceStarts($eventDate))->toBe(['2026-03-02 10:00'])
            ->and($eventDate->getFirstStartDate())->toBe($eventDate->start)
            ->and($eventDate->getLastEndDate())->toBe($eventDate->end);
    });

    it('applies exclusions and inclusions', function () {
        $eventDate = weeklyEventDate([
            'exDates' => ['2026-03-04'],
            'inDates' => ['2026-02-27'],
        ]);

        expect(occurrenceStarts($eventDate))->toBe([
            '2026-02-27 10:00',
            '2026-03-02 10:00',
            '2026-03-09 10:00',
            '2026-03-11 10:00',
        ]);
    });

    it('returns the first start and last end dates', function () {
        $eventDate = weeklyEventDate(['inDates' => ['2026-02-27']]);

        expect($eventDate->getFirstStartDate()->format('Y-m-d H:i'))->toBe('2026-02-27 10:00')
            ->and($eventDate->getLastEndDate()->format('Y-m-d H:i'))->toBe('2026-03-11 11:00');
    });

    it('returns the last end date of an event with more occurrences than the virtual limit', function (array $config, string $expected) {
        $eventDate = weeklyEventDate([
            'freq' => EventDate::FREQ_DAILY,
            'byDay' => null,
            ...$config,
        ]);

        expect($eventDate->getLastEndDate()->format('Y-m-d H:i'))->toBe($expected);
    })->with([
        'count' => [['count' => 1000], '2028-11-25 11:00'],
        'until' => [['ends' => EventDate::ENDS_UNTIL, 'until' => ['date' => '2029-03-01', 'timezone' => 'America/Toronto']], '2029-03-01 11:00'],
        'until with the last occurrences excluded' => [[
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2029-03-01', 'timezone' => 'America/Toronto'],
            'exDates' => ['2029-02-28', '2029-03-01'],
        ], '2029-02-27 11:00'],
        'until far in the future' => [['ends' => EventDate::ENDS_UNTIL, 'until' => ['date' => '2126-03-01', 'timezone' => 'America/Toronto']], '2126-03-01 11:00'],
        'until with an inclusion after it' => [[
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2029-03-01', 'timezone' => 'America/Toronto'],
            'inDates' => ['2029-03-05'],
        ], '2029-03-05 11:00'],
    ]);

    it('returns the last end date of an event that repeats every few years', function () {
        $eventDate = weeklyEventDate([
            'freq' => EventDate::FREQ_YEARLY,
            'interval' => 3,
            'byDay' => null,
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2034-12-31', 'timezone' => 'America/Toronto'],
        ]);

        expect($eventDate->getLastEndDate()->format('Y-m-d H:i'))->toBe('2032-03-02 11:00');
    });

    it('has no last end date when the event repeats forever', function (array $config) {
        expect(weeklyEventDate(['ends' => null, ...$config])->getLastEndDate())->toBeNull();
    })->with([
        'without a count' => [['count' => null]],
        'with the default count' => [['count' => (new EventDate)->count]],
        'with an until date' => [['until' => '2026-03-31']],
    ]);

    it('applies constraints without exceeding the count', function () {
        $occurrences = weeklyEventDate()->getOccurrences(
            new BetweenConstraint(new DateTime('2026-03-04'), new DateTime('2026-12-31')),
        );

        expect(array_map(
            fn ($recurrence) => $recurrence->getStart()->format('Y-m-d H:i'),
            $occurrences->toArray(),
        ))->toBe(['2026-03-04 10:00', '2026-03-09 10:00', '2026-03-11 10:00']);
    });

    it('applies constraints far beyond the start of events that repeat forever', function () {
        $occurrences = weeklyEventDate(['ends' => null, 'count' => null])->getOccurrences(
            new BetweenConstraint(new DateTime('2046-03-01'), new DateTime('2046-03-08')),
        );

        expect(array_map(
            fn ($recurrence) => $recurrence->getStart()->format('Y-m-d H:i'),
            $occurrences->toArray(),
        ))->toBe(['2046-03-05 10:00', '2046-03-07 10:00']);
    });

    it('returns up to 732 occurrences', function () {
        $occurrences = dailyEventDate([
            'ends' => EventDate::ENDS_UNTIL,
            'count' => null,
            'until' => '2028-11-25',
        ])->getOccurrences();

        expect($occurrences)->toHaveCount(732);
    });

    it('applies constraints after more occurrences than the virtual limit', function () {
        $occurrences = dailyEventDate(['count' => 1000])->getOccurrences(
            new BetweenConstraint(new DateTime('2028-05-10'), new DateTime('2028-05-11')),
        );

        expect(startsOf($occurrences))->toBe(['2028-05-10 10:00']);
    });

    it('applies constraints to events that do not repeat', function () {
        $eventDate = weeklyEventDate(['repeat' => false]);

        expect(startsOf($eventDate->getOccurrences(
            new BetweenConstraint(new DateTime('2026-03-01'), new DateTime('2026-03-03')),
        )))->toBe(['2026-03-02 10:00'])
            ->and(startsOf($eventDate->getOccurrences(
                new BetweenConstraint(new DateTime('2026-03-04'), new DateTime('2026-12-31')),
            )))->toBe([]);
    });

    it('can reuse a constraint for several events', function () {
        $constraint = new BetweenConstraint(new DateTime('2026-03-04'), new DateTime('2026-03-10'));

        // the first event passes the end of the constraint, which makes Recurr
        // set stopsTransformer on it, so the next event would stop at its
        // first occurrence before the constraint
        weeklyEventDate()->getOccurrences($constraint);
        $occurrences = weeklyEventDate(['ends' => null, 'count' => null])->getOccurrences($constraint);

        expect(array_map(
            fn ($recurrence) => $recurrence->getStart()->format('Y-m-d H:i'),
            $occurrences->toArray(),
        ))->toBe(['2026-03-04 10:00', '2026-03-09 10:00']);
    });
});

describe('getNextOccurrence', function () {
    it('returns the next occurrence', function (string $offset, bool $includeInProgress, int $days) {
        // today's occurrence lasts 2 hours, starting at the offset from now
        $start = new DateTime("-3 days $offset");
        $eventDate = new EventDate([
            'start' => $start,
            'end' => (clone $start)->modify('+2 hours'),
            'repeat' => true,
            'freq' => EventDate::FREQ_DAILY,
            'ends' => EventDate::ENDS_COUNT,
            'count' => 10,
        ]);

        expect($eventDate->getNextOccurrence($includeInProgress)->getStart()->format('Y-m-d H:i:s'))
            ->toBe((clone $start)->modify("+$days days")->format('Y-m-d H:i:s'));
    })->with([
        'upcoming today' => ['+1 hour', false, 3],
        'in progress' => ['-1 hour', false, 4],
        'upcoming today, including in progress' => ['+1 hour', true, 3],
        'in progress, including in progress' => ['-1 hour', true, 3],
        'ended, including in progress' => ['-3 hours', true, 4],
    ]);

    it('returns an all day occurrence in progress when including in progress', function () {
        $start = new DateTime('-3 days midnight');
        $eventDate = new EventDate([
            'start' => $start,
            'allDay' => true,
            'repeat' => true,
            'freq' => EventDate::FREQ_DAILY,
            'ends' => EventDate::ENDS_COUNT,
            'count' => 10,
        ]);

        expect($eventDate->getNextOccurrence(includeInProgress: true)->getStart()->format('Y-m-d H:i:s'))
            ->toBe((new DateTime('midnight'))->format('Y-m-d H:i:s'));
    });

    it('skips excluded occurrences', function () {
        $start = new DateTime('-3 days +1 hour');
        $eventDate = new EventDate([
            'start' => $start,
            'end' => (clone $start)->modify('+2 hours'),
            'repeat' => true,
            'freq' => EventDate::FREQ_DAILY,
            'exDates' => [
                (clone $start)->modify('+3 days')->format('Y-m-d'),
                (clone $start)->modify('+4 days')->format('Y-m-d'),
            ],
        ]);

        expect($eventDate->getNextOccurrence()->getStart()->format('Y-m-d H:i:s'))
            ->toBe((clone $start)->modify('+5 days')->format('Y-m-d H:i:s'));
    });

    it('returns the next occurrence after more past occurrences than the virtual limit', function () {
        $start = new DateTime('-800 days +1 hour');
        $eventDate = new EventDate([
            'start' => $start,
            'end' => (clone $start)->modify('+2 hours'),
            'repeat' => true,
            'freq' => EventDate::FREQ_DAILY,
            'ends' => EventDate::ENDS_COUNT,
            'count' => 1000,
        ]);

        expect($eventDate->getNextOccurrence()->getStart()->format('Y-m-d H:i:s'))
            ->toBe((clone $start)->modify('+800 days')->format('Y-m-d H:i:s'));
    });

    it('returns an event that does not repeat if it is next', function (string $start, bool $includeInProgress, bool $expected) {
        $start = new DateTime($start);
        $eventDate = new EventDate(['start' => $start, 'end' => (clone $start)->modify('+2 hours')]);

        expect($eventDate->getNextOccurrence($includeInProgress)?->getStart())->toEqual($expected ? $start : null);
    })->with([
        'upcoming' => ['+1 hour', false, true],
        'in progress' => ['-1 hour', false, false],
        'past' => ['-1 day', false, false],
        'upcoming, including in progress' => ['+1 hour', true, true],
        'in progress, including in progress' => ['-1 hour', true, true],
        'past, including in progress' => ['-1 day', true, false],
    ]);

    it('returns null when all occurrences are in the past', function (bool $includeInProgress) {
        expect(weeklyEventDate()->getNextOccurrence($includeInProgress))->toBeNull();
    })->with([
        'excluding in progress' => [false],
        'including in progress' => [true],
    ]);
});

describe('getOccurrencesInRange', function () {
    it('returns occurrences in the range, optionally including those in progress', function (bool $includeInProgress, array $expected) {
        $occurrences = dailyEventDate()->getOccurrencesInRange(
            new DateTime('2026-03-03 11:00', new DateTimeZone('UTC')),
            new DateTime('2026-03-05 10:00', new DateTimeZone('UTC')),
            $includeInProgress,
        );

        expect($occurrences)->each->toBeInstanceOf(Occurrence::class)
            ->and(startsOf($occurrences))->toBe($expected);
    })->with([
        'excluding in progress' => [false, ['2026-03-04 10:00', '2026-03-05 10:00']],
        'including in progress' => [true, ['2026-03-03 10:00', '2026-03-04 10:00', '2026-03-05 10:00']],
    ]);

    it('excludes an occurrence that ends when the range starts', function (bool $includeInProgress) {
        $occurrences = dailyEventDate()->getOccurrencesInRange(
            new DateTime('2026-03-03 12:00', new DateTimeZone('UTC')),
            new DateTime('2026-03-04 10:00', new DateTimeZone('UTC')),
            $includeInProgress,
        );

        expect(startsOf($occurrences))->toBe(['2026-03-04 10:00']);
    })->with([
        'excluding in progress' => [false],
        'including in progress' => [true],
    ]);

    it('includes an all day occurrence in progress when including in progress', function (bool $includeInProgress, array $expected) {
        $eventDate = dailyEventDate([
            'start' => new DateTime('2026-03-01 00:00'),
            'end' => null,
            'timezone' => null,
            'allDay' => true,
        ]);

        $occurrences = $eventDate->getOccurrencesInRange(
            new DateTime('2026-03-03 12:00'),
            new DateTime('2026-03-03 18:00'),
            $includeInProgress,
        );

        expect(startsOf($occurrences))->toBe($expected);
    })->with([
        'excluding in progress' => [false, []],
        'including in progress' => [true, ['2026-03-03 00:00']],
    ]);

    it('returns occurrences after more past occurrences than the virtual limit', function () {
        $occurrences = dailyEventDate(['count' => 1000])->getOccurrencesInRange(
            new DateTime('2028-05-10 11:00', new DateTimeZone('UTC')),
            new DateTime('2028-05-10 18:00', new DateTimeZone('UTC')),
            true,
        );

        expect(startsOf($occurrences))->toBe(['2028-05-10 10:00']);
    });

    it('returns an event that does not repeat if it is in the range', function (string $start, bool $includeInProgress, bool $expected) {
        $eventDate = new EventDate([
            'start' => "2026-03-03 $start:00",
            'end' => (new DateTime("2026-03-03 $start:00"))->modify('+2 hours')->format('Y-m-d H:i:s'),
            'timezone' => 'UTC',
        ]);

        $occurrences = $eventDate->getOccurrencesInRange(
            new DateTime('2026-03-03 11:00', new DateTimeZone('UTC')),
            new DateTime('2026-03-03 13:00', new DateTimeZone('UTC')),
            $includeInProgress,
        );

        expect(startsOf($occurrences))->toBe($expected ? ["2026-03-03 $start"] : []);
    })->with([
        'ends when the range starts' => ['09:00', false, false],
        'in progress' => ['10:00', false, false],
        'starts in the range' => ['12:00', false, true],
        'starts when the range ends' => ['13:00', false, true],
        'after' => ['14:00', false, false],
        'ends when the range starts, including in progress' => ['09:00', true, false],
        'in progress, including in progress' => ['10:00', true, true],
        'starts in the range, including in progress' => ['12:00', true, true],
        'starts when the range ends, including in progress' => ['13:00', true, true],
        'after, including in progress' => ['14:00', true, false],
    ]);
});

describe('isPast', function () {
    it('is past once the last occurrence has ended', function () {
        expect(weeklyEventDate()->isPast())->toBeTrue();
    });

    it('is not past when an occurrence ends in the future', function () {
        $start = new DateTime('+1 day');

        expect(weeklyEventDate(['start' => $start, 'end' => (clone $start)->modify('+1 hour'), 'timezone' => null])->isPast())
            ->toBeFalse();
    });

    it('is never past when the event repeats forever', function () {
        expect(weeklyEventDate(['ends' => null, 'start' => '2010-01-04 15:00:00', 'end' => '2010-01-04 16:00:00'])->isPast())
            ->toBeFalse();
    });
});
