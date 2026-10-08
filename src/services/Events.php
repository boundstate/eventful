<?php

namespace boundstate\eventful\services;

use boundstate\eventful\base\EventType;
use boundstate\eventful\db\Table;
use boundstate\eventful\enums\Color;
use boundstate\eventful\events\RegisterEventSourcesEvent;
use boundstate\eventful\events\RegisterEventTypesEvent;
use boundstate\eventful\fields\EventDate as EventDateField;
use boundstate\eventful\models\EntryEventType;
use boundstate\eventful\models\EventSource;
use boundstate\eventful\models\ProductEventType;
use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutProviderInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\FieldLayout;
use yii\base\Component;
use yii\db\Expression;

class Events extends Component
{
    /**
     * @see RegisterEventTypesEvent
     */
    const EVENT_REGISTER_TYPES = 'registerTypes';

    /**
     * @see RegisterEventSourcesEvent
     */
    const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @var ?array<class-string<EventType>>
     */
    private ?array $_typeClasses = null;

    /**
     * @var ?array<string, EventType>
     */
    private ?array $_types = null;

    /**
     * @var ?array<string, EventSource>
     */
    private ?array $_sources = null;

    public function findDateField(ElementInterface|FieldLayoutProviderInterface $type): ?EventDateField
    {
        $fieldLayout = $type->getFieldLayout();
        if (! $fieldLayout instanceof FieldLayout) {
            return null;
        }

        return ArrayHelper::firstWhere($fieldLayout->getCustomFields(), fn ($field): bool => $field instanceof EventDateField);
    }

    public function getTypes(): array
    {
        if (! $this->_types) {
            $typeInstances = [];

            foreach ($this->getTypeClasses() as $typeClass) {
                foreach ($typeClass::discoverTypes() as $key => $typeInstance) {
                    $typeInstances[$key] = $typeInstance;
                }
            }

            $this->_types = $typeInstances;
        }

        return $this->_types;
    }

    /**
     * @return array<string, EventSource>
     */
    public function getSources(): array
    {
        if (! $this->_sources) {
            $sources = [];

            $index = 0;
            foreach ($this->getTypes() as $key => $type) {
                $sources[$key] = EventSource::fromType($type, color: Color::at($index));
                $index++;
            }

            $event = new RegisterEventSourcesEvent(['sources' => $sources]);
            $this->trigger(self::EVENT_REGISTER_SOURCES, $event);

            $this->_sources = $event->sources;
        }

        return $this->_sources;
    }

    public function findSourceByKey(string $key): EventSource
    {
        return $this->getSources()[$key];
    }

    /**
     * @param  string[]|null  $sourceKeys
     * @param  User|null  $user  only return events the user can access
     * @return array<string, ElementInterface[]>
     */
    public function getEvents(
        mixed $date = null,
        ?array $extraQueryParams = null,
        ?array $sourceKeys = null,
        ?User $user = null,
    ): array {
        $elementsBySource = [];

        foreach ($this->getSources() as $key => $source) {
            if (
                (! $sourceKeys || in_array($key, $sourceKeys)) &&
                (! $user instanceof User || $source->type->canView($user))
            ) {

                $query = $source->type->find();

                $queryParams = $source->customQueryParams;

                if ($date !== null) {
                    $queryParams = [...$queryParams, $source->type->dateFieldHandle => $date];
                }

                if ($extraQueryParams) {
                    $queryParams = [...$queryParams, ...$extraQueryParams];
                }

                if ($user instanceof User && ! $source->type->canViewPeers($user)) {
                    $queryParams = [...$queryParams, 'authorId' => $user->id];
                }

                Craft::configure($query, $queryParams);

                $elementsBySource[$key] = $query->all();
            }
        }

        return $elementsBySource;
    }

    public function updateICalendarSequence(int $elementId): void
    {
        Db::upsert(
            Table::METADATA,
            [
                'id' => $elementId,
                'iCalendarSequence' => 0,
            ],
            [
                'iCalendarSequence' => new Expression('[[iCalendarSequence]] + 1'),
            ]
        );
    }

    private function getTypeClasses(): array
    {
        if (! $this->_typeClasses) {
            $this->_typeClasses = [EntryEventType::class];

            if (Craft::$app->plugins->isPluginEnabled('commerce')) {
                $this->_typeClasses[] = ProductEventType::class;
            }

            $event = new RegisterEventTypesEvent(['types' => $this->_typeClasses]);
            $this->trigger(self::EVENT_REGISTER_TYPES, $event);
            $this->_typeClasses = $event->types;
        }

        return $this->_typeClasses;
    }
}
