---
title: Occurrence
---

# Class Occurrence

An occurrence of an `EventDate`, which knows whether the event is all day.

<small class="block">Extends <span class="code">[Recurrence](https://github.com/simshaun/recurr/blob/v5.0.3/src/Recurr/Recurrence.php '\\Recurr\\Recurrence')</span></small>

## Properties

### allDay

```php
public bool $allDay
```

## Methods

### __construct

```php
public __construct(?DateTimeInterface $start = null, ?DateTimeInterface $end = null, int $index = 0, bool $allDay = false): mixed
```

|            |                                              |     |
| ---------- | -------------------------------------------- | --- |
| `$start`   | <span class="code">?DateTimeInterface</span> |     |
| `$end`     | <span class="code">?DateTimeInterface</span> |     |
| `$index`   | <span class="code">int</span>                |     |
| `$allDay`  | <span class="code">bool</span>               |     |
| **return** | <span class="code">mixed</span>              |     |
