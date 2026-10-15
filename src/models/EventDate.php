<?php

namespace boundstate\eventful\models;

use boundstate\eventful\helpers\DateHelper;
use boundstate\eventful\transformers\ArrayTransformer;
use boundstate\eventful\translators\Translator;
use craft\base\Model;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\validators\DateTimeValidator;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Recurr\DateExclusion;
use Recurr\DateInclusion;
use Recurr\Recurrence;
use Recurr\RecurrenceCollection;
use Recurr\Rule;
use Recurr\Transformer\Constraint\AfterConstraint;
use Recurr\Transformer\Constraint\BetweenConstraint;
use Recurr\Transformer\ConstraintInterface;
use Recurr\Transformer\TextTransformer;

/**
 * @property-read ?Rule $rule
 * @property-read ?string $repeatDescription
 * @property-read RecurrenceCollection<array-key, Occurrence> $occurrences
 * @property-read ?Occurrence $nextOccurrence
 * @property-read ?DateTime $firstStartDate
 * @property-read ?DateTime $lastEndDate
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

    private ?TextTransformer $_textTransformer = null;

    /**
     * @var ?RecurrenceCollection<array-key, Occurrence>
     */
    private ?RecurrenceCollection $_allOccurrences = null;

    private string|false|null $_repeatDescription = false;

    private DateTime|false|null $_firstStartDate = false;

    private DateTime|false|null $_lastEndDate = false;

    /**
     * @var array<int, ?Occurrence> Cach keyed by whether occurrences in progress are included.
     */
    private array $_nextOccurrences = [];

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

    public function getRule(): ?Rule
    {
        if (! $this->repeat || ! $this->start instanceof DateTime || ! $this->end instanceof DateTime) {
            return null;
        }

        if (! $this->_rule instanceof Rule) {
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
                $this->validateUntil(...),
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

    public function getRepeatDescription(): ?string
    {
        if ($this->_repeatDescription === false) {
            if ($this->rule) {
                $description = $this->getTextTransformer()->transform($this->rule);
                $this->_repeatDescription = StringHelper::upperCaseFirst((string) $description);
            } else {
                $this->_repeatDescription = null;
            }
        }

        return $this->_repeatDescription;
    }

    /**
     * Returns the occurrences, up to 732 of them (`ArrayTransformer::DEFAULT_LIMIT`).
     * Use {@link getOccurrencesInRange()} to get later occurrences.
     *
     * @return RecurrenceCollection<array-key, Occurrence>
     */
    public function getOccurrences(
        ?ConstraintInterface $constraint = null,
    ): RecurrenceCollection {
        if (! $this->rule) {
            return new RecurrenceCollection([
                new Occurrence($this->start, $this->end, allDay: $this->allDay),
            ]);
        }

        if (! $constraint instanceof ConstraintInterface) {
            if (! $this->_allOccurrences instanceof RecurrenceCollection) {
                $this->_allOccurrences = $this->toOccurrences(
                    ArrayTransformer::getRecurrences($this->rule),
                );
            }

            return $this->_allOccurrences;
        }

        return $this->toOccurrences(
            ArrayTransformer::getRecurrences($this->rule, $constraint),
        );
    }

    public function getNextOccurrence(bool $includeInProgress = false): ?Occurrence
    {
        if (! $this->start instanceof DateTime) {
            return null;
        }

        $now = new DateTimeImmutable;

        if (! $this->rule) {
            // event does not repeat
            $date = $includeInProgress ? ($this->end ?? $this->start) : $this->start;

            return $date > $now ? $this->getOccurrences()->first() : null;
        }

        $key = (int) $includeInProgress;
        if (! array_key_exists($key, $this->_nextOccurrences)) {
            $after = $now;
            if ($includeInProgress) {
                // constraints only test the start, but every occurrence has the same duration,
                // so an occurrence ends after now if it starts after now minus the duration
                $after = $now->sub($this->getDuration());
            }

            $this->_nextOccurrences[$key] = $this->getFirstOccurrence(new AfterConstraint($after));
        }

        return $this->_nextOccurrences[$key];
    }

    /**
     * Returns the occurrences that start in the range (inclusive), or with `$includeInProgress`,
     * also those that started before the range and end after its start.
     *
     * @return RecurrenceCollection<array-key, Occurrence>
     */
    public function getOccurrencesInRange(
        DateTimeInterface $start,
        DateTimeInterface $end,
        bool $includeInProgress = false,
    ): RecurrenceCollection {
        if (! $this->start instanceof DateTime) {
            return new RecurrenceCollection;
        }

        $start = DateTimeImmutable::createFromInterface($start);
        $end = DateTimeImmutable::createFromInterface($end);

        if (! $this->rule) {
            // event does not repeat
            $inRange = $this->start <= $end && ($includeInProgress
                ? ($this->end ?? $this->start) > $start || $this->start >= $start
                : $this->start >= $start);

            return $inRange ? $this->getOccurrences() : new RecurrenceCollection;
        }

        if (! $includeInProgress) {
            return $this->getOccurrences(new BetweenConstraint($start, $end, true));
        }

        // constraints only test the start, but every occurrence has the same duration,
        // so an occurrence ends after the range starts if it starts after that minus the duration
        $occurrences = $this->getOccurrences(
            new BetweenConstraint($start->sub($this->getDuration()), $end, true),
        );

        // the inclusive constraint also keeps an occurrence that ends exactly when the range starts
        return new RecurrenceCollection(array_values(array_filter(
            $occurrences->toArray(),
            fn (Occurrence $occurrence): bool => $occurrence->getEnd() > $start || $occurrence->getStart() >= $start,
        )));
    }

    public function getFirstStartDate(): ?DateTime
    {
        if (! $this->rule) {
            // event does not repeat
            return $this->start ?: null;
        }

        if ($this->_firstStartDate === false) {
            $start = $this->getFirstOccurrence()?->getStart();
            $this->_firstStartDate = $start ? DateTime::createFromInterface($start) : null;
        }

        return $this->_firstStartDate;
    }

    /**
     * Returns the first occurrence of a rule that passes the constraint, without generating them all.
     */
    private function getFirstOccurrence(?ConstraintInterface $constraint = null): ?Occurrence
    {
        $recurrence = ArrayTransformer::getRecurrences($this->rule, $constraint, limit: 1)->first();

        return $recurrence ? $this->toOccurrence($recurrence) : null;
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

        if ($this->_lastEndDate === false) {
            $this->_lastEndDate = $this->getLastOccurrence()?->getEnd();
        }

        return $this->_lastEndDate;
    }

    /**
     * Returns the last occurrence of a rule that ends (with a count or until date).
     */
    private function getLastOccurrence(): ?Occurrence
    {
        $lastRecurrence = ArrayTransformer::getRecurrences($this->rule, limit: null)->last();

        return $lastRecurrence ? $this->toOccurrence($lastRecurrence) : null;
    }

    public function isPast(): bool
    {
        $lastEndDate = $this->getLastEndDate();

        return $lastEndDate instanceof DateTime && $lastEndDate < new DateTime;
    }

    /**
     * Returns the duration of each occurrence of a rule.
     */
    private function getDuration(): DateInterval
    {
        $start = $this->rule->getStartDate();

        return $start->diff($this->rule->getEndDate() ?? $start);
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
     * @return RecurrenceCollection<array-key, Occurrence>
     */
    private function toOccurrences(RecurrenceCollection $recurrences): RecurrenceCollection
    {
        return new RecurrenceCollection(array_map(
            $this->toOccurrence(...),
            $recurrences->toArray(),
        ));
    }

    private function toOccurrence(Recurrence $recurrence): Occurrence
    {
        return new Occurrence(
            $recurrence->getStart(),
            $recurrence->getEnd(),
            $recurrence->getIndex(),
            allDay: $this->allDay,
        );
    }

    private function getTextTransformer(): TextTransformer
    {
        if (! $this->_textTransformer instanceof TextTransformer) {
            $this->_textTransformer = new TextTransformer(new Translator(timezone: $this->timezone));
        }

        return $this->_textTransformer;
    }
}
