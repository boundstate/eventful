---
title: FormatLength
---

# Enum FormatLength

Length of formatted dates and times.

## Cases

- `MEDIUM`
- `LONG`

## Methods

### parse

Returns the length for a case or its value (e.g. `'long'`).

```php
public static parse(self|string $length): self
```

|            |                                                    |                                   |
| ---------- | -------------------------------------------------- | --------------------------------- |
| `$length`  | <span class="code">self\|string</span>             |                                   |
| **return** | <span class="code">self</span>                     |                                   |
| **throws** | <span class="code">InvalidArgumentException</span> | if the value isn't a valid length |
