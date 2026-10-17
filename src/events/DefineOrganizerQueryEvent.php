<?php

namespace boundstate\eventful\events;

use craft\elements\db\UserQuery;
use craft\elements\User;
use yii\base\Event;

/**
 * Event for customizing the query for users listed in the organizer filter on the control panel calendar.
 */
class DefineOrganizerQueryEvent extends Event
{
    /**
     * @var UserQuery<array-key, User>
     */
    public UserQuery $query;
}
