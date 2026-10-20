<?php

namespace boundstate\eventful\helpers;

use boundstate\eventful\enums\DateFormat;
use boundstate\eventful\enums\FormatLength;
use boundstate\eventful\events\DefineTimezoneLabelEvent;
use Craft;
use craft\helpers\App;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use yii\base\Event;
use yii\i18n\Formatter;

abstract class DateHelper
{
    /**
     * @see DefineTimezoneLabelEvent
     */
    const EVENT_DEFINE_TIMEZONE_LABEL = 'defineTimezoneLabel';

    /**
     * Returns the system timezone.
     *
     * Unlike `Craft::$app->getTimeZone()`, this isn't the user's preferred timezone in control panel requests.
     */
    public static function systemTimezone(): string
    {
        return App::parseEnv(
            Craft::$app->getConfig()->getGeneral()->timezone
                ?? Craft::$app->getProjectConfig()->get('system.timeZone'),
        );
    }

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
     * Formats a date (without a time).
     *
     * @param  FormatLength|string  $format  A length or its value (e.g. `'long'`)
     *
     * @throws InvalidArgumentException if the format isn't valid
     */
    public static function formatDate(
        DateTimeInterface $startDate,
        FormatLength|string $format = FormatLength::MEDIUM,
        ?string $timezone = null,
    ): string {
        $length = FormatLength::parse($format);

        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        return $formatter->asDate(
            $startDate,
            DateIntervalFormatter::pattern($formatter->locale, self::dateSkeleton($length)),
        );
    }

    /**
     * Formats a range of dates (without times).
     *
     * @param  FormatLength|string  $format  A length or its value (e.g. `'long'`)
     *
     * @throws InvalidArgumentException if the format isn't valid
     */
    public static function formatDateRange(
        DateTimeInterface $startDate,
        ?DateTimeInterface $endDate,
        FormatLength|string $format = FormatLength::MEDIUM,
        ?string $timezone = null,
    ): string {
        $length = FormatLength::parse($format);

        if (! $endDate instanceof DateTimeInterface) {
            return self::formatDate($startDate, $length, $timezone);
        }

        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        // compares calendar dates, since an event can end the next day in less than 24 hours
        return DateIntervalFormatter::format($formatter, $startDate, $endDate, self::dateSkeleton($length));
    }

    /**
     * Formats a range of dates and times, or only the dates or the times, depending on the format.
     *
     * @throws InvalidArgumentException if the format isn't valid
     */
    public static function formatDatetimeRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        DateFormat|string $format = DateFormat::MEDIUM,
        ?string $timezone = null,
        bool $displayTimezone = false,
    ): string {
        $format = DateFormat::parse($format);
        $length = $format->length();

        if (! $format->hasDates()) {
            return self::formatTimeRange($startDate, $endDate, $length, $timezone, $displayTimezone);
        }

        if (! $format->hasTimes()) {
            return self::formatDateRange($startDate, $endDate, $length, $timezone);
        }

        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        $isSameDay = $formatter->asDate($startDate, 'yyyy-MM-dd') === $formatter->asDate($endDate, 'yyyy-MM-dd');

        if ($isSameDay) {
            return self::formatDate($startDate, $length, $timezone).
                ' ⋅ '.
                self::formatTimeRange(
                    $startDate,
                    $endDate,
                    format: $length,
                    timezone: $timezone,
                    displayTimezone: $displayTimezone,
                );
        }

        $formatted = DateIntervalFormatter::join(
            $formatter->locale,
            self::formatDatetime($formatter, $startDate, $length),
            self::formatDatetime($formatter, $endDate, $length),
        );

        if ($displayTimezone) {
            $formatted .= ' '.self::formatTimezone($formatter, $startDate);
        }

        return $formatted;
    }

    /**
     * Formats a range of times (without dates).
     *
     * @param  FormatLength|string  $format  A length or its value (e.g. `'long'`)
     *
     * @throws InvalidArgumentException if the format isn't valid
     */
    public static function formatTimeRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        FormatLength|string $format = FormatLength::MEDIUM,
        ?string $timezone = null,
        bool $displayTimezone = false,
    ): string {
        $length = FormatLength::parse($format);

        $formatter = self::getFormatter(
            $timezone ?: $startDate->getTimezone()->getName(),
        );

        $formatted = DateIntervalFormatter::format(
            $formatter,
            $startDate,
            $endDate,
            self::timeSkeleton($formatter, $length, $startDate, $endDate),
        );

        if ($displayTimezone) {
            $formatted .= ' '.self::formatTimezone($formatter, $startDate);
        }

        return $formatted;
    }

    private static function formatDatetime(
        Formatter $formatter,
        DateTimeInterface $date,
        FormatLength $length,
    ): string {
        return $formatter->asDate($date, DateIntervalFormatter::pattern($formatter->locale, self::dateSkeleton($length))).
            ' ⋅ '.
            $formatter->asDate($date, DateIntervalFormatter::pattern(
                $formatter->locale,
                self::timeSkeleton($formatter, $length, $date),
            ));
    }

    private static function dateSkeleton(FormatLength $length): string
    {
        return $length === FormatLength::LONG ? 'yMMMMd' : 'yMMMd';
    }

    /**
     * Returns the skeleton for the locale's 12 or 24 hour clock, without minutes for
     * the medium format if all the times are on the hour (e.g. "10 – 11 AM").
     */
    private static function timeSkeleton(
        Formatter $formatter,
        FormatLength $length,
        DateTimeInterface ...$dates,
    ): string {
        // the locale's preferred hour pattern, without quoted text (e.g. "HH 'Uhr'" in German)
        $hourPattern = preg_replace("/'[^']*'/", '', DateIntervalFormatter::pattern($formatter->locale, 'j'));
        $skeleton = preg_match('/[hK]/', (string) $hourPattern) ? 'h' : 'H';

        foreach ($dates as $date) {
            if ($length === FormatLength::LONG || $formatter->asTime($date, 'mm') !== '00') {
                return $skeleton.'m';
            }
        }

        return $skeleton;
    }

    private static function formatTimezone(Formatter $formatter, DateTimeInterface $date): string
    {
        $event = new DefineTimezoneLabelEvent([
            'date' => DateTimeImmutable::createFromInterface($date)->setTimezone(new DateTimeZone($formatter->timeZone)),
            'timezone' => $formatter->timeZone,
            'locale' => $formatter->locale,
            'label' => $formatter->asTime($date, 'zzz'),
        ]);
        Event::trigger(self::class, self::EVENT_DEFINE_TIMEZONE_LABEL, $event);

        return $event->label;
    }

    private static function getFormatter(?string $timezone): Formatter
    {
        $formatter = clone Craft::$app->getFormatter();
        $formatter->timeZone = $timezone;

        return $formatter;
    }
}
