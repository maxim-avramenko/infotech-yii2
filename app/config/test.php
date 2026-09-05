<?php

use app\components\SmsSender;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';
$container = require __DIR__ . '/container.php';
$container['singletons'][SmsSender::class]['useFileTransport'] = true;

return [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        \app\bootstrap\BookEventsBootstrap::class,
        \app\bootstrap\UserEventsBootstrap::class,
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@webroot' => '@app/web',
        '@web' => '/',
    ],
    'language' => 'en-US',
    'container' => $container,
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => \yii\caching\DummyCache::class,
        ],
        'session' => [
            'class' => \yii\web\Session::class,
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
            'messageClass' => 'yii\symfonymailer\Message',
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
        ],
        'log' => [
            'traceLevel' => 0,
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/test.log',
                ],
            ],
        ],
        'smsSender' => static fn (): \app\components\SmsSender => Yii::$container->get(\app\components\SmsSender::class),
        'queueEmail' => [
            'class' => \yii\queue\sync\Queue::class,
        ],
        'queueSms' => [
            'class' => \yii\queue\sync\Queue::class,
        ],
        'messageBroker' => static fn (): \app\components\MessageBroker => Yii::$container->get(\app\components\MessageBroker::class),
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            'scriptFile' => dirname(__DIR__) . '/web/index-test.php',
            'scriptUrl' => '/index-test.php',
        ],
    ],
    'params' => $params,
];
