---
title: DateHelper
---

# Abstract Class DateHelper

## Constants

### EVENT_DEFINE_TIMEZONE_LABEL

See <span class="code">[DefineTimezoneLabelEvent](../events/DefineTimezoneLabelEvent.md '\\boundstate\\eventful\\events\\DefineTimezoneLabelEvent'){ data-preview }</span>

## Methods

### systemTimezone

Returns the system timezone.

Unlike `Craft::$app->getTimeZone()`, this isn't the user's preferred timezone in control panel requests.

```php
public static systemTimezone(): string
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| **return** | <span class="code">string</span> |     |

### setTime

Returns a new DateTime object with the time set to the given time.

```php
public static setTime(DateTimeInterface $date, DateTimeInterface $time): DateTime
```

|            |                                             |     |
| ---------- | ------------------------------------------- | --- |
| `$date`    | <span class="code">DateTimeInterface</span> |     |
| `$time`    | <span class="code">DateTimeInterface</span> |     |
| **return** | <span class="code">DateTime</span>          |     |

### hasFixedOffset

Returns whether a date is in a fixed offset timezone (e.g. `+02:00` or `EST`),
rather than a timezone identifier (e.g. `America/Toronto` or `UTC`).

```php
public static hasFixedOffset(DateTimeInterface $date): bool
```

|            |                                             |     |
| ---------- | ------------------------------------------- | --- |
| `$date`    | <span class="code">DateTimeInterface</span> |     |
| **return** | <span class="code">bool</span>              |     |

### endOfDay

Returns a new DateTime object with the time set to the end of the day.

```php
public static endOfDay(DateTimeInterface $date): DateTime
```

|            |                                             |     |
| ---------- | ------------------------------------------- | --- |
| `$date`    | <span class="code">DateTimeInterface</span> |     |
| **return** | <span class="code">DateTime</span>          |     |

### formatDate

Formats a date (without a time).

```php
public static formatDate(DateTimeInterface $startDate, FormatLength|string $format = \boundstate\eventful\enums\FormatLength::MEDIUM, ?string $timezone = null): string
```

|              |                                                                                                                          |                                       |
| ------------ | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------- |
| `$startDate` | <span class="code">DateTimeInterface</span>                                                                              |                                       |
| `$format`    | <span class="code">[FormatLength](../enums/FormatLength.md '\\boundstate\\eventful\\enums\\FormatLength')\|string</span> | A length or its value (e.g. `'long'`) |
| `$timezone`  | <span class="code">?string</span>                                                                                        |                                       |
| **return**   | <span class="code">string</span>                                                                                         |                                       |
| **throws**   | <span class="code">InvalidArgumentException</span>                                                                       | if the format isn't valid             |

### formatDateRange

Formats a range of dates (without times).

```php
public static formatDateRange(DateTimeInterface $startDate, ?DateTimeInterface $endDate, FormatLength|string $format = \boundstate\eventful\enums\FormatLength::MEDIUM, ?string $timezone = null): string
```

|              |                                                                                                                          |                                       |
| ------------ | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------- |
| `$startDate` | <span class="code">DateTimeInterface</span>                                                                              |                                       |
| `$endDate`   | <span class="code">?DateTimeInterface</span>                                                                             |                                       |
| `$format`    | <span class="code">[FormatLength](../enums/FormatLength.md '\\boundstate\\eventful\\enums\\FormatLength')\|string</span> | A length or its value (e.g. `'long'`) |
| `$timezone`  | <span class="code">?string</span>                                                                                        |                                       |
| **return**   | <span class="code">string</span>                                                                                         |                                       |
| **throws**   | <span class="code">InvalidArgumentException</span>                                                                       | if the format isn't valid             |

### formatDatetimeRange

Formats a range of dates and times, or only the dates or the times, depending on the format.

```php
public static formatDatetimeRange(DateTimeInterface $startDate, DateTimeInterface $endDate, DateFormat|string $format = \boundstate\eventful\enums\DateFormat::MEDIUM, ?string $timezone = null, bool $displayTimezone = false): string
```

|                    |                                                                                                                    |                           |
| ------------------ | ------------------------------------------------------------------------------------------------------------------ | ------------------------- |
| `$startDate`       | <span class="code">DateTimeInterface</span>                                                                        |                           |
| `$endDate`         | <span class="code">DateTimeInterface</span>                                                                        |                           |
| `$format`          | <span class="code">[DateFormat](../enums/DateFormat.md '\\boundstate\\eventful\\enums\\DateFormat')\|string</span> |                           |
| `$timezone`        | <span class="code">?string</span>                                                                                  |                           |
| `$displayTimezone` | <span class="code">bool</span>                                                                                     |                           |
| **return**         | <span class="code">string</span>                                                                                   |                           |
| **throws**         | <span class="code">InvalidArgumentException</span>                                                                 | if the format isn't valid |

### formatTimeRange

Formats a range of times (without dates).

```php
public static formatTimeRange(DateTimeInterface $startDate, DateTimeInterface $endDate, FormatLength|string $format = \boundstate\eventful\enums\FormatLength::MEDIUM, ?string $timezone = null, bool $displayTimezone = false): string
```

|                    |                                                                                                                          |                                       |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------- |
| `$startDate`       | <span class="code">DateTimeInterface</span>                                                                              |                                       |
| `$endDate`         | <span class="code">DateTimeInterface</span>                                                                              |                                       |
| `$format`          | <span class="code">[FormatLength](../enums/FormatLength.md '\\boundstate\\eventful\\enums\\FormatLength')\|string</span> | A length or its value (e.g. `'long'`) |
| `$timezone`        | <span class="code">?string</span>                                                                                        |                                       |
| `$displayTimezone` | <span class="code">bool</span>                                                                                           |                                       |
| **return**         | <span class="code">string</span>                                                                                         |                                       |
| **throws**         | <span class="code">InvalidArgumentException</span>                                                                       | if the format isn't valid             |
