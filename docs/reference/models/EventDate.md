---
title: EventDate
---

# Class EventDate

<small class="block">Extends <span class="code">[Model](https://docs.craftcms.com/api/v5/craft-base-model.html '\\craft\\base\\Model')</span></small>

## Constants

### FREQ_DAILY

### FREQ_WEEKLY

### FREQ_MONTHLY

### FREQ_YEARLY

### ENDS_COUNT

### ENDS_UNTIL

### DEFAULT_DURATION

Default event duration in minutes, when no end is given (matches the input JS)

## Properties

### allowNeverEnding

```php
public bool $allowNeverEnding
```

### start

```php
public ?DateTime $start
```

### end

```php
public ?DateTime $end
```

### timezone

```php
public ?string $timezone
```

### allDay

```php
public bool $allDay
```

### repeat

```php
public bool $repeat
```

### interval

```php
public ?int $interval
```

### freq

```php
public ?string $freq
```

### byDay

```php
public array $byDay
```

### byMonthDay

```php
public array $byMonthDay
```

### ends

```php
public ?string $ends
```

### count

```php
public ?int $count
```

### until

```php
public ?DateTime $until
```

### inDates

```php
public array $inDates
```

### exDates

```php
public array $exDates
```

## Methods

### __construct

```php
public __construct(mixed $config = []): mixed
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| `$config`  | <span class="code">mixed</span> |     |
| **return** | <span class="code">mixed</span> |     |

### repeatsForCount

```php
public repeatsForCount(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |

### repeatsUntil

```php
public repeatsUntil(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |

### repeatsWeekly

```php
public repeatsWeekly(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |

### repeatsMonthly

```php
public repeatsMonthly(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |

### getRule

```php
public getRule(): ?Rule
```

|            |                                                                                                                        |     |
| ---------- | ---------------------------------------------------------------------------------------------------------------------- | --- |
| **return** | <span class="code">?[Rule](https://github.com/simshaun/recurr/blob/v5.0.3/src/Recurr/Rule.php '\\Recurr\\Rule')</span> |     |

### rules

```php
public rules(): array
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| **return** | <span class="code">array</span> |     |

### validateUntil

```php
public validateUntil(): void
```

### getRepeatDescription

```php
public getRepeatDescription(): ?string
```

|            |                                   |     |
| ---------- | --------------------------------- | --- |
| **return** | <span class="code">?string</span> |     |

### getOccurrences

Returns the occurrences, up to 732 of them (`ArrayTransformer::DEFAULT_LIMIT`).

Use [`getOccurrencesInRange()`](#getoccurrencesinrange) to get later occurrences.

```php
public getOccurrences(?ConstraintInterface $constraint = null): \Recurr\RecurrenceCollection<array-key,Occurrence>
```

|               |                                                                                                                                                                                              |     |
| ------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$constraint` | <span class="code">?[ConstraintInterface](https://github.com/simshaun/recurr/blob/v5.0.3/src/Recurr/Transformer/ConstraintInterface.php '\\Recurr\\Transformer\\ConstraintInterface')</span> |     |
| **return**    | <span class="code">\Recurr\RecurrenceCollection&lt;array-key,[Occurrence](Occurrence.md '\\boundstate\\eventful\\models\\Occurrence')&gt;</span>                                             |     |

### getNextOccurrence

```php
public getNextOccurrence(bool $includeInProgress = false): ?Occurrence
```

|                      |                                                                                                     |     |
| -------------------- | --------------------------------------------------------------------------------------------------- | --- |
| `$includeInProgress` | <span class="code">bool</span>                                                                      |     |
| **return**           | <span class="code">?[Occurrence](Occurrence.md '\\boundstate\\eventful\\models\\Occurrence')</span> |     |

### getOccurrencesInRange

Returns the occurrences that start in the range (inclusive), or with `$includeInProgress`,
also those that started before the range and end after its start.

```php
public getOccurrencesInRange(DateTimeInterface $start, DateTimeInterface $end, bool $includeInProgress = false): \Recurr\RecurrenceCollection<array-key,Occurrence>
```

|                      |                                                                                                                                                  |     |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | --- |
| `$start`             | <span class="code">DateTimeInterface</span>                                                                                                      |     |
| `$end`               | <span class="code">DateTimeInterface</span>                                                                                                      |     |
| `$includeInProgress` | <span class="code">bool</span>                                                                                                                   |     |
| **return**           | <span class="code">\Recurr\RecurrenceCollection&lt;array-key,[Occurrence](Occurrence.md '\\boundstate\\eventful\\models\\Occurrence')&gt;</span> |     |

### getFirstStartDate

```php
public getFirstStartDate(): ?DateTime
```

|            |                                     |     |
| ---------- | ----------------------------------- | --- |
| **return** | <span class="code">?DateTime</span> |     |

### getLastEndDate

```php
public getLastEndDate(): ?DateTime
```

|            |                                     |     |
| ---------- | ----------------------------------- | --- |
| **return** | <span class="code">?DateTime</span> |     |

### isPast

```php
public isPast(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |
