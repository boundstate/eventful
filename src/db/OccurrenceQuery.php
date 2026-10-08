<?php

namespace boundstate\eventful\db;

use boundstate\eventful\fields\EventDate as EventDateField;
use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\Occurrence;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\DateTimeHelper;
use DateTime;
use Recurr\Recurrence;
use Recurr\Transformer\Constraint\BetweenConstraint;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\QueryInterface;
use yii\db\QueryTrait;

/**
 * Queries the individual occurrences of events matched by an element query,
 * starting within a date range.
 *
 * Occurrences are expanded in memory, so the query can be sorted by `start` or `end`
 * and paginated (e.g. with `{% paginate %}`), but not filtered with `where()`.
 *
 * ```twig
 * {% set occurrences = craft.entries()
 *     .section('events')
 *     .occurrences({ from: 'now', to: '+3 months' }) %}
 * ```
 */
class OccurrenceQuery extends BaseObject implements QueryInterface
{
    use QueryTrait;

    /** The query for the event elements */
    public ElementQueryInterface $elementQuery;

    /** Occurrences starting at or after this date are returned (required) */
    public mixed $from = null;

    /** Occurrences starting at or before this date are returned (required) */
    public mixed $to = null;

    /** The handle of the event date field, if the elements have more than one */
    public ?string $field = null;

    /** @var Occurrence[]|null */
    private ?array $_occurrences = null;

    private ?array $_occurrencesKey = null;

    public function from(mixed $value): static
    {
        $this->from = $value;

        return $this;
    }

    public function to(mixed $value): static
    {
        $this->to = $value;

        return $this;
    }

    public function field(?string $value): static
    {
        $this->field = $value;

        return $this;
    }

    /**
     * @return Occurrence[]
     */
    public function all($db = null): array
    {
        if ($this->emulateExecution) {
            return [];
        }

        return array_slice(
            $this->sort($this->getOccurrences()),
            $this->offset ?: 0,
            $this->limit ?: null,
        );
    }

    // QueryInterface documents query results as arrays, but returns occurrences here
    // @phpstan-ignore method.childReturnType
    public function one($db = null): ?Occurrence
    {
        return (clone $this)->limit(1)->all()[0] ?? null;
    }

    /**
     * Returns the number of occurrences, ignoring `limit` and `offset`.
     */
    public function count($q = '*', $db = null): int
    {
        if ($this->emulateExecution) {
            return 0;
        }

        return count($this->getOccurrences());
    }

    public function exists($db = null): bool
    {
        return $this->count() > 0;
    }

    public function where($condition): never
    {
        throw new NotSupportedException('Occurrence queries can’t be filtered with where(). Filter the element query instead.');
    }

    public function andWhere($condition): never
    {
        $this->where($condition);
    }

    public function orWhere($condition): never
    {
        $this->where($condition);
    }

    public function indexBy($column): never
    {
        throw new NotSupportedException('Occurrence queries don’t support indexBy().');
    }

    /**
     * Returns the event elements and their dates.
     *
     * @return array<array{ElementInterface, EventDate}>
     */
    protected function findEvents(DateTime $from, DateTime $to): array
    {
        $handle = $this->field ?? $this->findFieldHandle();

        $query = clone $this->elementQuery;

        // Only fetch elements with occurrences in range. Any existing criteria
        // for the field is kept, unless it can't be combined with the range.
        $criteria = $query->$handle;
        if ($criteria === null || is_array($criteria)) {
            $query->$handle([...$criteria ?? [], 'inRange' => [$from, $to]]);
        }

        $events = [];
        foreach ($query->all() as $element) {
            $date = $element->getFieldValue($handle);
            if ($date instanceof EventDate) {
                $events[] = [$element, $date];
            }
        }

        return $events;
    }

    /**
     * @return Occurrence[] in the order of the element query
     */
    private function getOccurrences(): array
    {
        // compare the unparsed values, so relative dates like `now` don't change between calls
        $key = [$this->from, $this->to, $this->field];
        if ($this->_occurrences !== null && $this->_occurrencesKey == $key) {
            return $this->_occurrences;
        }

        $from = DateTimeHelper::toDateTime($this->from);
        $to = DateTimeHelper::toDateTime($this->to);
        if (! $from || ! $to) {
            throw new InvalidConfigException('Occurrence queries require valid `from` and `to` dates.');
        }

        $constraint = new BetweenConstraint($from, $to, true);

        $occurrences = [];
        foreach ($this->findEvents($from, $to) as [$element, $date]) {
            foreach ($date->getOccurrences($constraint) as $recurrence) {
                /** @var Recurrence $recurrence */
                // events that don't repeat aren't constrained
                if ($constraint->test($recurrence->getStart())) {
                    $occurrences[] = new Occurrence(
                        $element,
                        $date,
                        $recurrence->getStart(),
                        $recurrence->getEnd(),
                    );
                }
            }
        }

        $this->_occurrences = $occurrences;
        $this->_occurrencesKey = $key;

        return $occurrences;
    }

    /**
     * @param  Occurrence[]  $occurrences
     * @return Occurrence[]
     */
    private function sort(array $occurrences): array
    {
        $orderBy = $this->orderBy ?: ['start' => SORT_ASC];

        foreach (array_keys($orderBy) as $column) {
            if (! in_array($column, ['start', 'end'], true)) {
                throw new NotSupportedException("Occurrence queries can only be ordered by `start` or `end`, not `$column`.");
            }
        }

        usort($occurrences, function (Occurrence $a, Occurrence $b) use ($orderBy): int {
            foreach ($orderBy as $column => $direction) {
                $result = $column === 'start'
                    ? $a->getStart() <=> $b->getStart()
                    : $a->getEnd() <=> $b->getEnd();

                if ($result !== 0) {
                    return $direction === SORT_DESC ? -$result : $result;
                }
            }

            return 0;
        });

        return $occurrences;
    }

    private function findFieldHandle(): string
    {
        $handles = [];
        foreach ($this->elementQuery->getFieldLayouts() as $fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                if ($field instanceof EventDateField) {
                    $handles[$field->handle] = true;
                }
            }
        }

        if (count($handles) !== 1) {
            throw new InvalidConfigException(count($handles) === 0
                ? 'The queried elements have no event date field.'
                : 'The queried elements have multiple event date fields. Specify one with `field`.');
        }

        return array_key_first($handles);
    }
}
