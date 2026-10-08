<?php

namespace boundstate\eventful\behaviors;

use boundstate\eventful\db\OccurrenceQuery;
use Craft;
use craft\elements\db\ElementQueryInterface;
use yii\base\Behavior;

/**
 * Adds an `occurrences()` method to element queries.
 *
 * @property ElementQueryInterface $owner
 */
class OccurrencesBehavior extends Behavior
{
    /**
     * Returns a query for the occurrences of the matched events.
     *
     * @param  array{from?: mixed, to?: mixed, field?: string}  $config
     */
    public function occurrences(array $config = []): OccurrenceQuery
    {
        $query = new OccurrenceQuery(['elementQuery' => $this->owner]);
        Craft::configure($query, $config);

        return $query;
    }
}
