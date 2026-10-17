---
icon: lucide/sprout
---

# Twig filters

This plugin provides the following filters for use in Twig templates:

## eventDate

Formats the date (and times, unless all day) of an [`EventDate`](reference/fields/EventDate.md) field or one of its occurrences.
See [`EventDateHelper::formatDate()`](reference/helpers/EventDateHelper.md#formatdate){ data-preview }

```twig
{{ entry.date|eventDate }}
{# Output: Jul 30, 2026 ⋅ 10AM – 11AM #}

{{ entry.date|eventDate('longDate') }}
{# Output: July 30, 2026 #}

{{ entry.date|eventDate('mediumTime') }}
{# Output: 10AM – 11AM #}
```

The format can be `medium` or `long`, optionally followed by `Date` or `Time` to display only the dates or the times.

All day events are displayed without times, as a range of dates if they last several days (e.g. `Jul 30 - Aug 1, 2026`).
The time formats display `All day`.

### Repeating events

When applied to a repeating `EventDate` field, the filter displays its very first start and very last end, along with the times of each occurrence (e.g. `Jul 30 - Aug 28, 2026 ⋅ 10AM – 11AM`).
If it repeats forever, only the first date is displayed.

To display each occurrence, apply the filter to the occurrences instead:

```twig
<ul>
    {% for occurrence in entry.date.occurrences %}
        <li>{{ occurrence|eventDate }}</li>
    {% endfor %}
</ul>
{# Output:
  <ul>
    <li>Jul 30, 2026 ⋅ 10AM – 11AM</li>
    <li>Jul 31, 2026 ⋅ 10AM – 11AM</li>
    <li>Aug 1, 2026 ⋅ 10AM – 11AM</li>
  </ul> #}
```

!!! tip ""

    You can display the repeat rule description using [`EventDate::getRepeatDescription()`](reference/models/EventDate.md#getrepeatdescription){ data-preview }.

## eventTitle

Renders an event title using the configured template.
See [`EventRenderer`](reference/helpers/EventRenderer.md#render){ data-preview }

## eventDescription

Renders an event description using the configured template.
See [`EventRenderer`](reference/helpers/EventRenderer.md#render){ data-preview }

## eventLocation

Renders an event location using the configured template.
See [`EventRenderer`](reference/helpers/EventRenderer.md#render){ data-preview }
