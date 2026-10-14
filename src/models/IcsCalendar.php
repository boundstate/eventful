<?php

namespace boundstate\eventful\models;

use boundstate\eventful\enums\IcsMethod;
use Craft;
use craft\base\Model;
use DateTime;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;

class IcsCalendar extends Model
{
    private readonly VCalendar $_doc;

    /**
     * @var IcsEvent[]
     */
    private array $_events = [];

    /**
     * How many years after the latest event (or now) to include timezone transitions for.
     */
    private const TZ_HORIZON_YEARS = 10;

    public function __construct(array $config = [])
    {
        $this->_doc = new VCalendar([
            'PRODID' => sprintf(
                '-//%s/Calendar//EN',
                Craft::$app->sites->currentSite->name,
            ),
        ]);
        parent::__construct($config);
    }

    public function setMethod(?IcsMethod $method): static
    {
        if (! $method instanceof IcsMethod) {
            $this->_doc->remove('METHOD');
        } else {
            $this->_doc->METHOD = $method->value;
        }

        return $this;
    }

    public function addEvent(array $config = []): IcsEvent
    {
        $event = new IcsEvent($this->_doc, $config);
        $this->_events[] = $event;

        return $event;
    }

    public function serialize(): string
    {
        return $this->_doc->serialize();
    }

    /**
     * Adds a timezone definition for each timed event's timezone to the calendar.
     */
    public function addTimezones(): void
    {
        /** @var array<string, DateTime[]> $firstStarts */
        $firstStarts = [];
        /** @var array<string, DateTime[]> $starts */
        $starts = [];
        foreach ($this->_events as $event) {
            $start = $event->getStart();
            // all day events don't have times, so don't need a timezone
            if ($start instanceof DateTime && ! $event->isAllDay()) {
                $tz = $start->getTimezone()->getName();
                // the timezone must also cover inclusions before the start
                $firstStarts[$tz][] = $event->getFirstStart();
                $starts[$tz][] = $start;
            }
        }

        foreach ($starts as $tz => $tzStarts) {
            $this->addTimezone(new DateTimeZone($tz), min($firstStarts[$tz]), max($tzStarts));
        }
    }

