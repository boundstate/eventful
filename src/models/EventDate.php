<?php

namespace boundstate\eventful\models;

use boundstate\eventful\helpers\DateHelper;
use boundstate\eventful\transformers\ArrayTransformer;
use boundstate\eventful\translators\Translator;
use craft\base\Model;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\validators\DateTimeValidator;
use DateTime;
use DateTimeZone;
use Recurr\DateExclusion;
use Recurr\DateInclusion;
use Recurr\Recurrence;
use Recurr\RecurrenceCollection;
use Recurr\Rule;
use Recurr\Transformer\Constraint\AfterConstraint;
use Recurr\Transformer\ConstraintInterface;
use Recurr\Transformer\TextTransformer;

/**
 * @property-read ?Rule $rule
 * @property-read ?string $repeatDescription
 */
class EventDate extends Model
{
    const FREQ_DAILY = 'DAILY';

    const FREQ_WEEKLY = 'WEEKLY';

    const FREQ_MONTHLY = 'MONTHLY';

    const FREQ_YEARLY = 'YEARLY';

    const ENDS_COUNT = 'count';

    const ENDS_UNTIL = 'until';

    /**
     * Default event duration in minutes, when no end is given (matches the input JS)
     */
    const DEFAULT_DURATION = 60;

    public bool $allowNeverEnding = false;

    public ?DateTime $start = null;

    public ?DateTime $end = null;

    public ?string $timezone = null;

    public bool $allDay = false;

    public bool $repeat = false;

    public ?int $interval = 1;

    public ?string $freq = null;

    public array $byDay = [];

    public array $byMonthDay = [];

    public ?string $ends = null;

    public ?int $count = 3;

    public ?DateTime $until = null;

    public array $inDates = [];

    public array $exDates = [];

    private static array $dateAttributes = ['start', 'end', 'until'];

    private ?Rule $_rule = null;

    private ?ArrayTransformer $_arrayTransformer = null;

    private ?TextTransformer $_textTransformer = null;

    private ?RecurrenceCollection $_allRecurrences = null;

    private ?string $_repeatDescription = null;

    public function __construct($config = [])
    {
        // use our own logic to typecast & normalize DateTime attributes,
        // which takes into account the timezone for this field
        if (! empty($config['timezone'])) {
            foreach (self::$dateAttributes as $attribute) {
                if (empty($config[$attribute])) {
                    continue;
                }

                $value = $config[$attribute];

                $isTimeOnly = is_array($value) && ! isset($value['date']);
                $isDateOnly = is_array($value) && ! isset($value['time']);

                $config[$attribute] = $this->toDateTime($value, $config['timezone']);

                if (! $config[$attribute]) {
                    continue;
                }

                switch ($attribute) {
                    case 'end':
                        // if end time is provided without date, use start date
                        if ($isTimeOnly && ! empty($config['start'])) {
                            $config[$attribute]->modify(
                                $config['start']->format('Y-m-d'),
                            );
                        }
                        break;
                    case 'until':
                        // if until date is provided without time, set to end of day
                        if ($isDateOnly) {
                            $config[$attribute] = DateHelper::endOfDay(
                                $config[$attribute],
                            );
                        }
                        break;
                }
            }

            // if end is missing, default to the same duration the input JS fills in,
            // so the field can always be saved (e.g. a draft that was autosaved without an end)
            if (
                empty($config['allDay']) &&
                ($config['start'] ?? null) instanceof DateTime &&
                ! (($config['end'] ?? null) instanceof DateTime)
            ) {
                $config['end'] = (clone $config['start'])->modify(
                    sprintf('+%d minutes', self::DEFAULT_DURATION),
                );
            }

            // the end is only entered as a time, so an end time before the start time
            // means the event ends the next day (e.g. 11:30 PM - 12:30 AM)
            if (
                ($config['start'] ?? null) instanceof DateTime &&
                ($config['end'] ?? null) instanceof DateTime &&
                $config['end'] < $config['start']
            ) {
                $config['end']->modify('+1 day');
            }
        }

        if (empty($config['freq']) && ! empty($config['rule'])) {
            // legacy behaviour to handle when rule was stored in database,
            // but other attributes were not

            $config['repeat'] = true;

            $rule = new Rule($config['rule'], $config['start'], $config['end']);
            $config['interval'] = $rule->getInterval();
            $config['freq'] = $rule->getFreqAsText();
            $config['byDay'] = $rule->getByDay();
            $config['byMonthDay'] = $rule->getByMonthDay();

            $config['exDates'] = array_map(
                fn (DateExclusion $m): string => $m->date->format('Y-m-d'),
                $rule->getExDates(),
            );
            $config['inDates'] = array_map(
                fn (DateInclusion $m): string => $m->date->format('Y-m-d'),
                $rule->getRDates(),
            );

            asort($config['exDates']);
            asort($config['inDates']);

            if ($count = $rule->getCount()) {
                $config['ends'] = self::ENDS_COUNT;
                $config['count'] = $count;
            } elseif ($until = $rule->getUntil()) {
                $config['ends'] = self::ENDS_UNTIL;
                $config['until'] = $until;
            }
        }

        unset($config['rule']);

        parent::__construct($config);

        // all day events don't have an end input, so they end at the end of the start day
        // (the start may not have been converted above, since the timezone isn't posted for all day events)
        if ($this->allDay && $this->start instanceof DateTime) {
            $this->end = DateHelper::endOfDay(
                DateTime::createFromInterface($this->start)->setTime(0, 0),
            );
        }
    }

