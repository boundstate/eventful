<?php

namespace boundstate\eventful\tests;

use craft\i18n\Locale;
use craft\services\Config;
use yii\console\Application;

/**
 * A lightweight console application that provides just enough of Craft's
 * application API (config, i18n, formatting) for unit tests, without a database.
 */
class TestApplication extends Application
{
    public function getConfig(): Config
    {
        return $this->get('config');
    }

    public function getFormattingLocale(): Locale
    {
        return $this->getI18n()->getLocaleById($this->language);
    }
}
