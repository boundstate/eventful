---
title: Events
---

# Class Events

<small class="block">Extends <span class="code">[Component](https://www.yiiframework.com/doc/api/2.0/yii-base-component '\\yii\\base\\Component')</span></small>

## Constants

### EVENT_REGISTER_TYPES

See <span class="code">[RegisterEventTypesEvent](../events/RegisterEventTypesEvent.md '\\boundstate\\eventful\\events\\RegisterEventTypesEvent'){ data-preview }</span>

### EVENT_REGISTER_SOURCES

See <span class="code">[RegisterEventSourcesEvent](../events/RegisterEventSourcesEvent.md '\\boundstate\\eventful\\events\\RegisterEventSourcesEvent'){ data-preview }</span>

### EVENT_DEFINE_ORGANIZER_QUERY

See <span class="code">[DefineOrganizerQueryEvent](../events/DefineOrganizerQueryEvent.md '\\boundstate\\eventful\\events\\DefineOrganizerQueryEvent'){ data-preview }</span>

## Methods

### findDateField

```php
public findDateField(ElementInterface|FieldLayoutProviderInterface $type): ?EventDate
```

|            |                                                                                                                                                                                                                                                                                                                |     |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$type`    | <span class="code">[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')\|[FieldLayoutProviderInterface](https://docs.craftcms.com/api/v5/craft-base-fieldlayoutproviderinterface.html '\\craft\\base\\FieldLayoutProviderInterface')</span> |     |
| **return** | <span class="code">?[EventDate](../fields/EventDate.md '\\boundstate\\eventful\\fields\\EventDate')</span>                                                                                                                                                                                                     |     |

### getTypes

```php
public getTypes(): array
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| **return** | <span class="code">array</span> |     |

### getSources

```php
public getSources(): array<string,EventSource>
```

|            |                                                                                                                                     |     |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------- | --- |
| **return** | <span class="code">array&lt;string,[EventSource](../models/EventSource.md '\\boundstate\\eventful\\models\\EventSource')&gt;</span> |     |

### findSourceByKey

```php
public findSourceByKey(string $key): EventSource
```

|            |                                                                                                                 |                                   |
| ---------- | --------------------------------------------------------------------------------------------------------------- | --------------------------------- |
| `$key`     | <span class="code">string</span>                                                                                |                                   |
| **return** | <span class="code">[EventSource](../models/EventSource.md '\\boundstate\\eventful\\models\\EventSource')</span> |                                   |
| **throws** | <span class="code">InvalidArgumentException</span>                                                              | if there's no source with the key |

### getEventQueries

Returns a new query for the events of each source (or the given sources), keyed by source,
with the source's custom query params, the date criteria and the extra query params applied.

```php
public getEventQueries(mixed $date = null, ?array $extraQueryParams = null, string[]|null $sourceKeys = null, User|null $user = null): array<string,ElementQuery>
```

|                     |                                                                                                                                                                          |                                       |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------- |
| `$date`             | <span class="code">mixed</span>                                                                                                                                          |                                       |
| `$extraQueryParams` | <span class="code">?array</span>                                                                                                                                         |                                       |
| `$sourceKeys`       | <span class="code">string[]\|null</span>                                                                                                                                 |                                       |
| `$user`             | <span class="code">[User](https://docs.craftcms.com/api/v5/craft-elements-user.html '\\craft\\elements\\User')\|null</span>                                              | only query events the user can access |
| **return**          | <span class="code">array&lt;string,[ElementQuery](https://docs.craftcms.com/api/v5/craft-elements-db-elementquery.html '\\craft\\elements\\db\\ElementQuery')&gt;</span> |                                       |

### getEvents

Returns the events of each source (or the given sources), keyed by source.

```php
public getEvents(mixed $date = null, ?array $extraQueryParams = null, string[]|null $sourceKeys = null, User|null $user = null): array<string,ElementInterface[]>
```

|                     |                                                                                                                                                                         |                                        |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| `$date`             | <span class="code">mixed</span>                                                                                                                                         |                                        |
| `$extraQueryParams` | <span class="code">?array</span>                                                                                                                                        |                                        |
| `$sourceKeys`       | <span class="code">string[]\|null</span>                                                                                                                                |                                        |
| `$user`             | <span class="code">[User](https://docs.craftcms.com/api/v5/craft-elements-user.html '\\craft\\elements\\User')\|null</span>                                             | only return events the user can access |
| **return**          | <span class="code">array&lt;string,[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')[]&gt;</span> |                                        |

### getOrganizers

Returns the users listed in the organizer filter on the control panel calendar.

```php
public getOrganizers(): User[]
```

|            |                                                                                                                         |     |
| ---------- | ----------------------------------------------------------------------------------------------------------------------- | --- |
| **return** | <span class="code">[User](https://docs.craftcms.com/api/v5/craft-elements-user.html '\\craft\\elements\\User')[]</span> |     |

### updateICalendarSequence

Increments the iCalendar sequence of an event.

Events don't get a metadata row until they're first changed, and a missing row means sequence 0,
so the row is created with sequence 1.

```php
public updateICalendarSequence(int $elementId): void
```

|              |                               |     |
| ------------ | ----------------------------- | --- |
| `$elementId` | <span class="code">int</span> |     |

### getICalendarSequence

Returns the iCalendar sequence of an event.

For an event hard-deleted in the current request, returns its last sequence.

```php
public getICalendarSequence(int $elementId): int
```

|              |                               |     |
| ------------ | ----------------------------- | --- |
| `$elementId` | <span class="code">int</span> |     |
| **return**   | <span class="code">int</span> |     |
