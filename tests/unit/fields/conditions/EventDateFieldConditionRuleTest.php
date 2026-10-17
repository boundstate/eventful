<?php

use boundstate\eventful\fields\conditions\EventDateFieldConditionRule as Rule;
use boundstate\eventful\models\EventDate;

function matchesEventDate(string $operator, ?EventDate $value): bool
{
    $rule = new Rule(['operator' => $operator]);

    return (fn () => $this->matchFieldValue($value))->call($rule);
}

describe('matchFieldValue', function () {
    $timed = [
        'start' => '2026-03-02 15:00:00',
        'end' => '2026-03-02 16:00:00',
        'timezone' => 'America/Toronto',
    ];
    $repeating = [...$timed, 'repeat' => true, 'freq' => EventDate::FREQ_DAILY];
    $allDay = [...$timed, 'allDay' => true];

    it('matches whether the event repeats or is all day', function (string $operator, array $config, bool $expected) {
        expect(matchesEventDate($operator, new EventDate($config)))->toBe($expected);
    })->with([
        [Rule::OPERATOR_REPEATS, $repeating, true],
        [Rule::OPERATOR_REPEATS, $timed, false],
        [Rule::OPERATOR_NOT_REPEATS, $repeating, false],
        [Rule::OPERATOR_NOT_REPEATS, $timed, true],
        [Rule::OPERATOR_ALL_DAY, $allDay, true],
        [Rule::OPERATOR_ALL_DAY, $timed, false],
        [Rule::OPERATOR_NOT_ALL_DAY, $allDay, false],
        [Rule::OPERATOR_NOT_ALL_DAY, $timed, true],
    ]);

    it('does not treat a repeat without a frequency as repeating', function () use ($timed) {
        $value = new EventDate([...$timed, 'repeat' => true, 'freq' => '']);

        expect(matchesEventDate(Rule::OPERATOR_REPEATS, $value))->toBeFalse()
            ->and(matchesEventDate(Rule::OPERATOR_NOT_REPEATS, $value))->toBeTrue();
    });

    it('treats a missing value as neither repeating nor all day', function (string $operator, bool $expected) {
        expect(matchesEventDate($operator, null))->toBe($expected);
    })->with([
        [Rule::OPERATOR_REPEATS, false],
        [Rule::OPERATOR_NOT_REPEATS, true],
        [Rule::OPERATOR_ALL_DAY, false],
        [Rule::OPERATOR_NOT_ALL_DAY, true],
    ]);
});
