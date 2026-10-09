<?php

use boundstate\eventful\models\EventDate;
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

describe('constructor', function (): void {
    it('converts UTC dates to the event timezone', function (): void {
        $eventDate = weeklyEventDate();

        expect($eventDate->start->format('Y-m-d H:i T'))->toBe('2026-03-02 10:00 EST')
            ->and($eventDate->end->format('Y-m-d H:i T'))->toBe('2026-03-02 11:00 EST');
    });

    it('accepts date and time arrays', function (): void {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'time' => '10:00', 'timezone' => 'America/Toronto'],
            'end' => ['date' => '2026-03-02', 'time' => '11:30', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->start->format('Y-m-d H:i'))->toBe('2026-03-02 10:00')
            ->and($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-02 11:30');
    });

    it('uses the start date when the end only has a time', function (): void {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'time' => '10:00', 'timezone' => 'America/Toronto'],
            'end' => ['time' => '11:30', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-02 11:30');
    });

    it('sets the until time to the end of the day when only a date is given', function (): void {
        $eventDate = weeklyEventDate([
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2026-03-31', 'timezone' => 'America/Toronto'],
        ]);

        expect($eventDate->until->format('Y-m-d H:i:s'))->toBe('2026-03-31 23:59:59');
    });

    it('ignores empty dates', function (): void {
        $eventDate = new EventDate(['start' => '', 'end' => null, 'timezone' => 'America/Toronto']);

        expect($eventDate->start)->toBeNull()
            ->and($eventDate->end)->toBeNull();
    });

    it('ends the next day when the end time is before the start time', function (): void {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-03-02', 'time' => '23:30', 'timezone' => 'America/Toronto'],
            'end' => ['time' => '00:30', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-03 00:30');
    });

    it('fixes a stored end that is before the start', function (): void {
        $eventDate = new EventDate([
            'start' => '2026-03-03 04:30:00',
            'end' => '2026-03-02 05:30:00',
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->start->format('Y-m-d H:i'))->toBe('2026-03-02 23:30')
            ->and($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-03 00:30');
    });

    it('keeps an end on a later day', function (): void {
        $eventDate = new EventDate([
            'start' => '2026-03-02 15:00:00',
            'end' => '2026-03-04 14:00:00',
            'timezone' => 'America/Toronto',
        ]);

        expect($eventDate->end->format('Y-m-d H:i'))->toBe('2026-03-04 09:00');
    });

    it('defaults the end to an hour after the start when the end time is blank', function (): void {
        $eventDate = new EventDate([
            'start' => ['date' => '2026-10-07', 'time' => '9:00 AM', 'timezone' => 'America/Vancouver'],
            'end' => ['time' => '', 'timezone' => 'America/Vancouver'],
            'timezone' => 'America/Vancouver',
        ]);

        expect($eventDate->validate())->toBeTrue()
            ->and($eventDate->end->format('Y-m-d H:i'))->toBe('2026-10-07 10:00');
    });

    it('defaults the end when loading a draft saved without one', function (): void {
        $eventDate = new EventDate([
            'start' => '2026-10-07 07:00:00',
            'end' => null,
            'timezone' => 'America/Vancouver',
        ]);

        expect($eventDate->validate())->toBeTrue()
            ->and($eventDate->end->format('Y-m-d H:i T'))->toBe('2026-10-07 01:00 PDT');
    });

    it('does not default the end for all day events', function (): void {
        $eventDate = new EventDate([
            'start' => '2026-10-07 07:00:00',
            'timezone' => 'America/Vancouver',
            'allDay' => true,
        ]);

        expect($eventDate->end)->toBeNull();
    });

    it('populates attributes from a legacy rule', function (): void {
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

    it('populates the until date from a legacy rule', function (): void {
        $eventDate = new EventDate([
            'rule' => 'FREQ=DAILY;UNTIL=20260310T235959Z',
            'start' => new DateTime('2026-03-03 10:00'),
            'end' => new DateTime('2026-03-03 11:00'),
        ]);

        expect($eventDate->ends)->toBe(EventDate::ENDS_UNTIL)
            ->and($eventDate->until->format('Y-m-d'))->toBe('2026-03-10');
    });
});

describe('getRule', function (): void {
    it('returns null when the event does not repeat', function (): void {
        expect(weeklyEventDate(['repeat' => false])->getRule())->toBeNull();
    });

    it('returns null without a start and end date', function (): void {
        $eventDate = weeklyEventDate();
        $eventDate->end = null;

        expect($eventDate->getRule())->toBeNull();
    });

    it('builds a rule from the attributes', function (): void {
        $rule = weeklyEventDate([
            'interval' => 2,
            'exDates' => ['2026-03-04'],
            'inDates' => ['2026-03-06'],
        ])->getRule();

        expect($rule)->toBeInstanceOf(Rule::class)
            ->and($rule->getString())
            ->toBe('FREQ=WEEKLY;COUNT=4;DTEND=20260302T110000;INTERVAL=2;BYDAY=MO,WE;RDATE=20260306;EXDATE=20260304');
    });

    it('uses the until date when the event repeats until a date', function (): void {
        $rule = weeklyEventDate([
            'ends' => EventDate::ENDS_UNTIL,
            'until' => '2026-03-31 23:59:59',
        ])->getRule();

        expect($rule->getCount())->toBeNull()
            ->and($rule->getUntil()->format('Y-m-d'))->toBe('2026-03-31');
    });

    it('caches the rule unless refreshed', function (): void {
        $eventDate = weeklyEventDate();
        $rule = $eventDate->getRule();
        $eventDate->count = 10;

        expect($eventDate->getRule())->toBe($rule)
            ->and($eventDate->getRule(true)->getCount())->toBe(10);
    });
});

describe('validation', function (): void {
    it('passes for a valid event', function (): void {
        expect(weeklyEventDate()->validate())->toBeTrue();
    });

    it('requires a start date', function (): void {
        $eventDate = weeklyEventDate(['start' => null]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('start');
    });

    it('requires an end date and timezone unless all day', function (): void {
        $eventDate = new EventDate(['start' => new DateTime('2026-03-02')]);
        $allDay = new EventDate(['start' => new DateTime('2026-03-02'), 'allDay' => true]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKeys(['end', 'timezone'])
            ->and($allDay->validate())->toBeTrue();
    });

    it('requires days for weekly events', function (): void {
        $eventDate = weeklyEventDate(['byDay' => []]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('byDay');
    });

    it('requires the event to end unless never-ending events are allowed', function (): void {
        expect(weeklyEventDate(['ends' => null])->validate())->toBeFalse()
            ->and(weeklyEventDate(['ends' => null, 'allowNeverEnding' => true])->validate())->toBeTrue();
    });

    it('requires a positive count', function (): void {
        $eventDate = weeklyEventDate(['count' => 0]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('count');
    });

    it('requires an until date when repeating until a date', function (): void {
        $eventDate = weeklyEventDate(['ends' => EventDate::ENDS_UNTIL, 'until' => null]);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getErrors())->toHaveKey('until');
    });

    it('does not allow the until date to be before the start date', function (): void {
        $eventDate = weeklyEventDate(['ends' => EventDate::ENDS_UNTIL, 'until' => '2026-03-01 12:00:00']);

        expect($eventDate->validate())->toBeFalse()
            ->and($eventDate->getFirstError('until'))->toBe('Until cannot be before start date');
    });

    it('allows the until date to be on the start date', function (): void {
        expect(weeklyEventDate(['ends' => EventDate::ENDS_UNTIL, 'until' => '2026-03-02 23:59:59'])->validate())
            ->toBeTrue();
    });
});

describe('getRepeatDescription', function (): void {
    it('describes the repeat rule', function (): void {
        expect(weeklyEventDate()->getRepeatDescription())->toBe('Weekly on Monday and Wednesday 4 times');
    });

    it('describes a monthly rule repeating until a date in the event timezone', function (string $systemTimezone): void {
        Craft::$app->timeZone = $systemTimezone;

        $eventDate = weeklyEventDate([
            'freq' => EventDate::FREQ_MONTHLY,
            'byDay' => ['-1FR'],
            'ends' => EventDate::ENDS_UNTIL,
            'until' => ['date' => '2026-08-01', 'timezone' => 'America/Toronto'],
        ]);

        expect($eventDate->getRepeatDescription())->toBe('Monthly on the last Friday until August 1, 2026');
    })->with(['UTC', 'America/Toronto', 'Pacific/Auckland']);

    it('is translated into the application language', function (): void {
        Craft::$app->language = 'fr';

        expect(weeklyEventDate()->getRepeatDescription())->toBe('Chaque semaine le lundi et mercredi 4 fois');
    });

    it('is empty when the event does not repeat', function (): void {
        expect(weeklyEventDate(['repeat' => false])->getRepeatDescription())->toBe('');
    });
});

describe('occurrences', function (): void {
    it('has a single occurrence when the event does not repeat', function (): void {
        $eventDate = weeklyEventDate(['repeat' => false]);

        expect(occurrenceStarts($eventDate))->toBe(['2026-03-02 10:00'])
            ->and($eventDate->getFirstStartDate())->toBe($eventDate->start)
            ->and($eventDate->getLastEndDate())->toBe($eventDate->end);
    });

    it('applies exclusions and inclusions', function (): void {
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

    it('returns the first start and last end dates', function (): void {
        $eventDate = weeklyEventDate(['inDates' => ['2026-02-27']]);

        expect($eventDate->getFirstStartDate()->format('Y-m-d H:i'))->toBe('2026-02-27 10:00')
            ->and($eventDate->getLastEndDate()->format('Y-m-d H:i'))->toBe('2026-03-11 11:00');
    });

    it('has no last end date when the event repeats forever', function (): void {
        expect(weeklyEventDate(['ends' => null, 'count' => null])->getLastEndDate())->toBeNull();
    });

    it('applies constraints without exceeding the count', function (): void {
        $occurrences = weeklyEventDate()->getOccurrences(
            new BetweenConstraint(new DateTime('2026-03-04'), new DateTime('2026-12-31')),
        );

        expect(array_map(
            fn ($recurrence) => $recurrence->getStart()->format('Y-m-d H:i'),
            $occurrences->toArray(),
        ))->toBe(['2026-03-04 10:00', '2026-03-09 10:00', '2026-03-11 10:00']);
    });

    it('applies constraints far beyond the start of events that repeat forever', function (): void {
        $occurrences = weeklyEventDate(['ends' => null, 'count' => null])->getOccurrences(
            new BetweenConstraint(new DateTime('2046-03-01'), new DateTime('2046-03-08')),
        );

        expect(array_map(
            fn ($recurrence) => $recurrence->getStart()->format('Y-m-d H:i'),
            $occurrences->toArray(),
        ))->toBe(['2046-03-05 10:00', '2046-03-07 10:00']);
    });
});

describe('getNextOccurrence', function (): void {
    it('returns the next occurrence starting today or later', function (): void {
        $start = (new DateTime('-3 days'))->setTime(10, 0);
        $eventDate = new EventDate([
            'start' => $start,
            'end' => (clone $start)->setTime(11, 0),
            'repeat' => true,
            'freq' => EventDate::FREQ_DAILY,
            'ends' => EventDate::ENDS_COUNT,
            'count' => 10,
        ]);

        expect($eventDate->getNextOccurrence()->getStart()->format('Y-m-d H:i'))
            ->toBe((new DateTime)->format('Y-m-d').' 10:00');
    });

    it('returns null when all occurrences are in the past', function (): void {
        expect(weeklyEventDate()->getNextOccurrence())->toBeNull();
    });
});

describe('isPast', function (): void {
    it('is past once the last occurrence has ended', function (): void {
        expect(weeklyEventDate()->isPast())->toBeTrue();
    });

    it('is not past when an occurrence ends in the future', function (): void {
        $start = new DateTime('+1 day');

        expect(weeklyEventDate(['start' => $start, 'end' => (clone $start)->modify('+1 hour'), 'timezone' => null])->isPast())
            ->toBeFalse();
    });

    it('is never past when the event repeats forever', function (): void {
        expect(weeklyEventDate(['ends' => null, 'count' => null])->isPast())->toBeFalse();
    });
});
