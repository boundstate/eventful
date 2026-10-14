---
title: IcsCalendar
---

# Class IcsCalendar

<small class="block">Extends <span class="code">[Model](https://docs.craftcms.com/api/v5/craft-base-model.html '\\craft\\base\\Model')</span></small>

## Methods

### __construct

```php
public __construct(array $config = []): mixed
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| `$config`  | <span class="code">array</span> |     |
| **return** | <span class="code">mixed</span> |     |

### setMethod

```php
public setMethod(?IcsMethod $method): static
```

|            |                                                                                                          |     |
| ---------- | -------------------------------------------------------------------------------------------------------- | --- |
| `$method`  | <span class="code">?[IcsMethod](../enums/IcsMethod.md '\\boundstate\\eventful\\enums\\IcsMethod')</span> |     |
| **return** | <span class="code">static</span>                                                                         |     |

### addEvent

```php
public addEvent(array $config = []): IcsEvent
```

|            |                                                                                              |     |
| ---------- | -------------------------------------------------------------------------------------------- | --- |
| `$config`  | <span class="code">array</span>                                                              |     |
| **return** | <span class="code">[IcsEvent](IcsEvent.md '\\boundstate\\eventful\\models\\IcsEvent')</span> |     |

### serialize

```php
public serialize(): string
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| **return** | <span class="code">string</span> |     |

### addTimezones

Adds a timezone definition for each timed event's timezone to the calendar.

```php
public addTimezones(): void
```
