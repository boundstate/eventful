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
     * Adds a timezone definition the calendar.
     */
    public function addTimezones(): void
    {
        $timezones = array_unique(
            array_map(
                fn (IcsEvent $event) => $event
                    ->getStart()
                    ?->getTimezone()
                    ->getName(),
                $this->_events,
            ),
        );

        foreach ($timezones as $tz) {
            if ($tz) {
                $this->addTimezone(new DateTimeZone($tz));
            }
        }
    }

    private function addTimezone(DateTimeZone $tz): void
    {
        /** @var VTimeZone $tzComponent */
        $tzComponent = $this->_doc->add('VTIMEZONE', [
            'TZID' => $tz->getName(),
            'X-LIC-LOCATION' => $tz->getName(),
        ]);

        // generate the timezone transitions for a single year
        // (we don't have any events starting before 2020)
        $fromTimestamp = (new DateTime('2020-01-01'))->getTimestamp();
        $toTimestamp = (new DateTime('2021-01-01'))->getTimestamp();
        $transitions = $tz->getTransitions($fromTimestamp, $toTimestamp);

        $tzFrom = $transitions[0]['offset'];

        foreach ($transitions as $i => $transition) {
            if ($i === 0 && count($transitions) > 1) {
                continue;
            }

            $date = new DateTime($transition['time']);
            $offset = $transition['offset'];

            $component = $this->_doc->createComponent(
                $transition['isdst'] ? 'DAYLIGHT' : 'STANDARD',
                [
                    'TZOFFSETFROM' => $this->formatTzOffset($tzFrom),
                    'TZOFFSETTO' => $this->formatTzOffset($offset),
                    'TZNAME' => $transition['abbr'],
                    'DTSTART' => $date->format('Ymd\THis'),
                ],
            );

            if (count($transitions) > 1) {
                // assume that DST is observed from the 2nd Sunday in March to the 1st Sunday in November,
                // to avoid including every timezone transition for the duration of our events
                // (especially in case an event repeats forever)
                $component->RRULE = $transition['isdst']
                    ? 'FREQ=YEARLY;BYMONTH=3;BYDAY=2SU'
                    : 'FREQ=YEARLY;BYMONTH=11;BYDAY=1SU';
            }

            $tzComponent->add($component);

            $tzFrom = $offset;
        }
    }

    /**
     * Formats a UTC offset in seconds (e.g. `-12600`) as `±HHMM` (e.g. `-0330`).
     */
    private function formatTzOffset(int $offset): string
    {
        $absOffset = abs($offset);

        return sprintf(
            '%s%02d%02d',
            $offset >= 0 ? '+' : '-',
            intdiv($absOffset, 3600),
            intdiv($absOffset % 3600, 60),
        );
    }
}
