<?php

use boundstate\eventful\enums\IcsMethod;
use boundstate\eventful\enums\IcsStatus;
use boundstate\eventful\models\IcsCalendar;
use boundstate\eventful\models\IcsEvent;
use Recurr\Rule;

beforeEach(function (): void {
    $site = (object) ['name' => 'Acme', 'baseUrl' => 'https://events.example.com/'];
    Craft::$app->set('sites', (object) ['currentSite' => $site, 'primarySite' => $site]);

    IcsEvent::$now = (new DateTime('2026-01-01 12:00:00', new DateTimeZone('UTC')))->getTimestamp();
});

afterEach(function (): void {
    IcsEvent::$now = null;
});

/**
 * Returns the unfolded lines of the serialized calendar.
 */
function icsLines(IcsCalendar $calendar): array
{
    return explode("\r\n", str_replace("\r\n ", '', trim($calendar->serialize())));
}

function torontoTime(string $date): DateTime
{
    return new DateTime($date, new DateTimeZone('America/Toronto'));
}

it('identifies the site in the product identifier', function (): void {
    expect(icsLines(new IcsCalendar))->toContain('PRODID:-//Acme/Calendar//EN');
});

it('sets and removes the method', function (): void {
    $calendar = (new IcsCalendar)->setMethod(IcsMethod::REQUEST);

    expect(icsLines($calendar))->toContain('METHOD:REQUEST')
        ->and(icsLines($calendar->setMethod(null)))->not->toContain('METHOD:REQUEST');
});

it('serializes an event', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()
        ->setUid('event-1')
        ->setSequence(2)
        ->setStart(torontoTime('2026-03-02 10:00'))
        ->setEnd(torontoTime('2026-03-02 11:30'))
        ->setSummary('Yoga')
        ->setDescription('Bring a mat')
        ->setLocation('Studio A')
        ->setStatus(IcsStatus::CONFIRMED);

    expect(icsLines($calendar))->toContain(
        'BEGIN:VEVENT',
        'DTSTAMP:20260101T120000Z',
        'UID:event-1@events.example.com',
        'SEQUENCE:2',
        'DTSTART;TZID=America/Toronto:20260302T100000',
        'DTEND;TZID=America/Toronto:20260302T113000',
        'SUMMARY:Yoga',
        'DESCRIPTION:Bring a mat',
        'LOCATION:Studio A',
        'STATUS:CONFIRMED',
        'END:VEVENT',
    );
});

it('removes the status', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()->setStatus(IcsStatus::CANCELLED)->setStatus(null);

    expect(implode("\n", icsLines($calendar)))->not->toContain('STATUS');
});

it('adds exclusions and inclusions with times outside of the rule', function (): void {
    $start = torontoTime('2026-03-02 10:00');
    $end = torontoTime('2026-03-02 11:30');
    $rule = (new Rule(null, $start, $end))
        ->setFreq('WEEKLY')
        ->setByDay(['MO'])
        ->setCount(3)
        ->setExDates(['2026-03-09'])
        ->setRDates(['2026-03-04']);

    $calendar = new IcsCalendar;
    $calendar->addEvent()->setStart($start)->setEnd($end)->setRule($rule);

    expect(icsLines($calendar))->toContain(
        'RRULE:FREQ=WEEKLY;COUNT=3;BYDAY=MO',
        'EXDATE;TZID=America/Toronto:20260309T100000',
        'RDATE;TZID=America/Toronto:20260304T100000',
    );
});

it('serializes an all day event without times', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()
        ->setAllDay(true)
        ->setStart(torontoTime('2026-03-02 00:00'))
        ->setEnd(torontoTime('2026-03-02 23:59:59'));

    expect(icsLines($calendar))->toContain(
        'DTSTART;VALUE=DATE:20260302',
        // exclusive
        'DTEND;VALUE=DATE:20260303',
    );
});

it('serializes the dates of an all day event regardless of the order they are set', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()
        ->setStart(torontoTime('2026-03-02 00:00'))
        ->setEnd(torontoTime('2026-03-02 23:59:59'))
        ->setAllDay(true);

    $lines = icsLines($calendar);

    expect($lines)->toContain('DTSTART;VALUE=DATE:20260302', 'DTEND;VALUE=DATE:20260303')
        ->and(array_filter($lines, fn ($line): bool => str_starts_with($line, 'DTSTART')))->toHaveCount(1);
});

it('adds the rule, exclusions, and inclusions of an all day event as dates', function (): void {
    $start = torontoTime('2026-03-02 00:00');
    $end = torontoTime('2026-03-02 23:59:59');
    $rule = (new Rule(null, $start, $end))
        ->setFreq('WEEKLY')
        ->setByDay(['MO'])
        ->setUntil(torontoTime('2026-03-30 23:59:59'))
        ->setExDates(['2026-03-09'])
        ->setRDates(['2026-03-04']);

    $calendar = new IcsCalendar;
    $calendar->addEvent()->setAllDay(true)->setStart($start)->setEnd($end)->setRule($rule);

    expect(icsLines($calendar))->toContain(
        'RRULE:FREQ=WEEKLY;UNTIL=20260330;BYDAY=MO',
        'EXDATE;VALUE=DATE:20260309',
        'RDATE;VALUE=DATE:20260304',
    );
});

