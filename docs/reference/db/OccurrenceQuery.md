---
title: OccurrenceQuery
---

# Class OccurrenceQuery

Queries the individual occurrences of events matched by an element query,
starting within a date range.

Occurrences are expanded in memory, so the query can be sorted by `start` or `end`
and paginated (e.g. with `{%% paginate %%}`), but not filtered with `where()`.

```twig
{%% set occurrences = craft.entries()
    .section('events')
    .occurrences({ from: 'now', to: '+3 months' }) %%}
```

<small class="block">Extends <span class="code">[BaseObject](https://www.yiiframework.com/doc/api/2.0/yii-base-baseobject '\\yii\\base\\BaseObject')</span>, Implements <span class="code">[QueryInterface](https://www.yiiframework.com/doc/api/2.0/yii-db-queryinterface '\\yii\\db\\QueryInterface')</span></small>

## Properties

### elementQuery

The query for the event elements

```php
public ElementQueryInterface $elementQuery
```

### from

Occurrences starting at or after this date are returned (required)

```php
public mixed $from
```

### to

Occurrences starting at or before this date are returned (required)

```php
public mixed $to
```

### field

The handle of the event date field, if the elements have more than one

```php
public ?string $field
```

## Methods

### from

```php
public from(mixed $value): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>  |     |
| **return** | <span class="code">static</span> |     |

### to

```php
public to(mixed $value): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>  |     |
| **return** | <span class="code">static</span> |     |

### field

```php
public field(?string $value): static
```

|            |                                   |     |
| ---------- | --------------------------------- | --- |
| `$value`   | <span class="code">?string</span> |     |
| **return** | <span class="code">static</span>  |     |

### all

```php
public all(mixed $db = null): Occurrence[]
```

|            |                                                                                                                |     |
| ---------- | -------------------------------------------------------------------------------------------------------------- | --- |
| `$db`      | <span class="code">mixed</span>                                                                                |     |
| **return** | <span class="code">[Occurrence](../models/Occurrence.md '\\boundstate\\eventful\\models\\Occurrence')[]</span> |     |

### one

```php
public one(mixed $db = null): ?Occurrence
```

|            |                                                                                                               |     |
| ---------- | ------------------------------------------------------------------------------------------------------------- | --- |
| `$db`      | <span class="code">mixed</span>                                                                               |     |
| **return** | <span class="code">?[Occurrence](../models/Occurrence.md '\\boundstate\\eventful\\models\\Occurrence')</span> |     |

### count

Returns the number of occurrences, ignoring `limit` and `offset`.

```php
public count(mixed $q = '*', mixed $db = null): int
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| `$q`       | <span class="code">mixed</span> |     |
| `$db`      | <span class="code">mixed</span> |     |
| **return** | <span class="code">int</span>   |     |

### exists

```php
public exists(mixed $db = null): bool
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| `$db`      | <span class="code">mixed</span> |     |
| **return** | <span class="code">bool</span>  |     |

### where

```php
public where(mixed $condition): never
```

|              |                                 |     |
| ------------ | ------------------------------- | --- |
| `$condition` | <span class="code">mixed</span> |     |
| **return**   | <span class="code">never</span> |     |

### andWhere

```php
public andWhere(mixed $condition): never
```

|              |                                 |     |
| ------------ | ------------------------------- | --- |
| `$condition` | <span class="code">mixed</span> |     |
| **return**   | <span class="code">never</span> |     |

### orWhere

```php
public orWhere(mixed $condition): never
```

|              |                                 |     |
| ------------ | ------------------------------- | --- |
| `$condition` | <span class="code">mixed</span> |     |
| **return**   | <span class="code">never</span> |     |

### indexBy

```php
public indexBy(mixed $column): never
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| `$column`  | <span class="code">mixed</span> |     |
| **return** | <span class="code">never</span> |     |
