<?php

use boundstate\eventful\transformers\ArrayTransformer;
use Recurr\Recurrence;
use Recurr\Rule;
use Recurr\Transformer\Constraint\AfterConstraint;
use Recurr\Transformer\Constraint\BetweenConstraint;
use yii\base\InvalidArgumentException;

function weeklyRule(): Rule
{
    $tz = new DateTimeZone('America/Toronto');

    // Mondays, 10-11AM
    return (new Rule(null, new DateTime('2026-03-02 10:00', $tz), new DateTime('2026-03-02 11:00', $tz)))
        ->setFreq('WEEKLY')
        ->setByDay(['MO'])
        ->setCount(3);
}

/**
 * Daily, 10-11AM (UTC), starting Sunday, March 1, 2026, repeating forever.
 */
function dailyRule(): Rule
{
    $tz = new DateTimeZone('UTC');

    return (new Rule(null, new DateTime('2026-03-01 10:00', $tz), new DateTime('2026-03-01 11:00', $tz)))
        ->setFreq('DAILY');
}

function utc(string $date): DateTime
{
    return new DateTime($date, new DateTimeZone('UTC'));
}

/**
 * @param  iterable<Recurrence>  $recurrences
 */
function recurrenceTimes(iterable $recurrences): array
{
    $times = [];
    foreach ($recurrences as $recurrence) {
        $times[] = $recurrence->getStart()->format('Y-m-d H:i').' – '.$recurrence->getEnd()->format('H:i');
    }

    return $times;
}

it('generates recurrences from the rule', function (): void {
    expect(recurrenceTimes((new ArrayTransformer)->transform(weeklyRule())))->toBe([
        '2026-03-02 10:00 – 11:00',
        '2026-03-09 10:00 – 11:00',
        '2026-03-16 10:00 – 11:00',
    ]);
});

it('gives inclusions the start and end times of the rule', function (): void {
    $rule = weeklyRule()->setRDates(['2026-03-11']);

    expect(recurrenceTimes((new ArrayTransformer)->transform($rule)))
        ->toContain('2026-03-11 10:00 – 11:00');
});

it('gives inclusions of overnight events the same duration', function (): void {
    $tz = new DateTimeZone('America/Toronto');

    // Mondays, 11:30PM-12:30AM
    $rule = (new Rule(null, new DateTime('2026-03-02 23:30', $tz), new DateTime('2026-03-03 00:30', $tz)))
        ->setFreq('WEEKLY')
        ->setByDay(['MO'])
        ->setCount(1)
        ->setRDates(['2026-03-11']);

    $recurrences = (new ArrayTransformer)->transform($rule);

    expect($recurrences->last()->getStart()->format('Y-m-d H:i'))->toBe('2026-03-11 23:30')
        ->and($recurrences->last()->getEnd()->format('Y-m-d H:i'))->toBe('2026-03-12 00:30');
});

it('orders inclusions among the recurrences', function (): void {
    $rule = weeklyRule()->setRDates(['2026-03-11', '2026-02-20']);

    expect(recurrenceTimes((new ArrayTransformer)->transform($rule)))->toBe([
        '2026-02-20 10:00 – 11:00',
        '2026-03-02 10:00 – 11:00',
        '2026-03-09 10:00 – 11:00',
        '2026-03-11 10:00 – 11:00',
        '2026-03-16 10:00 – 11:00',
    ]);
});

it('applies constraints to inclusions', function (): void {
    $rule = weeklyRule()->setRDates(['2026-03-11', '2026-02-20']);
    $constraint = new AfterConstraint(new DateTime('2026-03-05', new DateTimeZone('America/Toronto')));

    expect(recurrenceTimes((new ArrayTransformer)->transform($rule, $constraint)))->toBe([
        '2026-03-09 10:00 – 11:00',
        '2026-03-11 10:00 – 11:00',
        '2026-03-16 10:00 – 11:00',
    ]);
});

it('uses the start time for inclusion end times when the rule has no end date', function (): void {
    $rule = (new Rule(null, new DateTime('2026-03-02 10:00')))
        ->setFreq('WEEKLY')
        ->setCount(1)
        ->setRDates(['2026-03-04']);

    expect(recurrenceTimes((new ArrayTransformer)->transform($rule)))->toBe([
        '2026-03-02 10:00 – 10:00',
        '2026-03-04 10:00 – 10:00',
    ]);
});

