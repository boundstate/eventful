---
title: IcsEvent
---

# Class IcsEvent

<small class="block">Extends <span class="code">[Model](https://docs.craftcms.com/api/v5/craft-base-model.html '\\craft\\base\\Model')</span></small>

## Properties

### now

```php
public static ?int $now
```

## Methods

### __construct

```php
public __construct(VCalendar $doc, array $config = []): mixed
```

|            |                                                                                                                                                            |     |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$doc`     | <span class="code">[VCalendar](https://github.com/sabre-io/vobject/tree/4.6.1/lib/Component/VCalendar.php '\\Sabre\\VObject\\Component\\VCalendar')</span> |     |
| `$config`  | <span class="code">array</span>                                                                                                                            |     |
| **return** | <span class="code">mixed</span>                                                                                                                            |     |

### setUid

```php
public setUid(string $uid): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$uid`     | <span class="code">string</span> |     |
| **return** | <span class="code">static</span> |     |

### setSequence

```php
public setSequence(int $sequence): static
```

|             |                                  |     |
| ----------- | -------------------------------- | --- |
| `$sequence` | <span class="code">int</span>    |     |
| **return**  | <span class="code">static</span> |     |

### setAllDay

Sets whether this is an all day event, so its dates are serialized without times.

NOTE: call this before [`setRule()`](#setrule).

```php
public setAllDay(bool $allDay): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$allDay`  | <span class="code">bool</span>   |     |
| **return** | <span class="code">static</span> |     |

### isAllDay

```php
public isAllDay(): bool
```

|            |                                |     |
| ---------- | ------------------------------ | --- |
| **return** | <span class="code">bool</span> |     |

### setStart

```php
public setStart(DateTime $start): static
```

|            |                                    |     |
| ---------- | ---------------------------------- | --- |
| `$start`   | <span class="code">DateTime</span> |     |
| **return** | <span class="code">static</span>   |     |

### getStart

```php
public getStart(): ?DateTime
```

|            |                                     |     |
| ---------- | ----------------------------------- | --- |
| **return** | <span class="code">?DateTime</span> |     |

### setEnd

Sets the end of the event (for all day events, the last day of the event).

```php
public setEnd(DateTime $end): static
```

|            |                                    |     |
| ---------- | ---------------------------------- | --- |
| `$end`     | <span class="code">DateTime</span> |     |
| **return** | <span class="code">static</span>   |     |

### getEnd

```php
public getEnd(): ?DateTime
```

|            |                                     |     |
| ---------- | ----------------------------------- | --- |
| **return** | <span class="code">?DateTime</span> |     |

### setStatus

```php
public setStatus(?IcsStatus $status): static
```

|            |                                                                                                          |     |
| ---------- | -------------------------------------------------------------------------------------------------------- | --- |
| `$status`  | <span class="code">?[IcsStatus](../enums/IcsStatus.md '\\boundstate\\eventful\\enums\\IcsStatus')</span> |     |
| **return** | <span class="code">static</span>                                                                         |     |

### setSummary

```php
public setSummary(string $summary): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$summary` | <span class="code">string</span> |     |
| **return** | <span class="code">static</span> |     |

### setDescription

```php
public setDescription(string $description): static
```

|                |                                  |     |
| -------------- | -------------------------------- | --- |
| `$description` | <span class="code">string</span> |     |
| **return**     | <span class="code">static</span> |     |

### setLocation

```php
public setLocation(string $location): static
```

|             |                                  |     |
| ----------- | -------------------------------- | --- |
| `$location` | <span class="code">string</span> |     |
| **return**  | <span class="code">static</span> |     |

### setRule

Sets the repeating rule for this event.

NOTE: the rule end date will be ignored; to set the event end date use [`setEnd()`](#setend).

```php
public setRule(Rule $rule): static
```

|            |                                                                                                                       |     |
| ---------- | --------------------------------------------------------------------------------------------------------------------- | --- |
| `$rule`    | <span class="code">[Rule](https://github.com/simshaun/recurr/blob/v5.0.3/src/Recurr/Rule.php '\\Recurr\\Rule')</span> |     |
| **return** | <span class="code">static</span>                                                                                      |     |

### setOrganizers

```php
public setOrganizers(mixed $users): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$users`   | <span class="code">mixed</span>  |     |
| **return** | <span class="code">static</span> |     |

### addOrganizers

```php
public addOrganizers(mixed $users): static
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| `$users`   | <span class="code">mixed</span>  |     |
| **return** | <span class="code">static</span> |     |

### setAttendees

```php
public setAttendees(mixed $users, ?bool $accepted = null): static
```

|             |                                  |     |
| ----------- | -------------------------------- | --- |
| `$users`    | <span class="code">mixed</span>  |     |
| `$accepted` | <span class="code">?bool</span>  |     |
| **return**  | <span class="code">static</span> |     |

### addAttendees

```php
public addAttendees(mixed $users, ?bool $accepted = null): static
```

|             |                                  |     |
| ----------- | -------------------------------- | --- |
| `$users`    | <span class="code">mixed</span>  |     |
| `$accepted` | <span class="code">?bool</span>  |     |
| **return**  | <span class="code">static</span> |     |
