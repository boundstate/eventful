---
icon: lucide/database-search
---

# Event queries

Assuming you have a `meetings` section and that the entry type has an event date field with the handle `eventDate`,
you can query all ongoing and upcoming meetings in your template as follows:

```twig
{% set meetings = craft.entries()
    .section('meetings')
    .eventDate({ lastEnd: ['or', '>= now', ':empty:'] })
    .orderBy('eventDate ASC')
    .all() %}
```

Criteria can be combined, e.g. `{ repeats: true, firstStart: '> now' }`.

| Criteria                                   | Result                                            |
| ------------------------------------------ | ------------------------------------------------- |
| `{ lastEnd: ['or', '>= now', ':empty:'] }` | Ongoing and future (last occurrence hasn't ended) |
| `{ lastEnd: '>= now' }`                    | Ongoing and future, excluding never-ending events |
| `{ lastEnd: '< now' }`                     | Past (all occurrences have ended)                 |
| `{ firstStart: '> now' }`                  | Only future (no occurrence has started)           |
| `{ repeats: true }`                        | Repeating events                                  |
| `{ repeats: false }`                       | One-time events                                   |
| `{ allDay: true }`                         | All day events                                    |
| `{ allDay: false }`                        | Timed events                                      |

## Events in a range

Use the `inRange` query filter to find events that start in a range, or started before the range but are still in progress:

```twig
{% set start = date('monday this week') %}
{% set end = start|date_modify('+1 week') %}

{% set meetings = craft.entries()
    .section('meetings')
    .eventDate({ inRange: [start, end] })
    .orderBy('eventDate ASC')
    .all() %}
```

The `inRange` query filter compares against each event's first start and last end,
so it may return repeating events without any occurrences actually in the range.

To list the occurrences that are actually in the range, use `getOccurrencesInRange()`.

```twig
{% for meeting in meetings %}
    {% for occurrence in meeting.eventDate.getOccurrencesInRange(start, end, includeInProgress: true) %}
        <p>{{ meeting.title }}: {{ occurrence|eventDate }}</p>
    {% endfor %}
{% endfor %}
```

!!! tip ""

    Both ends of the range are inclusive, so an occurrence that starts exactly at the end of the range is included.
    An occurrence that ends exactly at the start of the range isn't in progress, so it's excluded.

## Events from all sources

To query the events of every source shown on the control panel calendar (e.g. for a calendar on the front end),
use `getEventQueries()`. It returns a new query for each source, keyed by source, with the source's custom query params
and the given date criteria applied, which you can modify before getting the events:

```php
use boundstate\eventful\Eventful;
use boundstate\eventful\models\ProductEventType;

$events = Eventful::getInstance()->events;
$elementsBySource = [];

foreach ($events->getEventQueries(date: ['inRange' => [$start, $end]]) as $key => $query) {
    $source = $events->findSourceByKey($key);

    if ($source->type instanceof ProductEventType) {
        // products don't have an author, so use a field instead
        $query->andRelatedTo(['targetElement' => $currentUser, 'field' => 'hosts']);
    } else {
        $query->andRelatedTo($currentUser);
    }

    $elementsBySource[$key] = $query->all();
}
```

Use `andRelatedTo()` and `andWhere()` rather than `relatedTo()` and `where()`,
which would replace any criteria from the source's custom query params.

You can also limit the queries to some sources with `sourceKeys`, or to the events a user can access with `user`.
The `user` criteria are applied to the queries you get, so changing the author criteria could give access to more events.

If you don't need to modify the queries, `getEvents()` takes the same arguments and returns the events of each source.
