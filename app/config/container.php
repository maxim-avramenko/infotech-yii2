<?php

use app\components\BookCoverStorage;
use app\components\MessageBroker;
use app\components\SmsSender;
use app\repositories\BookAuthorRepository;
use app\repositories\BookAuthorSubscriptionRepository;
use app\repositories\BookRepository;
use app\repositories\SmsNotificationRepository;
use app\repositories\UserRepository;
use app\services\BookAuthorService;
use app\services\BookAuthorSubscriptionService;
use app\services\BookService;
use app\services\SmsNotificationService;
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
            'apiUrl' => env('SMS_API_URL') ?: 'https://smspilot.ru/api.php',
            'apiKey' => (string) (env('SMS_API_KEY') ?: ''),
            'from' => (string) (env('SMS_FROM') ?: ''),
            'test' => env('SMS_TEST', true),
            'callbackUrl' => (string) (env('SMS_CALLBACK_URL') ?: ''),
            'callbackMethod' => env('SMS_CALLBACK_METHOD') ?: 'post',
        ],
        Security::class => Security::class,
        UserRepository::class => UserRepository::class,
        UserService::class => UserService::class,
        BookAuthorRepository::class => BookAuthorRepository::class,
        BookAuthorService::class => BookAuthorService::class,
        BookAuthorSubscriptionRepository::class => BookAuthorSubscriptionRepository::class,
        BookAuthorSubscriptionService::class => BookAuthorSubscriptionService::class,
        SmsNotificationRepository::class => SmsNotificationRepository::class,
        SmsNotificationService::class => SmsNotificationService::class,
        BookCoverStorage::class => BookCoverStorage::class,
        BookRepository::class => BookRepository::class,
        BookService::class => BookService::class,
    ],
];
