<?php

namespace boundstate\eventful\models;

use DateTimeInterface;
use Recurr\Recurrence;

/**
 * An occurrence of an `EventDate`, which knows whether the event is all day.
 */
class Occurrence extends Recurrence
{
    public function __construct(
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        int $index = 0,
        public readonly bool $allDay = false,
    ) {
        parent::__construct($start, $end, $index);
    }
}
