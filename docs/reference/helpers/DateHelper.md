---
title: DateHelper
---

# Abstract Class DateHelper

## Methods

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

```php
public static formatDate(DateTimeInterface $startDate, "medium"|"long" $format = 'medium', ?string $timezone = null): string
```

|              |                                             |     |
| ------------ | ------------------------------------------- | --- |
| `$startDate` | <span class="code">DateTimeInterface</span> |     |
| `$format`    | <span class="code">"medium"\|"long"</span>  |     |
| `$timezone`  | <span class="code">?string</span>           |     |
| **return**   | <span class="code">string</span>            |     |

### formatDateRange

```php
public static formatDateRange(DateTimeInterface $startDate, ?DateTimeInterface $endDate, "medium"|"long" $format = 'medium', ?string $timezone = null): string
```

|              |                                              |     |
| ------------ | -------------------------------------------- | --- |
| `$startDate` | <span class="code">DateTimeInterface</span>  |     |
| `$endDate`   | <span class="code">?DateTimeInterface</span> |     |
| `$format`    | <span class="code">"medium"\|"long"</span>   |     |
| `$timezone`  | <span class="code">?string</span>            |     |
| **return**   | <span class="code">string</span>             |     |

### formatDatetimeRange

```php
public static formatDatetimeRange(DateTimeInterface $startDate, DateTimeInterface $endDate, string $format = 'medium', ?string $timezone = null, bool $displayTimezone = false): string
```

|                    |                                             |     |
| ------------------ | ------------------------------------------- | --- |
| `$startDate`       | <span class="code">DateTimeInterface</span> |     |
| `$endDate`         | <span class="code">DateTimeInterface</span> |     |
| `$format`          | <span class="code">string</span>            |     |
| `$timezone`        | <span class="code">?string</span>           |     |
| `$displayTimezone` | <span class="code">bool</span>              |     |
| **return**         | <span class="code">string</span>            |     |

### formatTimeRange

```php
public static formatTimeRange(DateTimeInterface $startDate, DateTimeInterface $endDate, ?string $format = 'medium', ?string $timezone = null, bool $displayTimezone = false): string
```

|                    |                                             |     |
| ------------------ | ------------------------------------------- | --- |
| `$startDate`       | <span class="code">DateTimeInterface</span> |     |
| `$endDate`         | <span class="code">DateTimeInterface</span> |     |
| `$format`          | <span class="code">?string</span>           |     |
| `$timezone`        | <span class="code">?string</span>           |     |
| `$displayTimezone` | <span class="code">bool</span>              |     |
| **return**         | <span class="code">string</span>            |     |
