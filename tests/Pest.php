<?php

use boundstate\eventful\tests\TestApplication;
use craft\i18n\I18N;
use craft\i18n\PhpMessageSource;
use craft\services\Config;

defined('YII_ENABLE_ERROR_HANDLER') || define('YII_ENABLE_ERROR_HANDLER', false);

require_once __DIR__.'/../vendor/yiisoft/yii2/Yii.php';
require_once __DIR__.'/../vendor/craftcms/cms/src/Craft.php';
require_once __DIR__.'/TestApplication.php';

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
|
| Boot a fresh lightweight application before each test, so that code relying
| on `Craft::$app` (formatting, translations, timezone) works in isolation.
|
*/

uses()->beforeEach(function () {
    new TestApplication([
        'id' => 'eventful-tests',
        'basePath' => dirname(__DIR__),
        'language' => 'en-US',
        'sourceLanguage' => 'en-US',
        'timeZone' => 'UTC',
        'components' => [
            'config' => [
                'class' => Config::class,
                'configDir' => __DIR__.'/_config',
            ],
            'i18n' => [
                'class' => I18N::class,
                'translations' => [
                    'eventful' => [
                        'class' => PhpMessageSource::class,
                        'sourceLanguage' => 'en-US',
                        'basePath' => __DIR__.'/../src/translations',
                        'forceTranslation' => true,
                    ],
                ],
            ],
        ],
    ]);

    setSystemTimezone('UTC');
})->in('unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| In control panel requests, Craft's timezone is the user's preferred one,
| so the system timezone comes from the config instead.
|
*/

function setSystemTimezone(string $timezone): void
{
    Craft::$app->getConfig()->getGeneral()->timezone = $timezone;
    Craft::$app->setTimeZone($timezone);
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| ICU separates date and time ranges with thin spaces, and times from AM/PM
| with narrow no-break spaces, so compare formatted dates with plain spaces.
|
*/

expect()->extend('toBeFormattedAs', fn (string $expected) => expect(str_replace(["\u{2009}", "\u{202F}"], ' ', $this->value))->toBe($expected));