    public function repeatsForCount(): bool
    {
        return $this->ends === self::ENDS_COUNT;
    }

    public function repeatsUntil(): bool
    {
        return $this->ends === self::ENDS_UNTIL;
    }

    public function repeatsWeekly(): bool
    {
        return $this->freq === self::FREQ_WEEKLY;
    }

    public function repeatsMonthly(): bool
    {
        return $this->freq === self::FREQ_MONTHLY;
    }

    public function getRule(?bool $forceRefresh = false): ?Rule
    {
        if (! $this->repeat || ! $this->start instanceof DateTime || ! $this->end instanceof DateTime) {
            return null;
        }

        if (! $this->_rule instanceof Rule || $forceRefresh) {
            $rule = (new Rule(null, $this->start, $this->end))
                ->setFreq($this->freq)
                ->setRDates($this->inDates)
                ->setExDates($this->exDates);

            if ($this->interval) {
                $rule->setInterval($this->interval);
            }
            if ($this->byDay) {
                $rule->setByDay($this->byDay);
            }
            if ($this->byMonthDay) {
                $rule->setByMonthDay($this->byMonthDay);
            }
            if ($this->repeatsForCount() && $this->count) {
                $rule->setCount($this->count);
            } elseif ($this->repeatsUntil() && $this->until instanceof DateTime) {
                $rule->setUntil($this->until);
            }

            $this->_rule = $rule;

            // these are generated from the rule, so are stale once it's rebuilt
            $this->_allRecurrences = null;
            $this->_repeatDescription = null;
        }

        return $this->_rule;
    }

    public function rules(): array
    {
        return [
            ['start', 'required'],
            [['start', 'end', 'until'], DateTimeValidator::class],
            [['end', 'timezone'], 'required', 'when' => fn (EventDate $model): bool => ! $model->allDay],
            ['repeat', 'boolean'],
            [['interval'], 'required', 'when' => fn (EventDate $model): bool => $model->repeat],
            [
                ['interval', 'count'],
                'number',
                'integerOnly' => true,
                'min' => 1,
                'when' => fn (EventDate $model): bool => $model->repeat,
            ],
            [['byDay', 'byMonthDay'], 'safe'],
            [
                'byDay',
                'required',
                'when' => fn (EventDate $model): bool => $model->repeatsWeekly(),
            ],
            ['ends', 'required', 'when' => fn (EventDate $model): bool => $model->repeat && ! $model->allowNeverEnding],
            [
                'count',
                'required',
                'when' => fn (EventDate $model): bool => $model->repeat && $model->repeatsForCount(),
            ],
            [
                'until',
                'required',
                'when' => fn (EventDate $model): bool => $model->repeat && $model->repeatsUntil(),
            ],
            [
                'until',
                'validateUntil',
                'skipOnError' => true,
                'when' => fn (EventDate $model): bool => (bool) $model->freq && $model->repeatsUntil(),
            ],
        ];
    }

    public function validateUntil(): void
    {
        $start = DateTimeHelper::toDateTime($this->start);
        $until = DateTimeHelper::toDateTime($this->until);
        if (
            $start &&
            $until &&
            $until->format('Y-m-d') < $start->format('Y-m-d')
        ) {
            $this->addError('until', 'Until cannot be before start date');
        }
    }

