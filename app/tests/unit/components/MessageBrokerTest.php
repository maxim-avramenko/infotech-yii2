<?php

declare(strict_types=1);

namespace tests\unit\components;

use app\components\MessageBroker;
use app\jobs\CreateBookSmsNotificationsJob;
use app\jobs\SendEmailJob;
use app\models\SmsNotification;
use app\services\SmsNotificationService;
use tests\unit\DbTestCase;
use yii\queue\Queue;

class MessageBrokerTest extends DbTestCase
{
    public function testEmailPushesSendEmailJob(): void
    {
        $emailQueue = $this->createMock(Queue::class);
        $smsQueue = $this->createMock(Queue::class);
        $emailQueue->expects($this->once())->method('push')->with($this->callback(static function ($job): bool {
            return $job instanceof SendEmailJob
                && $job->to === 'a@b.c'
                && $job->htmlBody === 'html';
        }))->willReturn(11);

        $broker = new MessageBroker($this->createMock(SmsNotificationService::class), [
            'emailQueue' => $emailQueue,
            'smsQueue' => $smsQueue,
        ]);
        $broker->init();

        verify($broker->email('a@b.c', 'S', 'text', 'html'))->equals(11);
    }

    public function testEmailDefaultsHtmlBodyToText(): void
    {
        $emailQueue = $this->createMock(Queue::class);
        $emailQueue->expects($this->once())->method('push')->with($this->callback(static function ($job): bool {
            return $job instanceof SendEmailJob && $job->htmlBody === 'plain';
        }))->willReturn(1);

        $broker = new MessageBroker($this->createMock(SmsNotificationService::class), [
            'emailQueue' => $emailQueue,
            'smsQueue' => $this->createMock(Queue::class),
        ]);
        $broker->init();
        $broker->email('a@b.c', 'S', 'plain');
    }

    public function testSmsEnqueuesNotification(): void
    {
        $sms = $this->createMock(SmsNotificationService::class);
        $notification = new SmsNotification();
        $notification->id = 77;
        $sms->expects($this->once())->method('enqueue')->with('7900', 'hi')->willReturn($notification);

        $broker = new MessageBroker($sms, [
            'emailQueue' => $this->createMock(Queue::class),
            'smsQueue' => $this->createMock(Queue::class),
        ]);
        $broker->init();
        verify($broker->sms('7900', 'hi'))->equals(77);
    }

    public function testEnqueueBookSubscriberSmsPushesJob(): void
    {
        $smsQueue = $this->createMock(Queue::class);
        $smsQueue->expects($this->once())->method('push')->with($this->callback(static function ($job): bool {
            return $job instanceof CreateBookSmsNotificationsJob && $job->bookId === 5;
        }))->willReturn(3);

        $broker = new MessageBroker($this->createMock(SmsNotificationService::class), [
            'emailQueue' => $this->createMock(Queue::class),
            'smsQueue' => $smsQueue,
        ]);
        $broker->init();
        verify($broker->enqueueBookSubscriberSms(5))->equals(3);
    }
}
