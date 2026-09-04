<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';

return [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'en-US',
    'container' => require __DIR__ . '/container.php',
    'components' => [
        'db' => $db,
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
            'identityClass' => 'app\models\User',
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
        ],
    ],
    'params' => $params,
];
