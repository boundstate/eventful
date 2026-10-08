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
