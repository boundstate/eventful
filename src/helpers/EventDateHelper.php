<?php

namespace boundstate\eventful\helpers;

use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\Occurrence;
use Craft;
use DateTimeInterface;
use InvalidArgumentException;
use Recurr\Recurrence;

/**
 * Helper class for working with event dates.
 */
abstract class EventDateHelper
{
    private const FORMATS = ['medium', 'long', 'mediumDate', 'longDate', 'mediumTime', 'longTime'];

    /**
     * Formats the date (and times, unless all day) of an `EventDate` or one of its occurrences.
     * A repeating `EventDate` is formatted from its first start to its last end (or only its first start if it
     * repeats forever), with the times of each occurrence. All day dates are formatted without times
     * (or as "All day" for the time formats), and aren't converted to the given timezone.
     *
     * @param  'medium'|'long'|'mediumDate'|'longDate'|'mediumTime'|'longTime'  $format
     * @param  ?bool  $allDay  Whether a `Recurrence` that isn't an {@link Occurrence} is all day
     *
     * @throws InvalidArgumentException if the format isn't one of the above
     */
    public static function formatDate(
        EventDate|Recurrence $event,
        string $format = 'medium',
        ?string $timezone = null,
        ?bool $allDay = null,
        bool $displayTimezone = false,
    ): string {
        // Twig templates can pass any string
        // @phpstan-ignore function.alreadyNarrowedType
        if (! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException(
                "Invalid event date format \"$format\". Use one of: ".implode(', ', self::FORMATS).'.',
            );
        }

        if ($event instanceof EventDate) {
            if ($event->repeat) {
                return self::formatRepeating($event, $format, $timezone, $displayTimezone);
            }

            // a non-repeating event is formatted the same as its only occurrence
            $event = new Occurrence($event->start, $event->end, allDay: $event->allDay);
        }

        $allDay ??= $event instanceof Occurrence && $event->allDay;

        if ($allDay) {
            return self::formatAllDay($event->getStart(), $event->getEnd(), $format);
        }

        return DateHelper::formatDatetimeRange(
            $event->getStart(),
            $event->getEnd(),
            format: $format,
            timezone: $timezone,
            displayTimezone: $displayTimezone,
        );
    }

    /**
     * Formats the value of an `EventDate` field.
     *
     * @param  'medium'|'long'|'mediumDate'|'longDate'|'mediumTime'|'longTime'  $format
     *
     * @deprecated in 2.2.0. Use `formatDate()` instead.
     */
    public static function formatDateRange(
        EventDate $event,
        string $format = 'medium',
        ?string $timezone = null,
        bool $displayTimezone = false,
    ): string {
        Craft::$app->getDeprecator()->log(
            'eventful.formatDateRange',
            '`EventDateHelper::formatDateRange()` has been deprecated. Use `formatDate()` instead.',
        );

        return self::formatDate($event, $format, $timezone, displayTimezone: $displayTimezone);
    }

    private static function formatRepeating(
        EventDate $event,
        string $format,
        ?string $timezone,
        bool $displayTimezone,
    ): string {
        if ($event->allDay) {
            return self::formatAllDay($event->getFirstStartDate(), $event->getLastEndDate(), $format);
        }

        $parts = [];

        if (! str_contains($format, 'Time')) {
            $parts[] = DateHelper::formatDateRange(
                $event->getFirstStartDate(),
                $event->getLastEndDate(),
                format: str_replace('Date', '', $format),
                timezone: $timezone,
            );
        }

        if (! str_contains($format, 'Date')) {
            $parts[] = DateHelper::formatTimeRange(
                $event->start,
                $event->end,
                format: str_replace('Time', '', $format),
                timezone: $timezone,
                displayTimezone: $displayTimezone,
            );
        }

        return implode(' ⋅ ', $parts);
    }

    /**
     * All day dates aren't converted to a timezone, since they don't have times.
     */
    private static function formatAllDay(
        DateTimeInterface $start,
        ?DateTimeInterface $end,
        string $format,
    ): string {
        if (str_contains($format, 'Time')) {
            return Craft::t('eventful', 'All day');
        }

        return DateHelper::formatDateRange($start, $end, format: str_replace('Date', '', $format));
    }
}
