<?php

namespace boundstate\eventful\transformers;

use craft\helpers\ArrayHelper;
use DateTime;
use DateTimeInterface;
use Recurr\Recurrence;
use Recurr\RecurrenceCollection;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer as BaseArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use Recurr\Transformer\ConstraintInterface;
use yii\base\InvalidArgumentException;

/**
 * Customizes the transformer
 * so inclusions have the same times specified by the rule start & end,
 * and so that inclusions are ordered in the correct position
 * (e.g. in case an inclusion is before the actual start date)
 *
 * Also applies constraints to inclusions
 */
class ArrayTransformer extends BaseArrayTransformer
{
    private DateTimeInterface $start;

    private DateTimeInterface $end;

    private ?ConstraintInterface $constraint = null;

    /**
     * The most recurrences returned by default (Recurr's default virtual limit), which keeps rules
     * that repeat forever, or for a long time, from generating too many
     */
    public const DEFAULT_LIMIT = 732;

    /**
     * Returns the recurrences of a rule, choosing the virtual limit and whether constraint
     * failures count for the rule, so callers don't have to.
     *
     * @param  ?int  $limit  the most recurrences to return, or null for all of them,
     *                       which is only allowed for rules that end (with a count or until date)
     * @return RecurrenceCollection<array-key, Recurrence>
     *
     * @throws InvalidArgumentException if the limit is null and the rule repeats forever
     */
    public static function getRecurrences(
        Rule $rule,
        ?ConstraintInterface $constraint = null,
        ?int $limit = self::DEFAULT_LIMIT,
    ): RecurrenceCollection {
        if ($limit === null && $rule->getCount() === null && ! $rule->getUntil() instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Rules that repeat forever need a limit.');
        }

        $config = new ArrayTransformerConfig;
        $config->setVirtualLimit(self::virtualLimit($rule, $constraint, $limit));

        $recurrences = (new self($config))->transform(
            $rule,
            // constraints keep state between runs (e.g. whether they stop the transformer)
            $constraint instanceof ConstraintInterface ? clone $constraint : null,
            // occurrences that fail the constraint must still count toward the rule's count,
            // but otherwise shouldn't count toward the virtual limit, so occurrences long
            // after the start can be found
            countConstraintFailures: $rule->getCount() !== null,
        );

        if ($limit !== null && count($recurrences) > $limit) {
            // inclusions are added after the virtual limit is applied, and the count can exceed it
            return new RecurrenceCollection($recurrences->slice(0, $limit));
        }

        return $recurrences;
    }

    /**
     * Returns a virtual limit that's enough to get the given limit of a rule's recurrences.
     */
    private static function virtualLimit(Rule $rule, ?ConstraintInterface $constraint, ?int $limit): int
    {
        // exclusions are removed after the virtual limit is applied, and each removes at most one
        // occurrence (they're at most daily), so that many more is always enough
        $limitWithExclusions = $limit === null ? null : $limit + count($rule->getExDates());

        $count = $rule->getCount();
        if ($count !== null) {
            // the count always stops it, and occurrences that fail the constraint count toward
            // the virtual limit too, so only a limit without a constraint can stop it sooner
            return $limitWithExclusions !== null && ! $constraint instanceof ConstraintInterface
                ? min($count, $limitWithExclusions)
                : $count;
        }

        // without a limit, the rule has an until date, which stops it
        return $limitWithExclusions ?? PHP_INT_MAX;
    }

    public function transform(
        Rule $rule,
        ?ConstraintInterface $constraint = null,
        $countConstraintFailures = true,
    ): array|RecurrenceCollection {
        // store properties so they can be used when handling inclusions
        $this->constraint = $constraint;
        $this->start = $rule->getStartDate();
        $this->end = $rule->getEndDate() ?? $this->start;

        return parent::transform($rule, $constraint, $countConstraintFailures);
    }

    protected function handleInclusions(
        array $inclusions,
        array $recurrences,
    ): array {
        $addedInclusion = false;

        // an inclusion can be on a date the rule already repeats on (e.g. if the rule changed after it was added),
        // or be repeated, which shouldn't duplicate the occurrence
        $starts = array_flip(array_map(
            fn (Recurrence $recurrence): int => $recurrence->getStart()->getTimestamp(),
            $recurrences,
        ));

        foreach ($inclusions as $inclusion) {
            $start = DateTime::createFromInterface($inclusion->date)->modify(
                $this->start->format('H:i'),
            );

            if (isset($starts[$start->getTimestamp()])) {
                continue;
            }

            if (! $this->constraint instanceof ConstraintInterface || $this->constraint->test($start)) {
                $starts[$start->getTimestamp()] = true;
                $recurrences[] = new Recurrence(
                    $start,
                    // keep the rule's duration, in case the event ends on a later day
                    DateTime::createFromInterface($start)->add(
                        $this->start->diff($this->end),
                    ),
                );
                $addedInclusion = true;
            }
        }

        if ($addedInclusion) {
            ArrayHelper::multisort(
                $recurrences,
                fn (Recurrence $re) => $re->getStart(),
            );
        }

        return array_values($recurrences);
    }
}
