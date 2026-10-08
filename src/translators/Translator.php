<?php

namespace boundstate\eventful\translators;

use Craft;
use craft\i18n\Locale;
use Recurr\Transformer\TranslatorInterface;

/**
 * Routes Recurr's text transformer strings through Craft's `eventful` translation category.
 *
 * Message keys mirror Recurr's keys, with `%param%` placeholders converted to Craft's `{param}` syntax.
 */
class Translator implements TranslatorInterface
{
    public function __construct(
        private readonly ?string $language = null,
    ) {}

    public function trans(mixed $string, array $params = []): string|array
    {
        return match ($string) {
            'day_names' => $this->getLocale()->getWeekDayNames(Locale::LENGTH_FULL, false),
            'month_names' => $this->getLocale()->getMonthNames(Locale::LENGTH_FULL),
            'day_date' => $this->getLocale()->getFormatter()->asDate((int) $params['date'], Locale::LENGTH_LONG),
            'day_month' => $this->t('day_month', [
                'month' => $this->getLocale()->getMonthName((int) $params['month'], Locale::LENGTH_FULL, false),
                'day' => (int) $params['day'],
            ]),
            'ordinal_number' => $this->ordinal(
                (int) $params['number'],
                ! empty($params['has_negatives']),
                ! empty($params['day_in_month']),
            ),
            default => $this->message($string, $params),
        };
    }

    private function message(string $key, array $params): string
    {
        // Yii falls back to the source language for empty translations, so languages that intentionally
        // omit a word (e.g. `the_for_weekday` in German) translate it to a single space instead.
        return trim($this->t(preg_replace('/%(\w+)%/', '{$1}', $key), $params));
    }

    private function ordinal(int $number, bool $hasNegatives, bool $inMonth): string
    {
        $negative = $number < 0;
        $ordinal = $this->t($negative ? 'ordinal_number_negative' : 'ordinal_number', [
            'number' => abs($number),
            'inMonth' => $inMonth ? 'yes' : 'no',
        ]);

        if ($hasNegatives) {
            $ordinal = $this->t('ordinal_number_day_suffix', [
                'ordinal' => $ordinal,
                'negative' => $negative ? 'yes' : 'no',
            ]);
        }

        return $ordinal;
    }

    private function t(string $message, array $params = []): string
    {
        return Craft::t('eventful', $message, $params, $this->language);
    }

    private function getLocale(): Locale
    {
        return Craft::$app->getI18n()->getLocaleById($this->language ?? Craft::$app->language);
    }
}
