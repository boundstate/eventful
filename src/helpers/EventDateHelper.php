<?php

namespace boundstate\eventful\helpers;

use boundstate\eventful\enums\DateFormat;
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
    /**
     * Formats the date (and times, unless all day) of an `EventDate` or one of its occurrences.
     * A repeating `EventDate` is formatted from its first start to its last end (or only its first start if it
     * repeats forever), with the times of each occurrence. All day dates are formatted without times
     * (or as "All day" for the time formats), and aren't converted to the given timezone.
     *
     * @param  DateFormat|string  $format  A format or its value (e.g. `'mediumDate'`)
     * @param  ?bool  $allDay  Whether a `Recurrence` that isn't an {@link Occurrence} is all day
     *
     * @throws InvalidArgumentException if the format isn't valid
     */
    public static function formatDate(
        EventDate|Recurrence $event,
        DateFormat|string $format = DateFormat::MEDIUM,
        ?string $timezone = null,
        ?bool $allDay = null,
        bool $displayTimezone = false,
    ): string {
        $format = DateFormat::parse($format);

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
     * @deprecated in 2.2.0. Use `formatDate()` instead.
     */
    public static function formatDateRange(
        EventDate $event,
        DateFormat|string $format = DateFormat::MEDIUM,
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
        DateFormat $format,
        ?string $timezone,
        bool $displayTimezone,
    ): string {
        if ($event->allDay) {
            return self::formatAllDay($event->getFirstStartDate(), $event->getLastEndDate(), $format);
        }

        $parts = [];

        if ($format->hasDates()) {
            $parts[] = DateHelper::formatDateRange(
                $event->getFirstStartDate(),
                $event->getLastEndDate(),
                format: $format->length(),
                timezone: $timezone,
            );
        }

        if ($format->hasTimes()) {
            $parts[] = DateHelper::formatTimeRange(
                $event->start,
                $event->end,
                format: $format->length(),
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
        DateFormat $format,
    ): string {
        if (! $format->hasDates()) {
            return Craft::t('eventful', 'All day');
        }

        return DateHelper::formatDateRange($start, $end, format: $format->length());
    }
}