    /**
     * Adds a timezone definition, generated from the timezone database.
     *
     * The definition starts at the first event, and includes transitions up to
     * {@link TZ_HORIZON_YEARS} years after the last event (or now), rather than until the events
     * end, which also gives calendars both the standard and daylight saving time observances.
     * Transitions that repeat yearly on the same rule are collapsed into an RRULE, which is left
     * open-ended if the rule is still in effect at the end, so it also covers events that repeat forever.
     */
    private function addTimezone(DateTimeZone $tz, DateTime $first, DateTime $last): void
    {
        /** @var VTimeZone $tzComponent */
        $tzComponent = $this->_doc->add('VTIMEZONE', [
            'TZID' => $tz->getName(),
            'X-LIC-LOCATION' => $tz->getName(),
        ]);

        $horizonYear = max((int) $last->format('Y'), (int) gmdate('Y', IcsEvent::$now ?? time())) + self::TZ_HORIZON_YEARS;
        $transitions = $tz->getTransitions($first->getTimestamp(), gmmktime(0, 0, 0, 1, 1, $horizonYear + 1));

        // fixed offset timezones (e.g. `+02:00`) don't have transitions, and return false
        if (! $transitions) {
            $offset = $tz->getOffset($first);
            $tzComponent->add($this->_doc->createComponent('STANDARD', [
                'TZOFFSETFROM' => $this->formatTzOffset($offset),
                'TZOFFSETTO' => $this->formatTzOffset($offset),
                'TZNAME' => $tz->getName(),
                'DTSTART' => gmdate('Ymd\THis', $first->getTimestamp() + $offset),
            ]));

            return;
        }

        // the first entry is the observance in effect at the first event, rather than an actual transition
        $initial = array_shift($transitions);
        $observances = [[
            'transition' => $initial + ['offsetFrom' => $initial['offset']],
            'count' => 1,
        ]];

        // index (in $observances) of the latest yearly observance for each signature
        $open = [];
        $offsetFrom = $initial['offset'];

        foreach ($transitions as $transition) {
            $transition['offsetFrom'] = $offsetFrom;
            $offsetFrom = $transition['offset'];

            // onsets are in local time, before the transition
            $local = $transition['ts'] + $transition['offsetFrom'];
            $year = (int) gmdate('Y', $local);
            $day = (int) gmdate('j', $local);
            $byDay = [(int) ceil($day / 7)];
            if ($day + 7 > (int) gmdate('t', $local)) {
                $byDay[] = -1;
            }

            $signature = implode('|', [
                $transition['isdst'] ? 1 : 0,
                $transition['offsetFrom'],
                $transition['offset'],
                $transition['abbr'],
                gmdate('n|w|His', $local),
            ]);

            $i = $open[$signature] ?? null;
            if ($i !== null && $observances[$i]['lastYear'] === $year - 1) {
                $sharedByDay = array_values(array_intersect($observances[$i]['byDay'], $byDay));
                if ($sharedByDay !== []) {
                    $observances[$i]['byDay'] = $sharedByDay;
                    $observances[$i]['lastYear'] = $year;
                    $observances[$i]['last'] = $transition;
                    $observances[$i]['count']++;

                    continue;
                }
            }

            $open[$signature] = count($observances);
            $observances[] = [
                'transition' => $transition,
                'last' => $transition,
                'lastYear' => $year,
                'byDay' => $byDay,
                'count' => 1,
            ];
        }

        foreach ($observances as $observance) {
            $transition = $observance['transition'];
            $local = $transition['ts'] + $transition['offsetFrom'];

            $component = $this->_doc->createComponent(
                $transition['isdst'] ? 'DAYLIGHT' : 'STANDARD',
                [
                    'TZOFFSETFROM' => $this->formatTzOffset($transition['offsetFrom']),
                    'TZOFFSETTO' => $this->formatTzOffset($transition['offset']),
                    'TZNAME' => $transition['abbr'],
                    'DTSTART' => gmdate('Ymd\THis', $local),
                ],
            );

            if ($observance['count'] > 1) {
                $component->RRULE = $this->transitionRule($observance, $horizonYear);
            }

            $tzComponent->add($component);
        }
    }

    /**
     * Returns the RRULE for a timezone observance that repeats yearly, e.g. `FREQ=YEARLY;BYMONTH=3;BYDAY=2SU`.
     */
    private function transitionRule(array $observance, int $horizonYear): string
    {
        $transition = $observance['transition'];
        $local = $transition['ts'] + $transition['offsetFrom'];

        // prefer e.g. 2nd Sunday, unless it's the 5th, which only some months have
        $byDay = min($observance['byDay']);
        $nth = max($observance['byDay']);
        if ($nth > 0 && $nth < 5) {
            $byDay = $nth;
        }

        $rule = sprintf(
            'FREQ=YEARLY;BYMONTH=%d;BYDAY=%d%s',
            gmdate('n', $local),
            $byDay,
            ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][(int) gmdate('w', $local)],
        );

        // the rule stopped before the end, so it was replaced (e.g. daylight saving time was abolished)
        if ($observance['lastYear'] < $horizonYear) {
            $rule .= ';UNTIL='.gmdate('Ymd\THis\Z', $observance['last']['ts']);
        }

        return $rule;
    }

    /**
     * Formats a UTC offset in seconds (e.g. `-12600`) as `±HHMM` (e.g. `-0330`),
     * or `±HHMMSS` if it has seconds (e.g. local mean time before standard time was adopted).
     */
    private function formatTzOffset(int $offset): string
    {
        $absOffset = abs($offset);
        $formatted = sprintf(
            '%s%02d%02d',
            $offset >= 0 ? '+' : '-',
            intdiv($absOffset, 3600),
            intdiv($absOffset % 3600, 60),
        );

        $seconds = $absOffset % 60;

        return $seconds !== 0 ? sprintf('%s%02d', $formatted, $seconds) : $formatted;
    }
}
