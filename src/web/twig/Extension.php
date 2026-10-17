<?php

namespace boundstate\eventful\web\twig;

use boundstate\eventful\enums\EventProp;
use boundstate\eventful\Eventful;
use boundstate\eventful\helpers\EventDateHelper;
use boundstate\eventful\helpers\EventRenderer;
use boundstate\eventful\models\EventDate;
use Craft;
use craft\base\ElementInterface;
use craft\web\View;
use Recurr\Recurrence;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Extension extends AbstractExtension
{
    public function __construct(protected ?View $view, protected ?TwigEnvironment $environment) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('eventDate', $this->eventDateFilter(...)),
            new TwigFilter('eventDateRange', $this->eventDateRangeFilter(...)),
            new TwigFilter('eventTitle', fn (ElementInterface $el): string => EventRenderer::render($el, EventProp::TITLE)),
            new TwigFilter('eventDescription', fn (ElementInterface $el): string => EventRenderer::render($el, EventProp::DESCRIPTION)),
            new TwigFilter('eventLocation', fn (ElementInterface $el): string => EventRenderer::render($el, EventProp::LOCATION)),
        ];
    }

    /**
     * Formats the date of an `EventDate` or one of its occurrences.
     */
    public function eventDateFilter(
        EventDate|Recurrence $event,
        string $format = 'medium',
        ?string $timezone = null,
        ?bool $displayTimezone = null,
    ): string {
        $settings = Eventful::getInstance()->settings;

        return EventDateHelper::formatDate(
            $event,
            format: $format,
            timezone: $timezone,
            displayTimezone: $displayTimezone ?? $settings->displayTimezone,
        );
    }

    /**
     * @deprecated in 2.2.0. Use the `eventDate` filter instead.
     */
    public function eventDateRangeFilter(
        EventDate $event,
        string $format = 'medium',
        ?string $timezone = null,
        ?bool $displayTimezone = null,
    ): string {
        Craft::$app->getDeprecator()->log(
            'eventful.eventDateRange',
            'The `eventDateRange` filter has been deprecated. Use `eventDate` instead.',
        );

        return $this->eventDateFilter($event, $format, $timezone, $displayTimezone);
    }
}
