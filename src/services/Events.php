<?php

namespace boundstate\eventful\services;

use boundstate\eventful\base\EventType;
use boundstate\eventful\db\Table;
use boundstate\eventful\enums\Color;
use boundstate\eventful\Eventful;
use boundstate\eventful\events\DefineOrganizerQueryEvent;
use boundstate\eventful\events\RegisterEventSourcesEvent;
use boundstate\eventful\events\RegisterEventTypesEvent;
use boundstate\eventful\fields\EventDate as EventDateField;
use boundstate\eventful\models\EntryEventType;
use boundstate\eventful\models\EventSource;
use boundstate\eventful\models\ProductEventType;
use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutProviderInterface;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\FieldLayout;
use InvalidArgumentException;
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
     * @see DefineOrganizerQueryEvent
     */
    const EVENT_DEFINE_ORGANIZER_QUERY = 'defineOrganizerQuery';

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

    /**
     * @throws InvalidArgumentException if there's no source with the key
     */
    public function findSourceByKey(string $key): EventSource
    {
        return $this->getSources()[$key] ?? throw new InvalidArgumentException("Unknown event source \"$key\".");
    }

    /**
     * Returns a new query for the events of each source (or the given sources), keyed by source,
     * with the source's custom query params, the date criteria and the extra query params applied.
     *
     * @param  string[]|null  $sourceKeys
     * @param  User|null  $user  only query events the user can access
     * @return array<string, ElementQuery>
     *
     * @phpstan-ignore missingType.generics
     */
    public function getEventQueries(
        mixed $date = null,
        ?array $extraQueryParams = null,
        ?array $sourceKeys = null,
        ?User $user = null,
    ): array {
        $queriesBySource = [];

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

                $queriesBySource[$key] = $query;
            }
        }

        return $queriesBySource;
    }

    /**
     * Returns the events of each source (or the given sources), keyed by source.
     *
     * @param  string[]|null  $sourceKeys
     * @param  User|null  $user  only return events the user can access
     * @return array<string, ElementInterface[]>
     *
     * @see getEventQueries()
     */
    public function getEvents(
        mixed $date = null,
        ?array $extraQueryParams = null,
        ?array $sourceKeys = null,
        ?User $user = null,
    ): array {
        return array_map(
            fn (ElementQuery $query): array => $query->all(),
            $this->getEventQueries($date, $extraQueryParams, $sourceKeys, $user),
        );
    }

    /**
     * Returns the users listed in the organizer filter on the control panel calendar.
     *
     * @return User[]
     */
    public function getOrganizers(): array
    {
        $query = User::find()
            ->group(Eventful::getInstance()->settings->organizerFilterGroup)
            ->orderBy('fullName');

        $this->trigger(self::EVENT_DEFINE_ORGANIZER_QUERY, new DefineOrganizerQueryEvent([
            'query' => $query,
        ]));

        return $query->all();
    }

    /**
     * Increments the iCalendar sequence of an event.
     *
     * Events don't get a metadata row until they're first changed, and a missing row means sequence 0,
     * so the row is created with sequence 1.
     */
    public function updateICalendarSequence(int $elementId): void
    {
        Db::upsert(
            Table::METADATA,
            [
                'id' => $elementId,
                'iCalendarSequence' => 1,
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
