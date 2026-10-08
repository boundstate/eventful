<?php

namespace boundstate\eventful\models;

use craft\base\ElementInterface;
use DateTimeInterface;
use Recurr\Recurrence;

/**
 * A single occurrence of an event, returned by an `OccurrenceQuery`.
 *
 * Since it is a `Recurrence`, it can be formatted with the `eventDate` Twig filter.
 */
class Occurrence extends Recurrence
{
    public function __construct(
        /** The event element */
        public ElementInterface $element,
        /** The element's event date */
        public EventDate $eventDate,
        DateTimeInterface $start,
        ?DateTimeInterface $end = null,
    ) {
        parent::__construct($start, $end);
    }
}
