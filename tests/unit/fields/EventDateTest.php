<?php

use boundstate\eventful\fields\EventDate;

describe('settings', function (): void {
    it('converts the legacy all day setting', function (bool $allDay, string $allDayMode): void {
        expect((new EventDate(['allDay' => $allDay]))->allDayMode)->toBe($allDayMode);
    })->with([
        [true, EventDate::ALL_DAY_ALWAYS],
        [false, EventDate::ALL_DAY_NEVER],
    ]);

    it('does not save the legacy all day setting', function (): void {
        expect((new EventDate(['allDay' => true]))->getSettings())
            ->not->toHaveKey('allDay')
            ->toHaveKey('allDayMode', EventDate::ALL_DAY_ALWAYS);
    });
});

describe('normalizeValue', function (): void {
    $timed = [
        'start' => '2026-03-02 15:00:00',
        'end' => '2026-03-02 16:00:00',
        'timezone' => 'America/Toronto',
    ];

    it('uses the field setting unless all day is optional', function (string $allDayMode, ?bool $stored, bool $expected) use ($timed): void {
        $field = new EventDate(['allDayMode' => $allDayMode]);
        $value = $stored === null ? $timed : [...$timed, 'allDay' => $stored];

        expect($field->normalizeValue($value, null)->allDay)->toBe($expected);
    })->with([
        [EventDate::ALL_DAY_NEVER, true, false],
        [EventDate::ALL_DAY_ALWAYS, false, true],
        [EventDate::ALL_DAY_OPTIONAL, true, true],
        [EventDate::ALL_DAY_OPTIONAL, false, false],
        [EventDate::ALL_DAY_OPTIONAL, null, false],
    ]);

    it('ignores the time inputs of an all day event from the request', function (): void {
        Craft::$app->setTimeZone('America/Los_Angeles');
        $field = new EventDate(['allDayMode' => EventDate::ALL_DAY_OPTIONAL]);

        $value = $field->normalizeValueFromRequest([
            'allDay' => '1',
            'start' => ['date' => '2026-03-02', 'time' => '10:00', 'timezone' => 'America/Toronto'],
            'end' => ['time' => '11:00', 'timezone' => 'America/Toronto'],
            'timezone' => 'America/Toronto',
        ], null);

        expect($value->allDay)->toBeTrue()
            ->and($value->start->format('Y-m-d H:i'))->toBe('2026-03-02 00:00')
            ->and($value->end->format('Y-m-d H:i:s'))->toBe('2026-03-02 23:59:59')
            ->and($value->validate())->toBeTrue();
    });
});

describe('serializeValue', function (): void {
    it('stores whether the event is all day', function (bool $allDay): void {
        $field = new EventDate(['allDayMode' => EventDate::ALL_DAY_OPTIONAL]);
        $value = $field->normalizeValue([
            'start' => '2026-03-02 15:00:00',
            'end' => '2026-03-02 16:00:00',
            'timezone' => 'America/Toronto',
            'allDay' => $allDay,
        ], null);

        expect($field->serializeValue($value, null))->toHaveKey('allDay', $allDay);
    })->with([true, false]);
});
