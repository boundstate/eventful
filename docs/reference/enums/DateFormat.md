---
title: DateFormat
---

# Enum DateFormat

Format for event dates: `MEDIUM` or `LONG`, optionally limited to only the dates or the times.

## Cases

- `MEDIUM`
- `LONG`
- `MEDIUM_DATE`
- `LONG_DATE`
- `MEDIUM_TIME`
- `LONG_TIME`

## Methods

### parse

Returns the format for a case or its value (e.g. `'mediumDate'`).

```php
public static parse(self|string $format): self
```

|            |                                                    |                                   |
| ---------- | -------------------------------------------------- | --------------------------------- |
| `$format`  | <span class="code">self\|string</span>             |                                   |
| **return** | <span class="code">self</span>                     |                                   |
| **throws** | <span class="code">InvalidArgumentException</span> | if the value isn't a valid format |

### length

```php
public length(): FormatLength
```

|            |                                                                                                         |     |
| ---------- | ------------------------------------------------------------------------------------------------------- | --- |
| **return** | <span class="code">[FormatLength](FormatLength.md '\\boundstate\\eventful\\enums\\FormatLength')</span> |     |

### hasDates

```php
public hasDates(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |

### hasTimes

```php
public hasTimes(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |
