---
title: Occurrence
---

# Class Occurrence

A single occurrence of an event, returned by an `OccurrenceQuery`.

Since it is a `Recurrence`, it can be formatted with the `eventDate` Twig filter.

<small class="block">Extends <span class="code">[Recurrence](https://github.com/simshaun/recurr/blob/v5.0.3/src/Recurr/Recurrence.php '\\Recurr\\Recurrence')</span></small>

## Properties

### element

The event element

```php
public ElementInterface $element
```

### eventDate

The element's event date

```php
public EventDate $eventDate
```

## Methods

### __construct

```php
public __construct(ElementInterface $element, EventDate $eventDate, DateTimeInterface $start, ?DateTimeInterface $end = null): mixed
```

|              |                                                                                                                                                   |     |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$element`   | <span class="code">[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| `$eventDate` | <span class="code">[EventDate](EventDate.md '\\boundstate\\eventful\\models\\EventDate')</span>                                                   |     |
| `$start`     | <span class="code">DateTimeInterface</span>                                                                                                       |     |
| `$end`       | <span class="code">?DateTimeInterface</span>                                                                                                      |     |
| **return**   | <span class="code">mixed</span>                                                                                                                   |     |
