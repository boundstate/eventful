<?php

namespace boundstate\eventful\models;

use craft\base\Model;
use craft\helpers\App;

class Settings extends Model
{
    public string $eventTitleTemplate = '{title}';

    public string $eventDescriptionTemplate = '{description}';

    public string $eventLocationTemplate = '{{ object.location.one()|address({ html: not ics }) }}';

    public bool $displayTimezone = false;

    /**
     * Whether Craft's timezone inputs (e.g. in the user's preferences) use the Event Date field's options
     */
    public bool $replaceCraftTimezoneOptions = true;

    public ?string $calendarSecret = null;

    public array $extraEventSources = [];

    public bool $organizerFilterEnabled = false;

    public string $organizerFilterLabel = 'Organizers';

    public ?string $organizerFilterGroup = null;

    public string $organizerQueryParam = 'authorId';

    public function parseCalendarSecret(): ?string
    {
        return App::parseEnv($this->calendarSecret);
    }

    public function defineRules(): array
    {
        return [
            [['eventTitleTemplate', 'eventDescriptionTemplate', 'eventLocationTemplate'], 'string'],
            [['calendarSecret'], 'string'],
            [['displayTimezone', 'replaceCraftTimezoneOptions', 'organizerFilterEnabled'], 'boolean'],
            [['organizerFilterLabel', 'organizerFilterGroup', 'organizerQueryParam'], 'string'],
        ];
    }
}