it('does not modify the given rule', function (): void {
    $rule = (new Rule(null, torontoTime('2026-03-02 10:00'), torontoTime('2026-03-02 11:30')))
        ->setFreq('DAILY')
        ->setExDates(['2026-03-04']);

    (new IcsCalendar)->addEvent()->setStart(torontoTime('2026-03-02 10:00'))->setRule($rule);

    expect($rule->getEndDate())->not->toBeNull()
        ->and($rule->getExDates())->toHaveCount(1);
});

it('adds organizers and attendees', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()
        ->setOrganizers(['old@example.com' => 'Old Organizer'])
        ->setOrganizers(['organizer@example.com' => 'Organizer'])
        ->addAttendees(['jane@example.com' => 'Jane'])
        ->addAttendees(['john@example.com' => 'John'], accepted: true);

    $lines = icsLines($calendar);

    expect($lines)->toContain(
        'ORGANIZER;CN=Organizer:MAILTO:organizer@example.com',
        'ATTENDEE;CN=Jane;ROLE=REQ-PARTICIPANT:MAILTO:jane@example.com',
        'ATTENDEE;CN=John;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:MAILTO:john@example.com',
    )->and(implode("\n", $lines))->not->toContain('old@example.com');
});

it('adds organizers and attendees without names', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()
        ->setOrganizers('organizer@example.com')
        ->addAttendees(['jane@example.com', 'mary@example.com' => 'Mary']);

    expect(icsLines($calendar))->toContain(
        'ORGANIZER:MAILTO:organizer@example.com',
        'ATTENDEE;ROLE=REQ-PARTICIPANT:MAILTO:jane@example.com',
        'ATTENDEE;CN=Mary;ROLE=REQ-PARTICIPANT:MAILTO:mary@example.com',
    );
});

it('replaces attendees', function (): void {
    $calendar = new IcsCalendar;
    $calendar->addEvent()
        ->addAttendees(['jane@example.com' => 'Jane'])
        ->setAttendees(['john@example.com' => 'John']);

    expect(implode("\n", icsLines($calendar)))
        ->not->toContain('jane@example.com')
        ->toContain('john@example.com');
});

describe('addTimezones', function (): void {
    it('adds each event timezone once', function (): void {
        $calendar = new IcsCalendar;
        $calendar->addEvent()->setStart(torontoTime('2026-03-02 10:00'));
        $calendar->addEvent()->setStart(torontoTime('2026-03-09 10:00'));
        $calendar->addEvent()->setStart(new DateTime('2026-03-02 10:00', new DateTimeZone('Europe/London')));
        $calendar->addEvent();
        $calendar->addTimezones();

        $tzIds = array_values(array_filter(icsLines($calendar), fn ($line): bool => str_starts_with($line, 'TZID:')));

        expect($tzIds)->toBe(['TZID:America/Toronto', 'TZID:Europe/London']);
    });

    it('does not add timezones for all day events', function (): void {
        $calendar = new IcsCalendar;
        $calendar->addEvent()->setAllDay(true)->setStart(torontoTime('2026-03-02 00:00'));
        $calendar->addTimezones();

        expect(implode("\n", icsLines($calendar)))->not->toContain('VTIMEZONE');
    });

    it('defines repeating daylight saving transitions', function (): void {
        $calendar = new IcsCalendar;
        $calendar->addEvent()->setStart(torontoTime('2026-03-02 10:00'));
        $calendar->addTimezones();

        $lines = icsLines($calendar);
        $daylight = array_slice($lines, array_search('BEGIN:DAYLIGHT', $lines), 7);
        $standard = array_slice($lines, array_search('BEGIN:STANDARD', $lines), 7);

        expect($daylight)->toContain(
            'TZOFFSETFROM:-0500',
            'TZOFFSETTO:-0400',
            'TZNAME:EDT',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU',
        )->and($standard)->toContain(
            'TZOFFSETFROM:-0400',
            'TZOFFSETTO:-0500',
            'TZNAME:EST',
            'RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU',
        );
    });

    it('formats offsets that are not whole hours', function (string $timezone, array $expected): void {
        $calendar = new IcsCalendar;
        $calendar->addEvent()->setStart(new DateTime('2026-03-02 10:00', new DateTimeZone($timezone)));
        $calendar->addTimezones();

        expect(icsLines($calendar))->toContain(...$expected);
    })->with([
        'positive half hour' => ['Asia/Kolkata', ['TZOFFSETFROM:+0530', 'TZOFFSETTO:+0530']],
        'positive 45 minutes' => ['Asia/Kathmandu', ['TZOFFSETFROM:+0545', 'TZOFFSETTO:+0545']],
        'negative half hour' => ['America/St_Johns', ['TZOFFSETFROM:-0330', 'TZOFFSETTO:-0230']],
    ]);

    it('defines a single non-repeating transition for timezones without daylight saving', function (): void {
        $calendar = new IcsCalendar;
        $calendar->addEvent()->setStart(new DateTime('2026-03-02 10:00', new DateTimeZone('America/Regina')));
        $calendar->addTimezones();

        $lines = icsLines($calendar);

        expect($lines)->toContain('BEGIN:STANDARD', 'TZOFFSETFROM:-0600', 'TZOFFSETTO:-0600')
            ->not->toContain('BEGIN:DAYLIGHT')
            ->and(implode("\n", $lines))->not->toContain('FREQ=YEARLY');
    });
});
