<?php

namespace boundstate\eventful\fields;

use boundstate\eventful\models\EventDate as EventDateModel;
use boundstate\eventful\web\assets\CpEventDateAsset;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\i18n\Locale;
use DateTime;
use yii\db\ExpressionInterface;
use yii\db\Schema;

class EventDate extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    public static function displayName(): string
    {
        return 'Event Date';
    }

    public static function icon(): string
    {
        return 'calendar-days';
    }

    public static function dbType(): array
    {
        return [
            'start' => Schema::TYPE_DATETIME,
            'end' => Schema::TYPE_DATETIME,
            'timezone' => Schema::TYPE_STRING,
            'allDay' => Schema::TYPE_BOOLEAN,

            // recurrence
            'freq' => Schema::TYPE_STRING,
            'inDates' => Schema::TYPE_JSON,
            'exDates' => Schema::TYPE_JSON,
            'interval' => Schema::TYPE_INTEGER,
            'byDay' => Schema::TYPE_JSON,
            'byMonthDay' => Schema::TYPE_JSON,
            'ends' => Schema::TYPE_STRING,
            'count' => Schema::TYPE_INTEGER,
            'until' => Schema::TYPE_DATETIME,

            // denormalized for querying
            'firstStart' => Schema::TYPE_DATETIME,
            'lastEnd' => Schema::TYPE_DATETIME,
        ];
    }

    public static function phpType(): string
    {
        return sprintf('\\%s|null', EventDateModel::class);
    }

    /**
     * Events always have start and end times
     */
    const ALL_DAY_NEVER = 'never';

    /**
     * Events always last all day
     */
    const ALL_DAY_ALWAYS = 'always';

    /**
     * Authors choose whether each event lasts all day
     */
    const ALL_DAY_OPTIONAL = 'optional';

    public bool $allowNeverEnding = false;

    /**
     * Whether events last all day (one of the `ALL_DAY_*` constants)
     */
    public string $allDayMode = self::ALL_DAY_NEVER;

    public function __construct($config = [])
    {
        // fields saved before `allDayMode` existed have an `allDay` boolean setting
        if (array_key_exists('allDay', $config)) {
            $config['allDayMode'] ??= $config['allDay'] ? self::ALL_DAY_ALWAYS : self::ALL_DAY_NEVER;
            unset($config['allDay']);
        }

        parent::__construct($config);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            'allDayMode',
            'in',
            'range' => [self::ALL_DAY_NEVER, self::ALL_DAY_ALWAYS, self::ALL_DAY_OPTIONAL],
        ];

        return $rules;
    }

    public function getElementValidationRules(): array
    {
        return [
            [
                function (ElementInterface $element): void {
                    /** @var EventDateModel $value */
                    $value = $element->getFieldValue($this->handle);
                    if (! $value->validate()) {
                        foreach ($value->getErrorSummary(false) as $errors) {
                            $element->addError($this->handle, $errors);
                        }
                    }
                },
            ],
        ];
    }

    /**
     * @param  ?EventDateModel  $value
     */
    public function serializeValue(
        mixed $value,
        ?ElementInterface $element,
    ): ?array {
        if (! $value) {
            return null;
        }

        $serialized = [
            'start' => Db::prepareDateForDb($value->start),
            'end' => Db::prepareDateForDb($value->end),
            'timezone' => $value->allDay
                ? Craft::$app->timeZone
                : $value->timezone,
            'allDay' => $value->allDay,

            // denormalized for querying
            'firstStart' => Db::prepareDateForDb($value->getFirstStartDate()),
            'lastEnd' => Db::prepareDateForDb($value->getLastEndDate()),
        ];

        if ($value->repeat && $value->freq) {
            $serialized['freq'] = $value->freq;
            $serialized['interval'] = $value->interval;
            $serialized['ends'] = $value->ends;

            if ($value->inDates !== []) {
                $serialized['inDates'] = $value->inDates;
            }

            if ($value->exDates !== []) {
                $serialized['exDates'] = $value->exDates;
            }

            if ($value->repeatsWeekly() || ($value->repeatsMonthly() && $value->byDay !== [])) {
                $serialized['byDay'] = $value->byDay;
            } elseif ($value->repeatsMonthly()) {
                $serialized['byMonthDay'] = $value->byMonthDay;
            }

            if ($value->repeatsForCount()) {
                $serialized['count'] = $value->count;
            } elseif ($value->repeatsUntil()) {
                $serialized['until'] = Db::prepareDateForDb($value->until);
            }
        }

        return $serialized;
    }

    public static function queryCondition(
        array $instances,
        mixed $value,
        array &$params,
    ): array|string|ExpressionInterface|false|null {
        if (is_array($value)) {
            $conditions = [];

            if (isset($value['inRange'])) {
                $start = $value['inRange'][0];
                if ($start instanceof DateTime) {
                    $start = $start->format(DateTime::ATOM);
                }
                $end = $value['inRange'][1];
                if ($end instanceof DateTime) {
                    $end = $end->format(DateTime::ATOM);
                }

                $conditions[] = Db::parseDateParam(
                    static::valueSql($instances, 'firstStart'),
                    "<= $end",
                );
                $conditions[] = Db::parseDateParam(
                    static::valueSql($instances, 'lastEnd'),
                    ['or', ">= $start", ':empty:'],
                );
            }

            if (isset($value['lastEnd'])) {
                $conditions[] = Db::parseDateParam(
                    static::valueSql($instances, 'lastEnd'),
                    $value['lastEnd'],
                );
            }

            if (isset($value['firstStart'])) {
                $conditions[] = Db::parseDateParam(
                    static::valueSql($instances, 'firstStart'),
                    $value['firstStart'],
                );
            }

            if ($conditions !== []) {
                return ['and', ...$conditions];
            }
        }

        return parent::queryCondition($instances, $value, $params);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValueInternal($value, false);
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValueInternal($value, true);
    }

    public function getSortOption(): array
    {
        return [
            'label' => Craft::t('site', $this->name),
            'orderBy' => $this->getValueSql('start'),
            'attribute' => isset($this->layoutElement->handle)
                ? "fieldInstance:{$this->layoutElement->uid}"
                : "field:$this->uid",
        ];
    }

    public function getPreviewHtml(
        mixed $value,
        ElementInterface $element,
    ): string {
        if (! $value) {
            return '';
        }

        $formatter = Craft::$app->getFormatter();

        return $value->allDay
            ? $formatter->asDate($value->start, Locale::LENGTH_MEDIUM)
            : $formatter->asDatetime($value->start, Locale::LENGTH_SHORT);
    }

    public function previewPlaceholderHtml(
        mixed $value,
        ?ElementInterface $element,
    ): string {
        return $this->getPreviewHtml(
            $value ??
                new EventDateModel([
                    'allDay' => $this->allDayMode === self::ALL_DAY_ALWAYS,
                    'start' => new DateTime,
                ]),
            $element ?? new Entry,
        );
    }

    public function getInputHtml(
        mixed $value,
        ?ElementInterface $element = null,
    ): string {
        return $this->inputHtmlInternal($value);
    }

    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->inputHtmlInternal($value, true);
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app
            ->getView()
            ->renderTemplate('eventful/fields/EventDate/settings', [
                'field' => $this,
            ]);
    }

    private function inputHtmlInternal(mixed $value, bool $static = false): string
    {
        $view = Craft::$app->view;
        $id = $this->getInputId();

        $freqOptions = [
            ['label' => 'day', 'value' => EventDateModel::FREQ_DAILY],
            ['label' => 'week', 'value' => EventDateModel::FREQ_WEEKLY],
            ['label' => 'month', 'value' => EventDateModel::FREQ_MONTHLY],
            ['label' => 'year', 'value' => EventDateModel::FREQ_YEARLY],
        ];

        $dayOptions = [
            ['label' => 'S', 'value' => 'SU'],
            ['label' => 'M', 'value' => 'MO'],
            ['label' => 'T', 'value' => 'TU'],
            ['label' => 'W', 'value' => 'WE'],
            ['label' => 'T', 'value' => 'TH'],
            ['label' => 'F', 'value' => 'FR'],
            ['label' => 'S', 'value' => 'SA'],
        ];

        $locale = Craft::$app->getUser()->getIdentity()->getPreferredLocale();

        $view->registerAssetBundle(CpEventDateAsset::class);
        $view->registerJsWithVars(
            fn ($inputId, $inputName, $locale): string => <<<JS
            new Craft.Eventful.Input($inputId, $inputName, $locale);
            JS
            ,
            [
                $view->namespaceInputId($id),
                $view->namespaceInputName($this->handle),
                $locale,
            ],
        );

        return $view->renderTemplate('eventful/fields/EventDate/input', [
            'id' => $id,
            'name' => $this->handle,
            'value' => $value ?? new EventDateModel(['allDay' => $this->allDayMode === self::ALL_DAY_ALWAYS]),
            'field' => $this,
            'freqOptions' => $freqOptions,
            'dayOptions' => $dayOptions,
            'readonly' => $static,
        ]);
    }

    private function normalizeValueInternal(mixed $value, bool $fromRequest): ?EventDateModel
    {
        if ($value instanceof EventDateModel) {
            // already normalized
            return $value;
        }

        if (! $value || ! is_array($value)) {
            return null;
        }

        $value['allDay'] = match ($this->allDayMode) {
            self::ALL_DAY_ALWAYS => true,
            self::ALL_DAY_OPTIONAL => (bool) ($value['allDay'] ?? false),
            default => false,
        };
        $value['allowNeverEnding'] = $this->allowNeverEnding;

        if ($fromRequest && $value['allDay']) {
            // the time inputs are only hidden when "All day" is checked,
            // so ignore them like the date only input of an always all day field
            // (all day events are in the system timezone, rather than the one selected)
            unset($value['end'], $value['timezone']);
            if (is_array($value['start'] ?? null)) {
                unset($value['start']['time']);
                $value['start']['timezone'] = Craft::$app->getTimeZone();
            }
        }

        if (! $fromRequest) {
            // from database

            // infer properties that aren't stored in database
            if (! empty($value['freq'])) {
                $value['repeat'] = true;
            }

            // unset denormalized properties
            unset($value['firstStart']);
            unset($value['lastEnd']);
        }

        return new EventDateModel($value);
    }
}

// @phpstan-ignore-next-line
class_alias(EventDate::class, \events\fields\EventDate::class);
