<?php

namespace boundstate\eventful\helpers;

use Craft;
use DateTime;
use DateTimeInterface;
use yii\i18n\Formatter;

abstract class DateHelper
{
    /**
     * Returns a new DateTime object with the time set to the given time.
     */
    public static function setTime(
        DateTimeInterface $date,
        DateTimeInterface $time,
    ): DateTime {
        return DateTime::createFromInterface($date)->modify(
            $time->format('H:i'),
        );
    }

    /**
     * Returns a new DateTime object with the time set to the end of the day.
     */
    public static function endOfDay(DateTimeInterface $date): DateTime
    {
        return DateTime::createFromInterface($date)->modify(
            '+1 day -1 microsecond',
        );
    }

    /**
     * @param  'medium'|'long'  $format
     */
    public static function formatDate(
        DateTimeInterface $startDate,
        string $format = 'medium',
        ?string $timezone = null,
    ): string {
        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        return $formatter->asDate(
            $startDate,
            $format === 'long' ? 'MMMM d, yyyy' : 'MMM d, yyyy',
        );
    }

    /**
     * @param  'medium'|'long'  $format
     */
    public static function formatDateRange(
        DateTimeInterface $startDate,
        ?DateTimeInterface $endDate,
        string $format = 'medium',
        ?string $timezone = null,
    ): string {
        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        $isSameDay = $endDate instanceof DateTimeInterface && $startDate->diff($endDate)->days === 0;

        $startDateFormat = $format === 'long' ? 'MMMM d, yyyy' : 'MMM d, yyyy';
        $endDateFormat = $startDateFormat;

        if ($endDate instanceof DateTimeInterface && ! $isSameDay) {
            if ($startDate->format('yyyy') === $endDate->format('yyyy')) {
                $startDateFormat = $format === 'long' ? 'MMMM d' : 'MMM d';
                if ($startDate->format('MMM') === $endDate->format('MMM')) {
                    $endDateFormat = 'd, yyyy';
                }
            }
        }

        $formatted = $formatter->asDate($startDate, $startDateFormat);

        if (! $isSameDay && $endDate instanceof DateTimeInterface) {
            $formatted .= ' - '.$formatter->asDate($endDate, $endDateFormat);
        }

        return $formatted;
    }

    public static function formatDatetimeRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $format = 'medium',
        ?string $timezone = null,
        bool $displayTimezone = false,
    ): string {
        if (str_contains($format, 'Time')) {
            return self::formatTimeRange(
                $startDate,
                $endDate,
                str_replace('Time', '', $format),
                $timezone,
            );
        }

        if (str_contains($format, 'Date')) {
            return self::formatDateRange(
                $startDate,
                $endDate,
                str_replace('Date', '', $format),
                $timezone,
            );
        }

        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        $formattedStartDate = $formatter->asDate($startDate, $format);
        $formattedEndDate = $formatter->asDate($endDate, $format);

        $isSameDay = $formattedStartDate === $formattedEndDate;

        if ($isSameDay) {
            $formatted =
                $formattedStartDate.
                ' ⋅ '.
                self::formatTimeRange(
                    $startDate,
                    $endDate,
                    format: $format,
                    timezone: $timezone,
                    displayTimezone: $displayTimezone,
                );
        } else {
            $formatted =
                $formattedStartDate.
                ' ⋅ '.
                self::formatTime($formatter, $startDate, $format).
                ' – '.
                $formattedEndDate.
                ' ⋅ '.
                self::formatTime($formatter, $endDate, $format);

            if ($displayTimezone) {
                $formatted .= ' '.self::formatTimezone($formatter, $startDate);
            }
        }

        return $formatted;
    }

    public static function formatTimeRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?string $format = 'medium',
        ?string $timezone = null,
        bool $displayTimezone = false,
    ): string {
        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        $formatted = self::formatTime($formatter, $startDate, $format).
            ' – '.
            self::formatTime($formatter, $endDate, $format);

        if ($displayTimezone) {
            $formatted .= ' '.self::formatTimezone($formatter, $startDate);
        }

        return $formatted;
    }

    private static function formatTime(
        Formatter $formatter,
        DateTimeInterface $date,
        ?string $format = 'medium',
    ): string {
        $format =
            $format === 'medium' && $formatter->asTime($date, 'mm') == '00'
                ? 'ha'
                : 'h:mma';

        return $formatter->asTime($date, $format);
    }

    private static function formatTimezone(Formatter $formatter, DateTimeInterface $date): string
    {
        return $formatter->asTime($date, 'zzz');
    }

    private static function getFormatter(?string $timezone): Formatter
    {
        $formatter = clone Craft::$app->getFormatter();
        $formatter->locale = 'en-US'; //  "AM" and "PM" instead of "a.m." and "p.m."
        $formatter->timeZone = $timezone;

        return $formatter;
    }
}
