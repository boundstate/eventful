---
icon: lucide/sprout
---

# Twig filters

This plugin provides the following filters for use in Twig templates:

## eventDateRange

Formats the value of an [`EventDate`](reference/fields/EventDate.md) field, displaying the first and last dates along with the time range.
See [`EventDateHelper::formatDateRange()`](reference/helpers/EventDateHelper.md#formatdaterange){ data-preview }

```twig
{{ entry.date|eventDateRange }}
{# Output: Jul 30 - Aug 28, 2026 · 10AM – 11AM #}

{{ entry.date|eventDateRange('longDate') }}
{# Output: July 30 - August 28, 2026 #}
```

All day events are displayed without times (the time formats display `All day`).

!!! tip ""
You can display the repeat rule description using [`EventDate::getRepeatDescription()`](reference/models/EventDate.md#getrepeatdescription){ data-preview }.

## eventDate

Formats a specific occurrence of an `EventDate`.
See [`EventDateHelper::formatDate()`](reference/helpers/EventDateHelper.md#formatdate){ data-preview }

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

Occurrences of all day events are displayed without times.

## eventTitle

Renders an event title using the configured template.
See [`EventRenderer`](reference/helpers/EventRenderer.md#render){ data-preview }

## eventDescription

Renders an event description using the configured template.
See [`EventRenderer`](reference/helpers/EventRenderer.md#render){ data-preview }

## eventLocation

Renders an event location using the configured template.
See [`EventRenderer`](reference/helpers/EventRenderer.md#render){ data-preview }
