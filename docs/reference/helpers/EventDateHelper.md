---
title: EventDateHelper
---

# Abstract Class EventDateHelper

Helper class for working with event dates.

## Methods

### formatDate

Formats the date (and times, unless all day) of an `EventDate` or one of its occurrences.

A repeating `EventDate` is formatted from its first start to its last end (or only its first start if it
repeats forever), with the times of each occurrence. All day dates are formatted without times
(or as "All day" for the time formats), and aren't converted to the given timezone.

```php
public static formatDate(EventDate|Recurrence $event, "medium"|"long"|"mediumDate"|"longDate"|"mediumTime"|"longTime" $format = 'medium', ?string $timezone = null, ?bool $allDay = null, bool $displayTimezone = false): string
```

|                    |                                                                                                                                                                                                                          |                                                                    |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------ |
| `$event`           | <span class="code">[EventDate](../models/EventDate.md '\\boundstate\\eventful\\models\\EventDate')\|[Recurrence](https://github.com/simshaun/recurr/blob/v5.0.3/src/Recurr/Recurrence.php '\\Recurr\\Recurrence')</span> |                                                                    |
| `$format`          | <span class="code">"medium"\|"long"\|"mediumDate"\|"longDate"\|"mediumTime"\|"longTime"</span>                                                                                                                           |                                                                    |
| `$timezone`        | <span class="code">?string</span>                                                                                                                                                                                        |                                                                    |
| `$allDay`          | <span class="code">?bool</span>                                                                                                                                                                                          | Whether a `Recurrence` that isn't an {@link Occurrence} is all day |
| `$displayTimezone` | <span class="code">bool</span>                                                                                                                                                                                           |                                                                    |
| **return**         | <span class="code">string</span>                                                                                                                                                                                         |                                                                    |
| **throws**         | <span class="code">InvalidArgumentException</span>                                                                                                                                                                       | if the format isn't one of the above                               |

### formatDateRange

Formats the value of an `EventDate` field.

!!! warning "Deprecated"

    in 2.2.0. Use `formatDate()` instead.

```php
public static formatDateRange(EventDate $event, "medium"|"long"|"mediumDate"|"longDate"|"mediumTime"|"longTime" $format = 'medium', ?string $timezone = null, bool $displayTimezone = false): string
```

|                    |                                                                                                           |     |
| ------------------ | --------------------------------------------------------------------------------------------------------- | --- |
| `$event`           | <span class="code">[EventDate](../models/EventDate.md '\\boundstate\\eventful\\models\\EventDate')</span> |     |
| `$format`          | <span class="code">"medium"\|"long"\|"mediumDate"\|"longDate"\|"mediumTime"\|"longTime"</span>            |     |
| `$timezone`        | <span class="code">?string</span>                                                                         |     |
| `$displayTimezone` | <span class="code">bool</span>                                                                            |     |
| **return**         | <span class="code">string</span>                                                                          |     |
