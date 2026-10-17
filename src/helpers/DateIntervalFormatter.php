<?php

namespace boundstate\eventful\helpers;

use DateTimeInterface;
use IntlDatePatternGenerator;
use Locale;
use ResourceBundle;
use yii\i18n\Formatter;

/**
 * Formats date and time ranges with the locale's interval patterns (e.g. "Mar 5 – 8, 2026" or "10:00 – 11:30 AM"),
 * like ICU's `DateIntervalFormat`, which PHP doesn't provide.
 *
 * @internal
 */
abstract class DateIntervalFormatter
{
    /**
     * Calendar fields from greatest to smallest, with a pattern that compares dates down to that field.
     */
    private const FIELDS = [
        'y' => 'y',
        'M' => 'y-MM',
        'd' => 'y-MM-dd',
        'a' => 'y-MM-dd a',
        'H' => 'y-MM-dd HH',
        'm' => 'y-MM-dd HH:mm',
    ];

    /**
     * @var array<string, IntlDatePatternGenerator>
     */
    private static array $generators = [];

    /**
     * @var array<string, ?string>
     */
    private static array $resources = [];

    /**
     * Formats a range of dates with a skeleton (e.g. `yMMMd` or `hm`), showing the fields they share only once.
     */
    public static function format(
        Formatter $formatter,
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $skeleton,
    ): string {
        $field = self::greatestDifference($formatter, $start, $end, $skeleton);

        if ($field === null) {
            return $formatter->asDate($start, self::pattern($formatter->locale, $skeleton));
        }

        $parts = self::intervalPattern($formatter->locale, $skeleton, $field);

        if ($parts === null) {
            return self::join(
                $formatter->locale,
                $formatter->asDate($start, self::pattern($formatter->locale, $skeleton)),
                $formatter->asDate($end, self::pattern($formatter->locale, $skeleton)),
            );
        }

        return $formatter->asDate($start, $parts[0]).$formatter->asDate($end, $parts[1]);
    }

    /**
     * Returns the locale's pattern for a skeleton (e.g. `MMM d, y` for `yMMMd` in English).
     */
    public static function pattern(string $locale, string $skeleton): string
    {
        self::$generators[$locale] ??= new IntlDatePatternGenerator($locale);

        return (string) self::$generators[$locale]->getBestPattern($skeleton);
    }

    /**
     * Joins two formatted dates that don't share any fields (e.g. "Mar 5, 2026, 10 PM – Mar 6, 2026, 1 AM").
     */
    public static function join(string $locale, string $start, string $end): string
    {
        return strtr(self::resource($locale, 'intervalFormats', 'fallback') ?? '{0} – {1}', ['{0}' => $start, '{1}' => $end]);
    }

    /**
     * Returns the greatest field that differs between the dates, or null if they don't differ in the skeleton's fields.
     */
    private static function greatestDifference(
        Formatter $formatter,
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $skeleton,
    ): ?string {
        // compare 12 and 24 hour clocks the same way
        $skeletonFields = str_split(strtr($skeleton, ['h' => 'H', 'K' => 'H', 'k' => 'H']));
        $smallest = max(array_keys(array_intersect(array_keys(self::FIELDS), $skeletonFields)));

        foreach (array_slice(self::FIELDS, 0, $smallest + 1) as $field => $pattern) {
            if ($formatter->asDate($start, $pattern) !== $formatter->asDate($end, $pattern)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Returns the locale's interval pattern for a skeleton and the greatest field that differs,
     * split into the patterns for the start and end dates.
     *
     * @return ?array{string, string}
     */
    private static function intervalPattern(string $locale, string $skeleton, string $field): ?array
    {
        // the interval patterns use the 12 hour field when the skeleton does, and the 24 hour field for AM/PM otherwise
        $is12Hour = str_contains($skeleton, 'h');
        $field = match (true) {
            $field === 'H' && $is12Hour => 'h',
            $field === 'a' && ! $is12Hour => 'H',
            default => $field,
        };

        $pattern = self::resource($locale, 'intervalFormats', $skeleton, $field);

        // locales only have interval patterns for abbreviated months, so widen them for full months
        if ($pattern === null && str_contains($skeleton, 'MMMM')) {
            $pattern = self::resource($locale, 'intervalFormats', str_replace('MMMM', 'MMM', $skeleton), $field);
            $pattern = $pattern === null ? null : self::widenMonths($pattern);
        }

        return $pattern === null ? null : self::split($pattern);
    }

    /**
     * Splits an interval pattern where its first field repeats, like ICU does
     * (e.g. `MMM d – d, y` into `MMM d – ` and `d, y`).
     *
     * @return ?array{string, string}
     */
    private static function split(string $pattern): ?array
    {
        $seen = [];
        $quoted = false;

        for ($i = 0, $length = strlen($pattern); $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === "'") {
                $quoted = ! $quoted;
            } elseif (! $quoted && ctype_alpha($char) && ($i === 0 || $pattern[$i - 1] !== $char)) {
                if (isset($seen[$char])) {
                    return [substr($pattern, 0, $i), substr($pattern, $i)];
                }

                $seen[$char] = true;
            }
        }

        return null;
    }

    /**
     * Widens abbreviated month fields (`MMM`) to full months (`MMMM`), outside of quoted text.
     */
    private static function widenMonths(string $pattern): string
    {
        $parts = preg_split("/('[^']*')/", $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$pattern];

        return implode('', array_map(
            fn (string $part) => str_starts_with($part, "'")
                ? $part
                : (string) preg_replace('/(?<![ML])(M|L)\1\1(?![ML])/', '$1$1$1$1', $part),
            $parts,
        ));
    }

    /**
     * Returns a string from the locale's Gregorian calendar data, falling back to its parent locales.
     * PHP's `ResourceBundle` only falls back for whole locales, not for keys missing from a locale.
     */
    private static function resource(string $locale, string ...$path): ?string
    {
        $cacheKey = $locale.'/'.implode('/', $path);

        if (array_key_exists($cacheKey, self::$resources)) {
            return self::$resources[$cacheKey];
        }

        foreach (self::localeChain($locale) as $bundleLocale) {
            $value = ResourceBundle::create($bundleLocale, null, false);

            foreach (['calendar', 'gregorian', ...$path] as $key) {
                $value = $value instanceof ResourceBundle ? $value->get($key, false) : null;
            }

            if (is_string($value)) {
                return self::$resources[$cacheKey] = $value;
            }
        }

        return self::$resources[$cacheKey] = null;
    }

    /**
     * Returns the locale and its parents (e.g. `en_GB`, `en_001`, `en` and `root`).
     *
     * @return string[]
     */
    private static function localeChain(string $locale): array
    {
        $locale = (string) Locale::canonicalize($locale);
        $chain = [];

        while ($locale !== '' && $locale !== 'root' && ! in_array($locale, $chain, true)) {
            $chain[] = $locale;
            $parent = ResourceBundle::create($locale, null, false)?->get('%%Parent', false);
            $locale = is_string($parent) ? $parent : substr($locale, 0, (int) strrpos($locale, '_'));
        }

        $chain[] = 'root';

        return $chain;
    }
}
