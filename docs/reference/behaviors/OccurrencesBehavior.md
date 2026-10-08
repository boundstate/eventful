---
title: OccurrencesBehavior
---

# Class OccurrencesBehavior

Adds an `occurrences()` method to element queries.

<small class="block">Extends <span class="code">[Behavior](https://www.yiiframework.com/doc/api/2.0/yii-base-behavior '\\yii\\base\\Behavior')</span></small>

## Methods

### occurrences

Returns a query for the occurrences of the matched events.

```php
public occurrences(array{from?: mixed, to?: mixed, field?: string} $config = []): OccurrenceQuery
```

|            |                                                                                                                     |     |
| ---------- | ------------------------------------------------------------------------------------------------------------------- | --- |
| `$config`  | <span class="code">array{from?: mixed, to?: mixed, field?: string}</span>                                           |     |
| **return** | <span class="code">[OccurrenceQuery](../db/OccurrenceQuery.md '\\boundstate\\eventful\\db\\OccurrenceQuery')</span> |     |
