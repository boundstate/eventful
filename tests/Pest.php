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

uses()->beforeEach(function (): void {
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
})->in('unit');
