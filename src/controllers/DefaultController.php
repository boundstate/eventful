<?php

namespace boundstate\eventful\controllers;

use boundstate\eventful\base\EventType;
use boundstate\eventful\enums\EventProp;
use boundstate\eventful\enums\IcsMethod;
use boundstate\eventful\Eventful;
use boundstate\eventful\helpers\EventDateHelper;
use boundstate\eventful\helpers\EventRenderer;
use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\EventSource;
use boundstate\eventful\web\assets\CpCalendarAsset;
use Craft;
use craft\base\Element;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;
use DateTime;
use Recurr\Recurrence;
use Recurr\Transformer\Constraint\BetweenConstraint;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use ZipArchive;

class DefaultController extends Controller
{
    public int|bool|array $allowAnonymous = [
        'calendar-ics' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * Renders the control panel calendar & filters
     */
    public function actionIndex(
        ?string $view = null,
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
    ): Response {
        $this->requireCpRequest();

        $settings = Eventful::getInstance()->settings;
        $types = Eventful::getInstance()->events->getTypes();
        $sources = Eventful::getInstance()->events->getSources();

        $currentUser = static::currentUser();

        $initialDate = new DateTime;
        if ($year && $month && $day) {
            $initialDate->setDate($year, $month, $day);
        }

        $viewableSources = array_filter(
            $sources,
            fn (EventSource $source): bool => $source->type->canView($currentUser),
        );

        $creatableTypes = array_filter(
            $types,
            fn (EventType $type): bool => $type->canCreate($currentUser),
        );

        $this->view->registerAssetBundle(CpCalendarAsset::class);
        $this->view->registerJs('new Craft.Eventful.Calendar();');

        $calendarSecret = $settings->parseCalendarSecret();

        return $this->renderTemplate('eventful/index', [
            'settings' => Eventful::getInstance()->settings,
            'initialView' => $view,
            'initialDate' => $initialDate->format('c'),
            'sources' => $viewableSources,
            'creatableTypes' => $creatableTypes,
            'icsUrl' => $calendarSecret ? "$calendarSecret.ics" : null,
            'extraEventSources' => array_map(fn ($source) => [
                ...$source,
                'url' => Craft::$app->view->renderObjectTemplate($source['url'], [
                    'year' => $initialDate->format('Y'),
                ]),
                'color' => "#{$source['color']}",
            ], $settings->extraEventSources),
        ]);
    }

    /**
     * Deletes a specific event occurrence.
     */
    public function actionDelete(): Response
    {
        $this->requireCpRequest();

        $elementId = $this->request->getRequiredParam('elementId');
        $elementType = $this->request->getParam('type');
        $siteId = $this->request->getParam('siteId');
        $exDate = $this->request->getParam('date');

        $elementsService = Craft::$app->getElements();

        if (! $elementType) {
            $elementType = $elementsService->getElementTypeById($elementId);
            if (! $elementType) {
                throw new BadRequestHttpException(
                    "Invalid element ID: $elementId",
                );
            }
        }

        $element = $elementType::find()
            ->id($elementId)
            ->siteId($siteId)
            ->status(null)
            ->one();

        // If this is a provisional draft, delete the canonical
        if ($element && $element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        if (! $element || $element->getIsDraft() || $element->getIsRevision()) {
            throw new BadRequestHttpException(
                'No element was identified by the request.',
            );
        }

        /** @var Element $element */
        $currentUser = static::currentUser();

        if (! $elementsService->canSaveCanonical($element, $currentUser)) {
            throw new ForbiddenHttpException(
                'User not authorized to delete this element.',
            );
        }

        $dateField = Eventful::getInstance()->events->findDateField($element);
        if (! $dateField) {
            throw new BadRequestHttpException('Element has no date field.');
        }

        $date = $element->getFieldValue($dateField->handle);
        if (! $date) {
            throw new BadRequestHttpException('Element has no date set.');
        }

        $date->exDates[] = $exDate;

        $element->setFieldValue($dateField->handle, $date);

        if (! $elementsService->saveElement($element)) {
            return $this->asFailure(
                Craft::t('app', 'Couldn’t delete {type} event.', [
                    'type' => $element::lowerDisplayName(),
                ]),
            );
        } else {
            return $this->asSuccess(
                Craft::t('app', '{type} event deleted.', [
                    'type' => $element::displayName(),
                ]),
            );
        }
    }

    /**
     * Returns JSON events data for the calendar
     */
    public function actionEvents(): Response
    {
        $this->requireCpRequest();

        $settings = Eventful::getInstance()->settings;

        $currentUser = static::currentUser();

        $site = Craft::$app->request->getQueryParam('site');
        $start = Craft::$app->request->getQueryParam('start');
        $end = Craft::$app->request->getQueryParam('end');
        $organizers = Craft::$app->request->getQueryParam('organizers');
        $sourceKeys = Craft::$app->request->getQueryParam('sources');

        $elementsBySource = Eventful::getInstance()->events->getEvents(
            date: ['inRange' => [$start, $end]],
            extraQueryParams: [
                'status' => ['enabled', 'disabled'],
                $settings->organizerQueryParam => $organizers,
                'site' => $site,
            ],
            sourceKeys: $sourceKeys,
            user: $currentUser,
        );

        $constraint = new BetweenConstraint(
            DateTimeHelper::toDateTime($start),
            DateTimeHelper::toDateTime($end),
            true,
        );

        $events = [];

        foreach ($elementsBySource as $sourceKey => $elements) {
            $source = Eventful::getInstance()->events->findSourceByKey(
                $sourceKey,
            );

            /** @var Element $element */
            foreach ($elements as $element) {
                /** @var EventDate $date */
                $date = $element->getFieldValue($source->type->dateFieldHandle);

                foreach ($date->getOccurrences($constraint) as $recurrence) {
                    /** @var Recurrence $recurrence */
                    $events[] = [
                        // event properties used by FullCalendar
                        // https://fullcalendar.io/docs/event-object
                        'start' => $recurrence->getStart()->format(DateTime::ATOM),
                        'end' => $recurrence->getEnd()?->format(DateTime::ATOM),
                        'allDay' => $date->allDay,
                        'editable' => false,
                        'title' => EventRenderer::render($element, EventProp::TITLE),
                        'backgroundColor' => $source->color,
                        'borderColor' => $source->color,
                        'classNames' => $element->enabled
                            ? ['event-enabled']
                            : ['event-disabled'],
                        'url' => $element->getCpEditUrl(),

                        // settings passed to Craft ElementEditorSlideout
                        // https://github.com/craftcms/cms/blob/5.x/src/web/assets/cp/src/js/ElementEditorSlideout.js
                        'element' => [
                            'elementId' => $element->id,
                            'siteId' => $element->siteId,
                            'type' => $element::class,
                            'canEdit' => $element->canSave($currentUser),
                            'canDelete' => $element->canDelete($currentUser),
                        ],

                        // custom properties for HUD
                        'sourceLabel' => $source->pluralDisplayName,
                        'dateDescription' => EventDateHelper::formatDate(
                            $recurrence,
                            allDay: $date->allDay,
                            displayTimezone: $settings->displayTimezone,
                        ),
                        'repeatDescription' => $date->getRepeatDescription(),
                        'description' => EventRenderer::render($element, EventProp::DESCRIPTION),
                        'location' => EventRenderer::render($element, EventProp::LOCATION),
                    ];
                }
            }
        }

        return $this->asJson($events);
    }

    /**
     * Downloads an ICS file for the specified element.
     */
    public function actionIcs(): Response
    {
        $this->requireCpRequest();

        $currentUser = static::currentUser();

        $elementId = $this->request->getRequiredQueryParam('elementId');

        $elementsService = Craft::$app->elements;

        // @phpstan-ignore argument.templateType
        $element = $elementsService->getElementById($elementId);
        if (! $element) {
            throw new NotFoundHttpException;
        }

        if (! $elementsService->canView($element, $currentUser)) {
            throw new ForbiddenHttpException(
                'User not authorized to view this element.',
            );
        }

        $icsContents = Eventful::getInstance()->exporter->toMultipleIcs(
            $element,
            method: IcsMethod::PUBLISH,
        );

        if (count($icsContents) === 1) {
            return $this->response->sendContentAsFile(
                $icsContents[0],
                "event-{$element->id}.ics",
            );
        } else {
            $zipPath =
                Craft::$app->getPath()->getTempPath().
                '/'.
                StringHelper::UUID().
                '.zip';
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new Exception('Cannot create zip at '.$zipPath);
            }

            App::maxPowerCaptain();

            foreach ($icsContents as $i => $icsContent) {
                $zip->addFromString(
                    "event-$element->id-".($i + 1).'.ics',
                    $icsContent,
                );
            }
            $zip->close();

            return $this->response->sendFile(
                $zipPath,
                "event-$element->id.zip",
            );
        }
    }

    /**
     * Subscription point for calendar.
     */
    public function actionCalendarIcs(string $secret): Response
    {
        $settings = Eventful::getInstance()->settings;

        if (! $secret || $secret !== $settings->parseCalendarSecret()) {
            throw new NotFoundHttpException;
        }

        $elementsBySource = Eventful::getInstance()->events->getEvents();
        $elements = array_merge(...array_values($elementsBySource));

        $data = Eventful::getInstance()->exporter->toIcs($elements);

        $this->response->headers
            ->set('content-type', 'text/calendar; charset=utf-8')
            ->set('content-length', (string) strlen($data))
            ->set('expires', '0')
            ->set('cache-control', 'must-revalidate, post-check=0, pre-check=0')
            ->set('pragma', 'public');

        $this->response->format = Response::FORMAT_RAW;
        $this->response->data = $data;

        return $this->response;
    }
}
