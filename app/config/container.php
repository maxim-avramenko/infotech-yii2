<?php

use app\components\MessageBroker;
use app\components\SmsSender;
use app\repositories\UserRepository;
use app\services\UserService;
use yii\base\Security;

return [
    'singletons' => [
        MessageBroker::class => [
            'class' => MessageBroker::class,
            'emailQueue' => 'queueEmail',
            'smsQueue' => 'queueSms',
        ],
        SmsSender::class => [
            'class' => SmsSender::class,
            'useFileTransport' => env('SMS_USE_FILE_TRANSPORT', true),
            'filePath' => '@runtime/sms',
        ],
        Security::class => Security::class,
        UserRepository::class => UserRepository::class,
        UserService::class => UserService::class,
    ],
];
