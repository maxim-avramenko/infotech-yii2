<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\components\SmsSender;
use app\models\SmsNotification;
use app\repositories\SmsNotificationRepository;
use app\services\SmsNotificationService;
use tests\unit\DbTestCase;

class SmsNotificationServiceTest extends DbTestCase
{
    public function testEnqueueAndSendDueMarksSent(): void
    {
        $sender = $this->createMock(SmsSender::class);
        $sender->expects($this->once())->method('send')->with('79001234567', 'Hi')->willReturn(true);
        $service = new SmsNotificationService(new SmsNotificationRepository(), $sender);

        $notification = $service->enqueue('79001234567', 'Hi');
        verify($notification->status)->equals(SmsNotification::STATUS_PENDING);
        verify($service->sendDue(10))->equals(1);
        $notification->refresh();
        verify($notification->status)->equals(SmsNotification::STATUS_SENT);
    }

    public function testSendDuePostponesWhenSenderReturnsFalse(): void
    {
        $sender = $this->createMock(SmsSender::class);
        $sender->method('send')->willReturn(false);
        $service = new SmsNotificationService(new SmsNotificationRepository(), $sender);
        $notification = $service->enqueue('79001234567', 'Hi');

        verify($service->sendDue())->equals(0);
        $notification->refresh();
        verify($notification->status)->equals(SmsNotification::STATUS_PENDING);
        verify($notification->attempts)->equals(1);
        verify($notification->last_error)->stringContainsString('SMS sender returned false');
    }

    public function testSendDuePostponesWhenSenderThrows(): void
    {
        $sender = $this->createMock(SmsSender::class);
        $sender->method('send')->willThrowException(new \RuntimeException('network'));
        $service = new SmsNotificationService(new SmsNotificationRepository(), $sender);
        $notification = $service->enqueue('79001234567', 'Hi');

        verify($service->sendDue())->equals(0);
        $notification->refresh();
        verify($notification->last_error)->equals('network');
    }

    public function testEnqueueForBookChunksAndDeduplicates(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);
        $repo = $this->getMockBuilder(SmsNotificationRepository::class)
            ->onlyMethods(['insertPendingBatch'])
            ->getMock();
        $repo->expects($this->exactly(2))->method('insertPendingBatch');
        $service = new SmsNotificationService($repo, $this->createMock(SmsSender::class));

        $messages = [];
        for ($i = 0; $i < 501; $i++) {
            $messages[] = ['phone' => sprintf('7900%07d', $i), 'text' => 't'];
        }
        $service->enqueueForBook((int) $book->id, $messages);

        $real = new SmsNotificationService(new SmsNotificationRepository(), $this->createMock(SmsSender::class));
        $real->enqueueForBook((int) $book->id, [
            ['phone' => '79001111111', 'text' => 'a'],
            ['phone' => '79001111111', 'text' => 'b'],
        ]);
        verify(SmsNotification::find()->andWhere(['book_id' => $book->id])->count())->equals(1);
    }
}