describe('getRecurrences', function (): void {
    it('returns every occurrence of a rule with a count beyond the default limit', function (): void {
        $recurrences = ArrayTransformer::getRecurrences(dailyRule()->setCount(1000), limit: null);

        expect($recurrences)->toHaveCount(1000)
            ->and($recurrences->last()->getStart()->format('Y-m-d'))->toBe('2028-11-24');
    });

    it('applies constraints beyond the virtual limit of a rule with a count', function (): void {
        $recurrences = ArrayTransformer::getRecurrences(
            dailyRule()->setCount(1000),
            new BetweenConstraint(utc('2028-11-23'), utc('2028-12-31')),
        );

        expect(recurrenceTimes($recurrences))->toBe([
            '2028-11-23 10:00 – 11:00',
            '2028-11-24 10:00 – 11:00',
        ]);
    });

    it('counts occurrences that fail the constraint toward the count', function (): void {
        $recurrences = ArrayTransformer::getRecurrences(
            dailyRule()->setCount(5),
            new AfterConstraint(utc('2026-03-03 12:00')),
        );

        expect(recurrenceTimes($recurrences))->toBe([
            '2026-03-04 10:00 – 11:00',
            '2026-03-05 10:00 – 11:00',
        ]);
    });

    it('returns every occurrence of a rule with an until date beyond the default limit', function (): void {
        $recurrences = ArrayTransformer::getRecurrences(dailyRule()->setUntil(utc('2028-11-24 23:59')), limit: null);

        expect($recurrences)->toHaveCount(1000)
            ->and($recurrences->last()->getStart()->format('Y-m-d'))->toBe('2028-11-24');
    });

    it('returns up to the default limit', function (Rule $rule): void {
        expect(ArrayTransformer::getRecurrences($rule))->toHaveCount(732);
    })->with([
        'with a count' => fn (): Rule => dailyRule()->setCount(1000),
        'with an until date' => fn (): Rule => dailyRule()->setUntil(utc('2028-11-24 23:59')),
        'repeating forever' => fn (): Rule => dailyRule(),
    ]);

    it('requires a limit for a rule that repeats forever', function (): void {
        ArrayTransformer::getRecurrences(dailyRule(), limit: null);
    })->throws(InvalidArgumentException::class);

    it('finds occurrences of a rule that repeats forever far beyond the default limit', function (): void {
        $recurrences = ArrayTransformer::getRecurrences(
            dailyRule(),
            new AfterConstraint(utc('2046-03-01 12:00')),
            limit: 2,
        );

        expect(recurrenceTimes($recurrences))->toBe([
            '2046-03-02 10:00 – 11:00',
            '2046-03-03 10:00 – 11:00',
        ]);
    });

    it('returns the limit despite exclusions', function (): void {
        $rule = dailyRule()->setExDates(['20260301', '20260302', '20260303']);

        expect(recurrenceTimes(ArrayTransformer::getRecurrences($rule, limit: 2)))->toBe([
            '2026-03-04 10:00 – 11:00',
            '2026-03-05 10:00 – 11:00',
        ]);
    });

    it('counts inclusions toward the limit', function (): void {
        $rule = dailyRule()->setRDates(['2026-02-20']);

        expect(recurrenceTimes(ArrayTransformer::getRecurrences($rule, limit: 2)))->toBe([
            '2026-02-20 10:00 – 11:00',
            '2026-03-01 10:00 – 11:00',
        ]);
    });

    it('can reuse a constraint', function (): void {
        $constraint = new BetweenConstraint(utc('2026-03-03'), utc('2026-03-05'));

        // the first run passes the end of the constraint, which makes Recurr set stopsTransformer
        // on it, so a second run would stop at its first occurrence before the constraint
        ArrayTransformer::getRecurrences(dailyRule(), $constraint);

        expect(recurrenceTimes(ArrayTransformer::getRecurrences(dailyRule(), $constraint)))->toBe([
            '2026-03-03 10:00 – 11:00',
            '2026-03-04 10:00 – 11:00',
        ]);
    });
});
