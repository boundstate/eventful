<?php

namespace boundstate\eventful\transformers;

use craft\helpers\ArrayHelper;
use DateTime;
use DateTimeInterface;
use Recurr\Recurrence;
use Recurr\RecurrenceCollection;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer as BaseArrayTransformer;
use Recurr\Transformer\ConstraintInterface;

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

        foreach ($inclusions as $inclusion) {
            $start = DateTime::createFromInterface($inclusion->date)->modify(
                $this->start->format('H:i'),
            );

            if (! $this->constraint instanceof ConstraintInterface || $this->constraint->test($start)) {
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
