<?php

use boundstate\eventful\transformers\ArrayTransformer;
use Recurr\Recurrence;
use Recurr\Rule;
use Recurr\Transformer\Constraint\AfterConstraint;

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
