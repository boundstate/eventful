---
title: Settings
---

# Class Settings

<small class="block">Extends <span class="code">[Model](https://docs.craftcms.com/api/v5/craft-base-model.html '\\craft\\base\\Model')</span></small>

## Properties

### eventTitleTemplate

```php
public string $eventTitleTemplate
```

### eventDescriptionTemplate

```php
public string $eventDescriptionTemplate
```

### eventLocationTemplate

```php
public string $eventLocationTemplate
```

### displayTimezone

```php
public bool $displayTimezone
```

### replaceCraftTimezoneOptions

Whether Craft's timezone inputs (e.g. in the user's preferences) use the Event Date field's options

```php
public bool $replaceCraftTimezoneOptions
```

### calendarSecret

```php
public ?string $calendarSecret
```

### extraEventSources

```php
public array $extraEventSources
```

### organizerFilterEnabled

```php
public bool $organizerFilterEnabled
```

### organizerFilterLabel

```php
public string $organizerFilterLabel
```

### organizerFilterGroup

```php
public ?string $organizerFilterGroup
```

### organizerQueryParam

```php
public string $organizerQueryParam
```

## Methods

### parseCalendarSecret

```php
public parseCalendarSecret(): ?string
```

|            |                                   |     |
| ---------- | --------------------------------- | --- |
| **return** | <span class="code">?string</span> |     |

### defineRules

```php
public defineRules(): array
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| **return** | <span class="code">array</span> |     |
