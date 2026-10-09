---
icon: lucide/zap
---

# Triggered events

This plugin triggers several events to allow greater customization for developers:

!!! tip ""

    Craft CMS has a [tutorial](https://craftcms.com/knowledge-base/custom-module-events) on wiring up your first event handler in a module.

## Events

### [Register types](reference/services/Events.md#event_register_types)

Event triggered when registering types of events.

```php
Event::on(
    Events::class,
    Events::EVENT_REGISTER_TYPES,
    function (RegisterEventTypesEvent $e) {
        $e->types[] = Meeting::class;
    }
);
```

### [Register sources](reference/services/Events.md#event_register_sources)

Event triggered when registering event sources.
The built-in types for entries and products will register an event source for each entry type and product type that has an event date field.

```php
Event::on(
    Events::class,
    Events::EVENT_REGISTER_SOURCES,
    function (RegisterEventSourcesEvent $e) {
        foreach ($e->sources as $key => $source) {
            if ($key === 'entry:meetings:meeting') {
                $source->color = Color::PISTACHIO->value;
            }
        }
    }
);
```

## Exporter

### [After build ICS](reference/services/Exporter.md#event_after_build_ics)

Event triggered after building the ICS object but before exporting the file.

```php
Event::on(
    Exporter::class,
    Exporter::EVENT_AFTER_BUILD_ICS,
    function (AfterBuildIcsEvent $event) {
        $event->icsEvent->setUid($event->element->relatedEntry->uid);
    }
);
```
