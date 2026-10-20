<?php

namespace boundstate\eventful\events;

use DateTimeImmutable;
use yii\base\Event;

/**
 * Event for customizing the timezone label added to formatted times (e.g. "EST"),
 * and shown in the timezone options of Event Date fields.
 */
class DefineTimezoneLabelEvent extends Event
{
    /**
     * @var DateTimeImmutable The date being formatted, converted to `$timezone`
     */
    public DateTimeImmutable $date;

    /**
     * @var string The timezone the date is displayed in (e.g. `America/Vancouver`)
     */
    public string $timezone;

    /**
     * @var string The locale the date is formatted for (e.g. `en-US`)
     */
    public string $locale;

    /**
     * @var string The short label to display (e.g. "EST"), which defaults to the locale's short name
     */
    public string $label;
}
