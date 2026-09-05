<?php

declare(strict_types=1);

namespace app\jobs;

use app\services\BookAuthorSubscriptionService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

class CreateBookSmsNotificationsJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    public int $bookId;

    public function execute($queue): void
    {
        Yii::createObject(BookAuthorSubscriptionService::class)->createSmsNotificationsForBook($this->bookId);
    }

    public function getTtr(): int
    {
        return 300;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 20;
    }
}
