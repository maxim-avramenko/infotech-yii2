<?php

declare(strict_types=1);

namespace tests\unit\jobs;

use app\components\SmsSender;
use app\jobs\CreateBookSmsNotificationsJob;
use app\jobs\SendEmailJob;
use app\jobs\SendSmsJob;
use app\services\BookAuthorSubscriptionService;
use tests\unit\DbTestCase;
use Yii;
use yii\queue\Queue;

class JobsTest extends DbTestCase
{
    public function testSendEmailJobSendsMailAndRetries(): void
    {
        $job = new SendEmailJob([
            'to' => 'to@example.com',
            'subject' => 'Subject',
            'textBody' => 'Text',
        ]);
        $job->execute($this->createMock(Queue::class));

        $this->tester->seeEmailIsSent();
        $email = $this->tester->grabLastSentEmail();
        verify($email->getTo())->arrayHasKey('to@example.com');
        verify($job->getTtr())->equals(60);
        verify($job->canRetry(2, new \RuntimeException()))->true();
        verify($job->canRetry(3, new \RuntimeException()))->false();
    }

    public function testSendSmsJobWritesFile(): void
    {
        $dir = Yii::getAlias('@runtime/sms');
        foreach (glob($dir . '/*.txt') ?: [] as $file) {
            unlink($file);
        }

        $job = new SendSmsJob(['phone' => '79001234567', 'text' => 'Ping']);
        $job->execute($this->createMock(Queue::class));

        $files = glob($dir . '/*.txt') ?: [];
        verify($files)->notEmpty();
        verify(file_get_contents($files[array_key_last($files)]))->stringContainsString('79001234567');
        verify($job->getTtr())->equals(60);
        verify($job->canRetry(1, new \RuntimeException()))->true();
    }

    public function testSendSmsJobDelegatesToAppSmsSender(): void
    {
        $sender = $this->createMock(SmsSender::class);
        $sender->expects($this->once())->method('send')->with('79005554433', 'Queued')->willReturn(true);
        Yii::$app->set('smsSender', $sender);

        try {
            $job = new SendSmsJob(['phone' => '79005554433', 'text' => 'Queued']);
            $job->execute($this->createMock(Queue::class));
        } finally {
            Yii::$app->set('smsSender', static fn (): SmsSender => Yii::$container->get(SmsSender::class));
        }
    }

    public function testCreateBookSmsNotificationsJobDelegatesToService(): void
    {
        $service = $this->createMock(BookAuthorSubscriptionService::class);
        $service->expects($this->once())->method('createSmsNotificationsForBook')->with(15);
        Yii::$container->set(BookAuthorSubscriptionService::class, $service);

        $job = new CreateBookSmsNotificationsJob(['bookId' => 15]);
        $job->execute($this->createMock(Queue::class));
        verify($job->getTtr())->equals(300);
        verify($job->canRetry(19, new \RuntimeException()))->true();
        verify($job->canRetry(20, new \RuntimeException()))->false();

        Yii::$container->clear(BookAuthorSubscriptionService::class);
    }
}
