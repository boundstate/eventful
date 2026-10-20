<?php

namespace boundstate\eventful\controllers;

use boundstate\eventful\base\EventType;
use boundstate\eventful\enums\EventProp;
use boundstate\eventful\enums\IcsMethod;
use boundstate\eventful\Eventful;
use boundstate\eventful\helpers\DateHelper;
use boundstate\eventful\helpers\EventDateHelper;
use boundstate\eventful\helpers\EventRenderer;
use boundstate\eventful\models\EventDate;
use boundstate\eventful\models\EventSource;
use boundstate\eventful\web\assets\CpCalendarAsset;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\Cp as CpHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\twig\variables\Cp;
use DateTime;
use Recurr\Recurrence;
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

        // the statuses of all the sources' element types, for the status filter
        $statuses = [];
        foreach ($viewableSources as $source) {
            /** @var class-string<ElementInterface> $elementType */
            $elementType = $source->type->find()->elementType;
            if ($elementType::hasStatuses()) {
                $statuses += $elementType::statuses();
            }
        }

        $this->view->registerAssetBundle(CpCalendarAsset::class);
        $this->view->registerJs('new Craft.Eventful.Calendar();');

        $calendarSecret = $settings->parseCalendarSecret();

        return $this->renderTemplate('eventful/index', [
            'settings' => Eventful::getInstance()->settings,
            'initialView' => $view,
            'initialDate' => $initialDate->format('c'),
            'sources' => $viewableSources,
            'creatableTypes' => $creatableTypes,
            'statuses' => $statuses,
            'organizers' => $settings->organizerFilterEnabled
                ? Eventful::getInstance()->events->getOrganizers()
                : [],
            'icsUrl' => $calendarSecret ? "$calendarSecret.ics" : null,
            'extraEventSources' => array_map(fn (array $source): array => [
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
        $this->requirePostRequest();

        $elementId = $this->request->getRequiredBodyParam('elementId');
        $elementType = $this->request->getBodyParam('type');
        $siteId = $this->request->getBodyParam('siteId');
        $exDate = $this->request->getRequiredBodyParam('date');

        $elementsService = Craft::$app->getElements();

        if ($elementType && ! is_subclass_of($elementType, ElementInterface::class)) {
            throw new BadRequestHttpException("Invalid element type: $elementType");
        }

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
        }

        return $this->asSuccess(
            Craft::t('app', '{type} event deleted.', [
                'type' => $element::displayName(),
            ]),
        );
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
        $statusFilter = Craft::$app->request->getQueryParam('status');

        $startDate = DateTimeHelper::toDateTime($start);
        $endDate = DateTimeHelper::toDateTime($end);
        if (! $startDate || ! $endDate) {
            throw new BadRequestHttpException('Valid start and end dates are required.');
        }

        $elementsBySource = Eventful::getInstance()->events->getEvents(
            date: ['inRange' => [$start, $end]],
            extraQueryParams: [
                // sources whose element type doesn't support the status don't return any events
                'status' => $statusFilter ?: ['enabled', 'disabled'],
                $settings->organizerQueryParam => $organizers,
                'site' => $site,
            ],
            sourceKeys: $sourceKeys,
            user: $currentUser,
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
                $status = $element->getStatus();
                $statusLabel = $element->showStatusIndicator()
                    ? CpHelper::componentStatusLabelHtml($element)
                    : null;

                foreach ($date->getOccurrencesInRange($startDate, $endDate, includeInProgress: true) as $recurrence) {
                    /** @var Recurrence $recurrence */
                    $events[] = [
                        // event properties used by FullCalendar
                        // https://fullcalendar.io/docs/event-object
                        // all day events are given as dates, so they aren't shifted to the browser's timezone
                        'start' => $date->allDay
                            ? $recurrence->getStart()->format('Y-m-d')
                            : $recurrence->getStart()->format(DateTime::ATOM),
                        // all day events end at the end of their last day, but FullCalendar expects an exclusive end date
                        'end' => $date->allDay
                            ? DateTime::createFromInterface($recurrence->getEnd())->modify('+1 day')->format('Y-m-d')
                            : $recurrence->getEnd()?->format(DateTime::ATOM),
                        'allDay' => $date->allDay,
                        'editable' => false,
                        'title' => EventRenderer::render($element, EventProp::TITLE),
                        'backgroundColor' => $source->color,
                        'borderColor' => $source->color,
                        // e.g. "live", "pending", "expired" or "disabled" for entries
                        'classNames' => [
                            "event-status-{$status}",
                            ...in_array($status, [Element::STATUS_ENABLED, Entry::STATUS_LIVE], true)
                                ? []
                                : ['event-unpublished'],
                        ],
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

                        'occurrenceDate' => $recurrence->getStart()->format('Y-m-d'),

                        // custom properties for HUD
                        'sourceLabel' => $source->pluralDisplayName,
                        'statusLabel' => $statusLabel,
                        // in the event's timezone (e.g. its local time if it's in person),
                        // so it's displayed if it isn't the calendar's (the user's preferred one),
                        // even for occurrences when they have the same offset, so it's consistent
                        'dateDescription' => EventDateHelper::formatDate(
                            $recurrence,
                            allDay: $date->allDay,
                            displayTimezone: $settings->displayTimezone || (! $date->allDay && ! DateHelper::hasSameOffsets(
                                $recurrence->getStart()->getTimezone()->getName(),
                                Craft::$app->getTimeZone(),
                                $recurrence->getStart(),
                            )),
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
     * Returns the timezone options for the date field, with labels for the given date,
     * since a timezone's offset and abbreviation can change during the year.
     */
    public function actionTimezoneOptions(): Response
    {
        $this->requireCpRequest();

        $date = DateTimeHelper::toDateTime($this->request->getRequiredQueryParam('date'));
        if (! $date) {
            throw new BadRequestHttpException('A valid date is required.');
        }

        $options = (new Cp)->getTimeZoneOptions($date);

        // in the format Selectize expects
        return $this->asJson(array_map(fn (array $option): array => [
            'value' => $option['value'],
            'text' => $option['label'],
            'hint' => $option['data']['hint'] ?? null,
        ], $options));
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

        if ($icsContents === []) {
            throw new BadRequestHttpException('Element has no date set.');
        }

        if (count($icsContents) === 1) {
            return $this->response->sendContentAsFile(
                $icsContents[0],
                "event-{$element->id}.ics",
            );
        }

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

    /**
     * Subscription point for calendar.
     */
    public function actionCalendarIcs(string $secret): Response
    {
        $settings = Eventful::getInstance()->settings;

        $calendarSecret = $settings->parseCalendarSecret();

        // compare in constant time, so the secret can't be guessed from response times
        if (! $secret || ! $calendarSecret || ! hash_equals($calendarSecret, $secret)) {
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