    public function getRepeatDescription(): string
    {
        if ($this->_repeatDescription === null) {
            if ($this->rule) {
                $description = $this->getTextTransformer()->transform($this->rule);
                $this->_repeatDescription = StringHelper::upperCaseFirst((string) $description);
            } else {
                $this->_repeatDescription = '';
            }
        }

        return $this->_repeatDescription;
    }

    public function getOccurrences(
        ?ConstraintInterface $constraint = null,
    ): RecurrenceCollection {
        if (! $this->rule) {
            return new RecurrenceCollection([
                new Occurrence($this->start, $this->end, allDay: $this->allDay),
            ]);
        }

        if (! $constraint instanceof ConstraintInterface) {
            if (! $this->_allRecurrences instanceof RecurrenceCollection) {
                $this->_allRecurrences = $this->toOccurrences(
                    $this->getArrayTransformer()->transform($this->rule),
                );
            }

            return $this->_allRecurrences;
        }

        // Occurrences that fail the constraint must still count toward the
        // rule's count, but otherwise shouldn't count toward the virtual limit,
        // so occurrences of long-running events can be found
        return $this->toOccurrences(
            $this->getArrayTransformer()->transform(
                $this->rule,
                $constraint,
                countConstraintFailures: $this->rule->getCount() !== null,
            ),
        );
    }

    /**
     * Returns the first occurrence that starts after now.
     */
    public function getNextOccurrence(): ?Recurrence
    {
        if (! $this->start instanceof DateTime) {
            return null;
        }

        $now = new DateTime;

        $occurrence = $this->getOccurrences(new AfterConstraint($now))->first();

        // the constraint is ignored when the event doesn't repeat, so check the start too
        return $occurrence && $occurrence->getStart() > $now ? $occurrence : null;
    }

    public function getFirstStartDate(): ?DateTime
    {
        if (! $this->rule) {
            // event does not repeat
            return $this->start ?: null;
        }

        $firstRecurrence = $this->getOccurrences()->first();

        return $firstRecurrence ? $firstRecurrence->getStart() : null;
    }

    public function getLastEndDate(): ?DateTime
    {
        if (! $this->rule) {
            // event does not repeat
            return $this->end ?: null;
        }

        // check the rule rather than the attributes, since `count` and `until` keep their values
        // (e.g. the default count) when the event doesn't end that way
        if ($this->rule->getCount() === null && ! $this->rule->getUntil() instanceof DateTime) {
            // event repeats forever
            return null;
        }

        $lastRecurrence = $this->getOccurrences()->last();

        return $lastRecurrence ? $lastRecurrence->getEnd() : null;
    }

    public function isPast(): bool
    {
        $lastEndDate = $this->getLastEndDate();

        return $lastEndDate instanceof DateTime && $lastEndDate < new DateTime;
    }

    private function toDateTime(
        mixed $value,
        ?string $timezone,
    ): DateTime|false {
        // dates are stored in the database as UTC
        $date = DateTimeHelper::toDateTime($value, false, false);
        if (! $date) {
            return false;
        }
        if ($timezone) {
            $date->setTimezone(new DateTimeZone($timezone));
        }

        return $date;
    }

    /**
     * @param  RecurrenceCollection<array-key, Recurrence>  $recurrences
     */
    private function toOccurrences(RecurrenceCollection $recurrences): RecurrenceCollection
    {
        return new RecurrenceCollection(array_map(
            fn (Recurrence $recurrence): Occurrence => new Occurrence(
                $recurrence->getStart(),
                $recurrence->getEnd(),
                $recurrence->getIndex(),
                allDay: $this->allDay,
            ),
            $recurrences->toArray(),
        ));
    }

    private function getArrayTransformer(): ArrayTransformer
    {
        if (! $this->_arrayTransformer instanceof ArrayTransformer) {
            $this->_arrayTransformer = new ArrayTransformer;
        }

        return $this->_arrayTransformer;
    }

    private function getTextTransformer(): TextTransformer
    {
        if (! $this->_textTransformer instanceof TextTransformer) {
            $this->_textTransformer = new TextTransformer(new Translator(timezone: $this->timezone));
        }

        return $this->_textTransformer;
    }
}
