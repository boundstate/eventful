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

| Criteria                                   | Result                                            |
| ------------------------------------------ | ------------------------------------------------- |
| `{ lastEnd: ['or', '>= now', ':empty:'] }` | Ongoing and future (last occurrence hasn't ended) |
| `{ lastEnd: '>= now' }`                    | Ongoing and future, excluding never-ending events |
| `{ lastEnd: '< now' }`                     | Past (all occurrences have ended)                 |
| `{ firstStart: '> now' }`                  | Only future (no occurrence has started)           |

<!-- prettier-ignore -->
!!! note
    Events that repeat forever have no last end date, so `lastEnd` is empty for them.
    Include `':empty:'` in `lastEnd` criteria to match these events.

## Occurrence queries

To list each occurrence of repeating events separately (e.g. a weekly event appearing once per week),
call `occurrences()` on an element query with the date range to include:

```twig
{% set occurrences = craft.entries()
    .section('meetings')
    .occurrences({ from: 'today', to: '+3 months' }) %}

{% for occurrence in occurrences.limit(10).all() %}
    <a href="{{ occurrence.element.url }}">{{ occurrence.element.title }}</a>
    {{ occurrence|eventDate }}
{% endfor %}
```

Each occurrence has the following properties:

| Property    | Description                          |
| ----------- | ------------------------------------ |
| `element`   | The event element (e.g. the entry)   |
| `eventDate` | The element's event date field value |
| `start`     | When this occurrence starts          |
| `end`       | When this occurrence ends            |

The range is required, and includes occurrences that _start_ within it.
Occurrences are sorted by start date, unless ordered otherwise with `.orderBy({ start: SORT_DESC })` (or `end`).

If the elements have more than one event date field, specify which to use with `field`:

```twig
{% set occurrences = craft.entries()
    .section('meetings')
    .occurrences({ from: 'today', to: '+3 months', field: 'eventDate' }) %}
```

### Pagination

Occurrence queries can be paginated like element queries:

```twig
{% paginate craft.entries()
    .section('meetings')
    .occurrences({ from: 'today', to: '+3 months' })
    .limit(10) as pageInfo, occurrences %}
```

### Calendars

To display occurrences on a calendar, group them by date:

```twig
{% set occurrencesByDay = craft.entries()
    .section('meetings')
    .occurrences({ from: '2026-10-01', to: '2026-10-31 23:59:59' })
    .all()|group(o => o.start|date('Y-m-d')) %}

{% for occurrence in occurrencesByDay['2026-10-08'] ?? [] %}
    {{ occurrence.element.title }} {{ occurrence.start|date('g:ia') }}
{% endfor %}
```

<!-- prettier-ignore -->
!!! note
    Occurrences are expanded from each matching event's recurrence rule when the query runs,
    so they can only be filtered by narrowing the element query (e.g. by section or category), not with `where()`.
    Keep the range as small as needed, since every event with occurrences in the range is loaded.
