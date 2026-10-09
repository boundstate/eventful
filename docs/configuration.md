---
icon: lucide/settings
---

# Configuration

This plugin builds its configuration from [Project config](https://craftcms.com/docs/5.x/system/project-config.html), managed via the **Eventful** &rarr; **Settings** screen in Craft’s control panel.

## Event properties

Once you add an Event Date field to something, you'll likely want to define other properties that should also show in the calendar, in ICS files, etc.

You can customize how event titles, descriptions, and locations are rendered by modifying the relevant [object templates].
The templates are passed the element `object`, and an `ics` variable that is `true` when rendering in an ICS file.

## Public calendar

If you want to have a public iCalendar format URL, you can configure a **calendar secret**,
which will be used to generate a URL like `https://example.com/admin/secret.ics`.

!!! tip ""

    Once configured, view the public URL by clicking on the :lucide-ellipsis: button above the calendar.

## Extra event sources

You can configure extra event sources to display readonly on the calendar.
The URLs are [object templates] passed a `year` variable,
so you can configure URLs like `https://canada-holidays.ca/ics/{year}`.

!!! tip ""

    You may want to configure yearly sources multiple times (e.g. `{year}`, `{year+1}` and `{year-1}`),
    so that they can be displayed even when navigating back and forward a year.

## Calendar filters

### Organizer (Craft Team / Pro)

You can optionally enable the ability to filter the calendar by organizer.

By default this will list all users and filter events by their `authorId`,
but you can customize the filter label, user group, and organizer field handle.

!!! warning ""

    Commerce products don't have a built-in author field, so when filtering products, you need to add a field to the product type and configure an appropriate field handle.

[object templates]: https://craftcms.com/docs/5.x/system/object-templates.html
