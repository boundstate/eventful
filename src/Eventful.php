<?php

namespace boundstate\eventful;

use boundstate\eventful\fields\EventDate as EventDateField;
use boundstate\eventful\models\Settings;
use boundstate\eventful\services\Events;
use boundstate\eventful\services\Exporter;
use boundstate\eventful\web\twig\Extension;
use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DefineMenuItemsEvent;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Eventful plugin
 *
 * @method static Eventful getInstance()
 *
 * @property-read Events $events
 * @property-read Exporter $exporter
 * @property-read Settings $settings
 */
class Eventful extends Plugin
{
    public bool $hasCpSection = true;

    public bool $hasCpSettings = true;

    public string $schemaVersion = '1.1.0';

    public static function config(): array
    {
        return [
            'components' => [
                'events' => Events::class,
                'exporter' => Exporter::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function (): void {
            Craft::$app->view->registerTwigExtension(
                new Extension(Craft::$app->view, Craft::$app->view->twig),
            );
        });
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = 'Events';

        return $item;
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings;
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'eventful/settings',
            ['settings' => $this->getSettings()]
        );
    }

    private function attachEventHandlers(): void
    {
        if (Craft::$app->request->isCpRequest) {
            // dynamic icon date number
            Craft::$app->view->registerJs(
                'document.querySelectorAll("[data-eventful-icon-date]").forEach(el => el.textContent = new Date().getDate())'
            );
        }

        // Custom routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $e): void {
                $e->rules[
                    "$this->handle/<view:\w+>/<year:\d+>/<month:\d+>/<day:\d+>"
                ] = 'eventful/default/index';
                $e->rules[$this->handle] = 'eventful/default/index';
                $e->rules['<secret>.ics'] = 'eventful/default/calendar-ics';
            },
        );

        // Custom fields
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (
            RegisterComponentTypesEvent $e,
        ): void {
            $e->types[] = EventDateField::class;
        });

        // Action menu
        Event::on(
            Element::class,
            Element::EVENT_DEFINE_ACTION_MENU_ITEMS,
            function (DefineMenuItemsEvent $e): void {
                /** @var Element $element */
                $element = $e->sender;

                if (
                    ! ElementHelper::isDraftOrRevision($element) &&
                    $this->events->findDateField($element)
                ) {
                    $e->items[] = [
                        'id' => "ical-{$element->id}",
                        'icon' => 'download',
                        'label' => 'Download iCal file',
                        'url' => UrlHelper::actionUrl('/eventful/default/ics', [
                            'elementId' => $element->id,
                        ]),
                    ];
                }
            },
        );

        // Increment sequence on events so it can be used when sending event updates
        Event::on(Element::class, Element::EVENT_BEFORE_SAVE, function (
            ModelEvent $e,
        ): void {
            /** @var Element $element */
            $element = $e->sender;
            if (
                ! ElementHelper::isDraftOrRevision($element) &&
                ! $element->resaving &&
                $this->events->findDateField($element)
            ) {
                $this->events->updateICalendarSequence($element->id);
            }
        });
    }
}
