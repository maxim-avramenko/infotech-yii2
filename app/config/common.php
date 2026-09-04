<?php

use app\components\MessageBroker;
use app\components\SmsSender;
use yii\queue\LogBehavior;
use yii\queue\redis\Queue;
use yii\symfonymailer\Mailer;

return [
    'cache' => require __DIR__ . '/cache.php',
    'redis' => require __DIR__ . '/redis.php',
    'db' => require __DIR__ . '/db.php',
    'mailer' => [
        'class' => Mailer::class,
        'viewPath' => '@app/mail',
        'useFileTransport' => env('MAIL_USE_FILE_TRANSPORT', true),
    ],
    'smsSender' => static fn (): SmsSender => Yii::$container->get(SmsSender::class),
    'queueEmail' => [
        'class' => Queue::class,
        'redis' => 'redis',
        'channel' => env('QUEUE_EMAIL_CHANNEL', 'queue-email'),
        'as log' => LogBehavior::class,
    ],
    'queueSms' => [
        'class' => Queue::class,
        'redis' => 'redis',
        'channel' => env('QUEUE_SMS_CHANNEL', 'queue-sms'),
        'as log' => LogBehavior::class,
    ],
    'messageBroker' => static fn (): MessageBroker => Yii::$container->get(MessageBroker::class),
];
