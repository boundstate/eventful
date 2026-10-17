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
     * Returns whether a date is in a fixed offset timezone (e.g. `+02:00` or `EST`),
     * rather than a timezone identifier (e.g. `America/Toronto` or `UTC`).
     */
    public static function hasFixedOffset(DateTimeInterface $date): bool
    {
        return $date->getTimezone()->getLocation() === false;
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
            DateIntervalFormatter::pattern($formatter->locale, self::dateSkeleton($format)),
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
        if (! $endDate instanceof DateTimeInterface) {
            return self::formatDate($startDate, $format, $timezone);
        }

        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        // compares calendar dates, since an event can end the next day in less than 24 hours
        return DateIntervalFormatter::format($formatter, $startDate, $endDate, self::dateSkeleton($format));
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
                $displayTimezone,
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

        $isSameDay = $formatter->asDate($startDate, 'yyyy-MM-dd') === $formatter->asDate($endDate, 'yyyy-MM-dd');

        if ($isSameDay) {
            return self::formatDate($startDate, $format, $timezone).
                ' ⋅ '.
                self::formatTimeRange(
                    $startDate,
                    $endDate,
                    format: $format,
                    timezone: $timezone,
                    displayTimezone: $displayTimezone,
                );
        }

        $formatted = DateIntervalFormatter::join(
            $formatter->locale,
            self::formatDatetime($formatter, $startDate, $format),
            self::formatDatetime($formatter, $endDate, $format),
        );

        if ($displayTimezone) {
            $formatted .= ' '.self::formatTimezone($formatter, $startDate);
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

        $formatted = DateIntervalFormatter::format(
            $formatter,
            $startDate,
            $endDate,
            self::timeSkeleton($formatter, $format, $startDate, $endDate),
        );

        if ($displayTimezone) {
            $formatted .= ' '.self::formatTimezone($formatter, $startDate);
        }

        return $formatted;
    }

    private static function formatDatetime(
        Formatter $formatter,
        DateTimeInterface $date,
        string $format,
    ): string {
        return $formatter->asDate($date, DateIntervalFormatter::pattern($formatter->locale, self::dateSkeleton($format))).
            ' ⋅ '.
            $formatter->asDate($date, DateIntervalFormatter::pattern(
                $formatter->locale,
                self::timeSkeleton($formatter, $format, $date),
            ));
    }

    private static function dateSkeleton(string $format): string
    {
        return $format === 'long' ? 'yMMMMd' : 'yMMMd';
    }

    /**
     * Returns the skeleton for the locale's 12 or 24 hour clock, without minutes for
     * the medium format if all the times are on the hour (e.g. "10 – 11 AM").
     */
    private static function timeSkeleton(
        Formatter $formatter,
        ?string $format,
        DateTimeInterface ...$dates,
    ): string {
        // the locale's preferred hour pattern, without quoted text (e.g. "HH 'Uhr'" in German)
        $hourPattern = preg_replace("/'[^']*'/", '', DateIntervalFormatter::pattern($formatter->locale, 'j'));
        $skeleton = preg_match('/[hK]/', (string) $hourPattern) ? 'h' : 'H';

        foreach ($dates as $date) {
            if ($format !== 'medium' || $formatter->asTime($date, 'mm') !== '00') {
                return $skeleton.'m';
            }
        }

        return $skeleton;
    }

    private static function formatTimezone(Formatter $formatter, DateTimeInterface $date): string
    {
        return $formatter->asTime($date, 'zzz');
    }

    private static function getFormatter(?string $timezone): Formatter
    {
        $formatter = clone Craft::$app->getFormatter();
        $formatter->timeZone = $timezone;

        return $formatter;
    }
}
