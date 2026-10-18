<?php

namespace boundstate\eventful\enums;

use InvalidArgumentException;

/**
 * Length of formatted dates and times.
 */
enum FormatLength: string
{
    case MEDIUM = 'medium';
    case LONG = 'long';

    /**
     * Returns the length for a case or its value (e.g. `'long'`).
     *
     * @throws InvalidArgumentException if the value isn't a valid length
     */
    public static function parse(self|string $length): self
    {
        if ($length instanceof self) {
            return $length;
        }

        return self::tryFrom($length) ?? throw new InvalidArgumentException(
            "Invalid format length \"$length\". Use one of: ".
            implode(', ', array_map(fn (self $case): string => $case->value, self::cases())).'.',
        );
    }
}
