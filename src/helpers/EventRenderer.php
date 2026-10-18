<?php

namespace boundstate\eventful\helpers;

use boundstate\eventful\enums\EventProp;
use boundstate\eventful\Eventful;
use Craft;
use craft\base\ElementInterface;
use Soundasleep\Html2Text;

/**
 * Helper class for rendering event properties.
 */
abstract class EventRenderer
{
    /**
     * Renders the event property using the configured template.
     *
     * @param  ElementInterface  $element  Event to render
     * @param  EventProp  $prop  Property to render
     * @param  bool  $ics  Whether this is being rendered for an ICS file
     */
    public static function render(ElementInterface $element, EventProp $prop, bool $ics = false): string
    {
        $value = Craft::$app->view->renderObjectTemplate(
            Eventful::getInstance()->settings['event'.ucfirst($prop->value).'Template'],
            $element,
            ['ics' => $ics],
        );

        return $ics ? self::toPlainText($value) : $value;
    }

    /**
     * Converts HTML to plain text, keeping paragraphs, line breaks, list items and links.
     * Text without any HTML tags is returned as is.
     */
    private static function toPlainText(string $html): string
    {
        if (strip_tags($html) === $html) {
            return $html;
        }

        return Html2Text::convert($html, ['ignore_errors' => true]);
    }
}
