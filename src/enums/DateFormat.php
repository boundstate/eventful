<?php

namespace boundstate\eventful\enums;

use InvalidArgumentException;

/**
 * Format for event dates: `MEDIUM` or `LONG`, optionally limited to only the dates or the times.
 */
enum DateFormat: string
{
    case MEDIUM = 'medium';
    case LONG = 'long';
    case MEDIUM_DATE = 'mediumDate';
    case LONG_DATE = 'longDate';
    case MEDIUM_TIME = 'mediumTime';
    case LONG_TIME = 'longTime';

    /**
     * Returns the format for a case or its value (e.g. `'mediumDate'`).
     *
     * @throws InvalidArgumentException if the value isn't a valid format
     */
    public static function parse(self|string $format): self
    {
        if ($format instanceof self) {
            return $format;
        }

        return self::tryFrom($format) ?? throw new InvalidArgumentException(
            "Invalid event date format \"$format\". Use one of: ".
            implode(', ', array_map(fn (self $case): string => $case->value, self::cases())).'.',
        );
    }

    public function length(): FormatLength
    {
        return in_array($this, [self::LONG, self::LONG_DATE, self::LONG_TIME], true)
            ? FormatLength::LONG
            : FormatLength::MEDIUM;
    }

    public function hasDates(): bool
    {
        return ! in_array($this, [self::MEDIUM_TIME, self::LONG_TIME], true);
    }

    public function hasTimes(): bool
    {
        return ! in_array($this, [self::MEDIUM_DATE, self::LONG_DATE], true);
    }
}
