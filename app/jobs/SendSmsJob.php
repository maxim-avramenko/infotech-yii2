<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

class SendSmsJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    public string $phone;
    public string $text;

    public function execute($queue): void
    {
        Yii::$app->smsSender->send($this->phone, $this->text);
    }

    public function getTtr(): int
    {
        return 60;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }
}
