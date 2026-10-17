<?php

namespace boundstate\eventful\fields\conditions;

use boundstate\eventful\models\EventDate as EventDateModel;
use craft\base\conditions\BaseConditionRule;
use craft\fields\conditions\FieldConditionRuleInterface;
use craft\fields\conditions\FieldConditionRuleTrait;

/**
 * Matches event date fields by whether the event repeats or is all day,
 * e.g. to only show other fields in a field layout for repeating events.
 */
class EventDateFieldConditionRule extends BaseConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    public const OPERATOR_REPEATS = 'repeats';

    public const OPERATOR_NOT_REPEATS = 'notRepeats';

    public const OPERATOR_ALL_DAY = 'allDay';

    public const OPERATOR_NOT_ALL_DAY = 'notAllDay';

    public string $operator = self::OPERATOR_REPEATS;

    protected function operators(): array
    {
        return [
            self::OPERATOR_REPEATS,
            self::OPERATOR_NOT_REPEATS,
            self::OPERATOR_ALL_DAY,
            self::OPERATOR_NOT_ALL_DAY,
        ];
    }

    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_REPEATS => 'repeats',
            self::OPERATOR_NOT_REPEATS => 'doesn’t repeat',
            self::OPERATOR_ALL_DAY => 'is all day',
            self::OPERATOR_NOT_ALL_DAY => 'isn’t all day',
            default => parent::operatorLabel($operator),
        };
    }

    protected function elementQueryParam(): ?array
    {
        return match ($this->operator) {
            self::OPERATOR_REPEATS => ['repeats' => true],
            self::OPERATOR_NOT_REPEATS => ['repeats' => false],
            self::OPERATOR_ALL_DAY => ['allDay' => true],
            self::OPERATOR_NOT_ALL_DAY => ['allDay' => false],
            default => null,
        };
    }

    /**
     * @param  ?EventDateModel  $value
     */
    protected function matchFieldValue($value): bool
    {
        $repeats = $value !== null && $value->repeat && $value->freq;
        $allDay = $value !== null && $value->allDay;

        return match ($this->operator) {
            self::OPERATOR_REPEATS => $repeats,
            self::OPERATOR_NOT_REPEATS => ! $repeats,
            self::OPERATOR_ALL_DAY => $allDay,
            self::OPERATOR_NOT_ALL_DAY => ! $allDay,
            default => true,
        };
    }
}
