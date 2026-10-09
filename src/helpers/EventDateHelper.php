<?php

namespace boundstate\eventful\helpers;

use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\Occurrence;
use Craft;
use Recurr\Recurrence;

/**
 * Helper class for working with event dates.
 */
abstract class EventDateHelper
{
    /**
     * Formats a `Recurrence` as a date (and times if not all day).
     * All day dates aren't converted to the given timezone, since they don't have times.
     *
     * @param  'medium'|'long'  $format
     * @param  ?bool  $allDay  Defaults to whether the occurrence is all day, if it's an {@link Occurrence}
     */
    public static function formatDate(
        Recurrence $event,
        string $format = 'medium',
        ?string $timezone = null,
        ?bool $allDay = null,
        bool $displayTimezone = false,
    ): string {
        $allDay ??= $event instanceof Occurrence && $event->allDay;

        return $allDay
            ? DateHelper::formatDate(
                $event->getStart(),
                format: $format,
            )
            : DateHelper::formatDatetimeRange(
                $event->getStart(),
                $event->getEnd(),
                format: $format,
                timezone: $timezone,
                displayTimezone: $displayTimezone,
            );
    }

    /**
     * Formats the value of an `EventDate` field as a date range.
     * All day events are formatted without times (or as "All day" for the time formats),
     * and aren't converted to the given timezone.
     *
     * @param  'medium'|'long'|'mediumDate'|'longDate'|'mediumTime'|'longTime'  $format
     */
    public static function formatDateRange(
        EventDate $event,
        string $format = 'medium',
        ?string $timezone = null,
        bool $displayTimezone = false,
    ): string {
        if ($event->allDay) {
            if (str_contains($format, 'Time')) {
                return Craft::t('eventful', 'All day');
            }

            return DateHelper::formatDateRange(
                $event->getFirstStartDate(),
                $event->getLastEndDate(),
                format: str_replace('Date', '', $format),
            );
        }

        if ($event->repeat) {
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

        if (str_contains($format, 'Date')) {
            return DateHelper::formatDateRange(
                $event->start,
                $event->end,
                format: str_replace('Date', '', $format),
                timezone: $timezone,
            );
        }

        if (str_contains($format, 'Time')) {
            return DateHelper::formatTimeRange(
                $event->start,
                $event->end,
                format: str_replace('Time', '', $format),
                timezone: $timezone,
                displayTimezone: $displayTimezone,
            );
        }

        return DateHelper::formatDatetimeRange(
            $event->start,
            $event->end,
            timezone: $timezone,
            displayTimezone: $displayTimezone,
        );
    }
}
