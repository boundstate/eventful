<?php

namespace boundstate\eventful\web\twig\variables;

use boundstate\eventful\helpers\DateHelper;
use DateTime;

/**
 * Replaces `craft.cp`, so Craft's timezone inputs (e.g. in the user's preferences)
 * have the same options as the Event Date field.
 */
class Cp extends \craft\web\twig\variables\Cp
{
    public function getTimeZoneOptions(?DateTime $offsetDate = null): array
    {
        return DateHelper::timezoneOptions($offsetDate);
    }
}
